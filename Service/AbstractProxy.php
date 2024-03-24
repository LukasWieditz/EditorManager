<?php

/*!
 * KL/EditorManager/Service/AbstractProxy.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Service;

use InvalidArgumentException;
use KL\EditorManager\Entity\AbstractProxy as AbstractProxyEntity;
use KL\EditorManager\Repository\AbstractProxy as AbstractProxyRepo;
use XF;
use XF\Db\Exception;
use XF\PrintableException;
use XF\Service\AbstractService;
use XF\Util\File;

/**
 * Class AbstractProxy
 * @package KL\EditorManager\Service
 */
abstract class AbstractProxy extends AbstractService
{
    /**
     * @var bool
     */
    protected $forceRefresh = false;
    /**
     * @var int
     */
    protected $maxConcurrent = 10;

    /**
     * @var AbstractProxyRepo
     */
    protected $proxyRepo;

    /**
     * @return string
     */
    abstract protected function getProxyClass(): string;

    /**
     *
     */
    protected function setup(): void
    {
        $this->proxyRepo = $this->repository($this->getProxyClass());
    }

    /**
     * @param bool $value
     */
    public function forceRefresh($value = true): void
    {
        $this->forceRefresh = (bool)$value;
    }

    /**
     * @return bool
     */
    public function isRefreshForced(): bool
    {
        return $this->forceRefresh;
    }

    /**
     * @param $url
     * @return AbstractProxyEntity
     * @throws PrintableException
     * @throws PrintableException
     */
    public function getResource($url): AbstractProxyEntity
    {
        $resource = $this->proxyRepo->getByUrl($url);
        if ($resource) {
            if ($this->isRefreshRequired($resource)) {
                $this->refetchResource($resource);
            }
        } else {
            if ($this->canFetchResource()) {
                $resource = $this->fetchNewResource($url);
            }
        }

        return $resource;
    }

    /**
     * @param AbstractProxyEntity $resource
     * @return bool
     */
    protected function isRefreshRequired(AbstractProxyEntity $resource): bool
    {
        if ($this->forceRefresh) {
            return true;
        }

        return $resource->isRefreshRequired() && $this->canFetchResource();
    }

    /**
     * @return bool
     */
    public function canFetchResource(): bool
    {
        if ($this->forceRefresh) {
            return true;
        }

        $active = $this->proxyRepo->getTotalActiveFetches();
        return ($active < $this->maxConcurrent);
    }

    /**
     * @param $url
     * @return AbstractProxyEntity|null
     * @throws PrintableException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function fetchNewResource($url)
    {
        /** @var AbstractProxyEntity $resource */
        $resource = $this->em()->create($this->getProxyClass());
        $resource->url = $url;
        $resource->pruned = true;
        $resource->is_processing = time(); // may have slept, need to set to now

        try {
            $resource->save();
        } catch (Exception $e) {
            // this is mostly a duplicate key issue
            return null;
        }

        $fetchResults = $this->fetchResourceDataFromUrl($resource->url);
        $this->finalizeFromFetchResults($resource, $fetchResults);

