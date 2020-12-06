<?php

/*!
 * KL/EditorManager/XF/Proxy/Controller.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Proxy;

use KL\EditorManager\Entity\AbstractProxy;
use League\Flysystem\FileNotFoundException;
use XF\App;
use XF\Db\Exception;
use XF\Http\Request;
use XF\Http\Response;
use XF\PrintableException;
use XF\Proxy\Linker;

/**
 * Class Controller
 * @package KL\EditorManager\XF\Proxy
 */
class Controller extends XFCP_Controller
{
    /**
     * Controller constructor.
     * @param App $app
     * @param Linker $linker
     * @param Request|null $request
     */
    public function __construct(App $app, Linker $linker, Request $request = null)
    {
        parent::__construct($app, $linker, $request);
    }

    protected function _resolveKLEMProxyRecursion(string $type, Request $request, $url)
    {
        $uriParts = explode('?', $request->getFullRequestUri(), 2);
        $subMatchTest = $uriParts[0] . '?';

        $subUrl = $url;
        $subHash = null;

        // Recursion can happen, mostly if people copy proxied image URLs.
        // Try to resolve that here.
        do {
            $hasSubMatch = false;

            if (strpos($subUrl, $subMatchTest) === 0) {
                $subMatchQs = substr($subUrl, strlen($subMatchTest));
                parse_str($subMatchQs, $subMatchParams);
                if (isset($subMatchParams[$type])
                    && is_scalar($subMatchParams[$type])
                    && isset($subMatchParams['hash'])
                    && is_scalar($subMatchParams['hash'])
                ) {
                    $subMatchUrl = trim(strval($subMatchParams[$type]));
                    $subMatchHash = trim(strval($subMatchParams['hash']));
                    if ($this->linker->verifyHash($subMatchUrl, $subMatchHash)) {
                        $subUrl = $subMatchUrl;
                        $subHash = $subMatchHash;
                        $hasSubMatch = true;
                    }
                }
            }
        } while ($hasSubMatch);

        if ($subHash) {
            return [$subUrl, $subHash];
        } else {
            return null;
        }
    }

    /**
     * @param Request $request
     * @param $url
     * @return array|null
     */
    protected function resolveKLEMVideoProxyRecursion(Request $request, $url)
    {
        return $this->_resolveKLEMProxyRecursion('video', $request, $url);
    }

    /**
     * @param Request $request
     * @param $url
     * @return array|null
     */
    protected function resolveKLEMAudioProxyRecursion(Request $request, $url)
    {
        return $this->_resolveKLEMProxyRecursion('audio', $request, $url);
    }

    /**
     * @param $url
     * @param $hash
     * @param $entityClass
     * @param $validateFunction
     * @param $applyResponseHeaderFunction
     * @return Response
     * @throws Exception
     * @throws FileNotFoundException
     * @throws PrintableException
     */
    protected function _outputKLEMResource(
        $url,
        $hash,
        $entityClass,
        $validateFunction,
        $applyResponseHeaderFunction
    ): Response {
        $error = null;
        if ($this->$validateFunction($url, $hash, $error)) {
            /** @var \KL\EditorManager\Service\AbstractProxy $proxy */
            $proxy = $this->app->service($entityClass);
            $resource = $proxy->getResource($url);
            if (!$resource || !$resource->isValid()) {
                $resource = null;
            }
        } else {
            $resource = null;
        }

        if (!$resource) {
            if (!$error) {
                $error = self::ERROR_FAILED;
            }

            /** @var \KL\EditorManager\Repository\AbstractProxy $proxyRepo */
            $proxyRepo = $this->app->repository($entityClass);
            $video = $proxyRepo->getPlaceholder();
        }

        if (!$error) {
            $proxyRepo = $this->app->repository($entityClass);

            $proxyRepo->logView($video);
            if ($this->referrer && $this->app->options()->klEMVideoAudioProxyReferrer['enabled']) {
                $proxyRepo->logReferrer($video, $this->referrer);
            }
        }

        $response = $this->app->response();
        $this->$applyResponseHeaderFunction($response, $resource, $error);

        if ($resource->isPlaceholder()) {
            $body = $response->responseFile($resource->getPlaceholderPath());
        } else {
            $stream = $this->app->fs()->readStream($resource->getAbstractedFilePath());
            $body = $response->responseStream($stream, $resource->file_size);
        }

        $response->body($body);

        return $response;
    }