        return $resource;
    }

    /**
     * @param AbstractProxyEntity $resource
     * @return AbstractProxyEntity
     * @throws PrintableException
     */
    public function refetchResource(AbstractProxyEntity $resource): AbstractProxyEntity
    {
        $resource->is_processing = time();
        $resource->save();

        $fetchResults = $this->fetchResourceDataFromUrl($resource->url);
        $this->finalizeFromFetchResults($resource, $fetchResults);

        return $resource;
    }

    /**
     * @param $url
     * @return array
     */
    public function testResourceFetch($url): array
    {
        $results = $this->fetchResourceDataFromUrl($url);
        if ($results['dataFile']) {
            @unlink($results['dataFile']);
            $results['dataFile'] = null;
        }

        return $results;
    }

    /**
     * @return int
     */
    abstract protected function getProxyMaxSize(): int;

    /**
     * @return array
     */
    abstract protected function getValidExtensionMap(): array;

    /**
     * @return string
     */
    abstract protected function getType(): string;

    /**
     * @param $url
     * @return array
     */
    protected function fetchResourceDataFromUrl($url): array
    {
        $url = $this->proxyRepo->cleanUrlForFetch($url);
        if (!preg_match('#^https?://#i', $url)) {
            throw new InvalidArgumentException("URL must be http or https");
        }

        $urlParts = @parse_url($url);

        $validResource = false;
        $fileName = !empty($urlParts['path']) ? basename($urlParts['path']) : null;
        $mimeType = null;
        $error = null;
        $streamFile = File::getTempDir() . '/' . strtr(md5($url) . '-' . uniqid(), '/\\.', '---') . '.temp';

        $proxyMaxSize = $this->getProxyMaxSize() * 1024;

        try {
            $options = [
                'headers' => [
                    'Accept' => $this->getType() . '/*,*'
                ]
            ];
            $limits = [
                'time' => 8,
                'bytes' => $proxyMaxSize ?: -1
            ];
            $response = $this->app->http()->reader()->getUntrusted($url, $limits, $streamFile, $options, $error);
        } catch (\Exception $e) {
            $response = null;
            $error = $e->getMessage();
        }

        if ($response) {
            $response->getBody()->close();

            if ($response->getStatusCode() == 200) {
                $disposition = (string)$response->getHeader('Content-Disposition');
                if ($disposition && preg_match('/filename=(\'|"|)(.+)\\1/siU', $disposition, $match)) {
                    $fileName = $match[2];
                }
                if (!$fileName) {
                    $fileName = $this->getType();
                }

                $resourceInfo = filesize($streamFile) && @pathinfo($streamFile);
                if ($resourceInfo) {
                    $resourceType = (string)$response->getHeader('content-type');

                    $extension = File::getFileExtension($fileName);
                    $extensionMap = $this->getValidExtensionMap();
                    if (isset($extensionMap[$resourceType])) {
                        $mimeType = $resourceType;

                        $validExtensions = $extensionMap[$resourceType];
                        if (!in_array($extension, $validExtensions)) {
                            $extensionStart = strrpos($fileName, '.');
                            $fileName = (
                                $extensionStart
                                    ? substr($fileName, 0, $extensionStart)
                                    : $fileName
                                ) . '.' . $validExtensions[0];
                        }

                        $validResource = true;
                    } else {
                        $error = XF::phraseDeferred('kl_em_resource_is_invalid_type');
                    }
                } else {
                    $error = XF::phraseDeferred('kl_em_file_not_a_resource');
                }
            } else {
                $error = XF::phraseDeferred('received_unexpected_response_code_x_message_y', [
                    'code' => $response->getStatusCode(),
                    'message' => $response->getReasonPhrase()
                ]);
            }
        }

        if (!$validResource) {
            @unlink($streamFile);
        }

        return [
            'valid' => $validResource,
            'error' => $error,
            'dataFile' => $validResource ? $streamFile : null,
            'fileName' => $fileName,
            'mimeType' => $mimeType
        ];
    }

    /**
     * @param AbstractProxyEntity $resource
     * @param array $fetchResults
     * @throws PrintableException
     */
    protected function finalizeFromFetchResults(AbstractProxyEntity $resource, array $fetchResults): void
    {
        $resource->is_processing = 0;

        if ($fetchResults['valid']) {
            $newResourcePath = $resource->getAbstractedFilePath();

            if (File::copyFileToAbstractedPath($fetchResults['dataFile'], $newResourcePath)) {
                $resource->fetch_date = time();
                $resource->file_name = $fetchResults['fileName'];
                $resource->file_size = filesize($fetchResults['dataFile']);
                $resource->mime_type = $fetchResults['mimeType'];
                $resource->pruned = false;
                $resource->failed_date = 0;
                $resource->fail_count = 0;
            } else {
                $resource->pruned = true;
            }

            @unlink($fetchResults['dataFile']);
        } else {
            $resource->failed_date = time();
            $resource->fail_count++;
        }

        $resource->save();
    }
}