    /**
     * @param $url
     * @param $hash
     * @return Response
     * @throws FileNotFoundException
     * @throws Exception
     * @throws PrintableException
     */
    public function outputKLEMVideo($url, $hash): Response
    {
        return $this->_outputKLEMResource(
            $url,
            $hash,
            'KL\EditorManager:VideoProxy',
            'validateKLEMVideoRequest',
            'applyKLEMVideoResponseHeaders'
        );
    }

    /**
     * @param $url
     * @param $hash
     * @return Response
     * @throws FileNotFoundException
     * @throws Exception
     * @throws PrintableException
     * @throws PrintableException
     */
    public function outputKLEMAudio($url, $hash): Response
    {
        return $this->_outputKLEMResource(
            $url,
            $hash,
            'KL\EditorManager:AudioProxy',
            'validateKLEMAudioRequest',
            'applyKLEMAudioResponseHeaders'
        );
    }

    /**
     * @param array $allowedTypes
     * @param Response $response
     * @param AbstractProxy $resource
     * @param $error
     */
    protected function _applyKLEMResponseHeaders(
        array $allowedTypes,
        Response $response,
        AbstractProxy $resource,
        $error
    ): void {
        if (!$error) {
            $response->header('Cache-Control', 'public');

            $expectedETag = $resource->getETagValue();
            if ($expectedETag) {
                $response->header('ETag', '"' . $expectedETag . '"', true);

                if ($this->eTag && $this->eTag === "\"$expectedETag\"") {
                    $response->httpCode(304);
                    $response->removeHeader('Last-Modified');
                    return;
                }
            }
        }

        if (in_array($resource->mime_type, $allowedTypes)) {
            $response->contentType($resource->mime_type);
            $response->setDownloadFileName($resource->file_name, true);
        } else {
            $response->contentType('application/octet-stream');
            $response->setDownloadFileName($resource->file_name);
        }

        $response->header('X-Content-Type-Options', 'nosniff');

        if ($error) {
            $response->header('X-Proxy-Error', $error);
        }
    }

    /**
     * @param Response $response
     * @param \KL\EditorManager\Entity\VideoProxy $video
     * @param $error
     */
    protected function applyKLEMVideoResponseHeaders(
        Response $response,
        \KL\EditorManager\Entity\VideoProxy $video,
        $error
    ): void {
        $this->_applyKLEMResponseHeaders([
            'video/mp4',
            'video/webm'
        ], $response, $video, $error);
    }

    /**
     * @param Response $response
     * @param \KL\EditorManager\Entity\AudioProxy $audio
     * @param $error
     */
    protected function applyKLEMAudioResponseHeaders(
        Response $response,
        \KL\EditorManager\Entity\AudioProxy $audio,
        $error
    ): void {
        $this->_applyKLEMResponseHeaders([
            'audio/mp4',
            'audio/mp3'
        ], $response, $audio, $error);
    }

    /**
     * @param $type
     * @param $url
     * @param $hash
     * @param null $error
     * @return bool
     */
    protected function _validateKLEMRequest($type, $url, $hash, &$error = null): bool
    {
        if (!$this->linker->isTypeEnabled($type)) {
            $error = self::ERROR_DISABLED;
            return false;
        }

        if (!$this->validateProxyRequestGeneric($url, $hash, $error)) {
            return false;
        }

        return true;
    }

    /**
     * @param $url
     * @param $hash
     * @param null $error
     * @return bool
     */
    protected function validateKLEMVideoRequest($url, $hash, &$error = null): bool
    {
        return $this->_validateKLEMRequest('video', $url, $hash, $error);
    }

    /**
     * @param $url
     * @param $hash
     * @param null $error
     * @return bool
     */
    protected function validateKLEMAudioRequest($url, $hash, &$error = null): bool
    {
        return $this->_validateKLEMRequest('audio', $url, $hash, $error);
    }
}