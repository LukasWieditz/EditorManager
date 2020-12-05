<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Service;

use InvalidArgumentException;
use KL\EditorManager\Entity\VideoProxy as VideoProxyEntity;
use KL\EditorManager\Repository\VideoProxy as VideoProxyRepo;
use XF;
use XF\Db\Exception;
use XF\PrintableException;
use XF\Service\AbstractService;
use XF\Util\File;

/**
 * Class VideoProxy
 * @package KL\EditorManager\Service
 */
class VideoProxy extends AbstractService
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
     * @var VideoProxyRepo
     */
    protected $proxyRepo;

    /**
     *
     */
    protected function setup()
    {
        $this->proxyRepo = $this->repository('KL\EditorManager:VideoProxy');
    }

    /**
     * @param bool $value
     */
    public function forceRefresh($value = true)
    {
        $this->forceRefresh = (bool)$value;
    }

    /**
     * @return bool
     */
    public function isRefreshForced()
    {
        return $this->forceRefresh;
    }

    /**
     * @param $url
     * @return VideoProxyEntity|null
     * @throws PrintableException
     * @throws PrintableException
     */
    public function getVideo($url)
    {
        $video = $this->proxyRepo->getByUrl($url);
        if ($video) {
            if ($this->isRefreshRequired($video)) {
                $this->refetchVideo($video);
            }
        } else {
            if ($this->canFetchVideo()) {
                $video = $this->fetchNewVideo($url);
            }
        }

        return $video;
    }

    /**
     * @param VideoProxyEntity $video
     * @return bool
     */
    protected function isRefreshRequired(VideoProxyEntity $video)
    {
        if ($this->forceRefresh) {
            return true;
        }

        return $video->isRefreshRequired() && $this->canFetchVideo();
    }

    /**
     * @return bool
     */
    public function canFetchVideo()
    {
        if ($this->forceRefresh) {
            return true;
        }

        $active = $this->proxyRepo->getTotalActiveFetches();
        return ($active < $this->maxConcurrent);
    }

    /**
     * @param $url
     * @return VideoProxyEntity|null
     * @throws PrintableException
     */
    public function fetchNewVideo($url)
    {
        /** @var VideoProxyEntity $video */
        $video = $this->em()->create('KL\EditorManager:VideoProxy');
        $video->url = $url;
        $video->pruned = true;
        $video->is_processing = time(); // may have slept, need to set to now

        try {
            $video->save();
        } catch (Exception $e) {
            // this is mostly a duplicate key issue
            return null;
        }

        $fetchResults = $this->fetchVideoDataFromUrl($video->url);
        $this->finalizeFromFetchResults($video, $fetchResults);

        return $video;
    }

    /**
     * @param VideoProxyEntity $video
     * @return VideoProxyEntity
     * @throws PrintableException
     */
    public function refetchVideo(VideoProxyEntity $video)
    {
        $video->is_processing = time();
        $video->save();

        $fetchResults = $this->fetchVideoDataFromUrl($video->url);
        $this->finalizeFromFetchResults($video, $fetchResults);

        return $video;
    }

    /**
     * @param $url
     * @return array
     */
    public function testVideoFetch($url)
    {
        $results = $this->fetchVideoDataFromUrl($url);
        if ($results['dataFile']) {
            @unlink($results['dataFile']);
            $results['dataFile'] = null;
        }

        return $results;
    }

    /**
     * @param $url
     * @return array
     */
    protected function fetchVideoDataFromUrl($url)
    {
        $url = $this->proxyRepo->cleanUrlForFetch($url);
        if (!preg_match('#^https?://#i', $url)) {
            throw new InvalidArgumentException("URL must be http or https");
        }

        $urlParts = @parse_url($url);

        $validVideo = false;
        $fileName = !empty($urlParts['path']) ? basename($urlParts['path']) : null;
        $mimeType = null;
        $error = null;
        $streamFile = File::getTempDir() . '/' . strtr(md5($url) . '-' . uniqid(), '/\\.', '---') . '.temp';
        $videoProxyMaxSize = $this->app->options()->klEMVideoProxyMaxSize * 1024;

        try {
            $options = [
                'headers' => [
                    'Accept' => 'video/*,*'
                ]
            ];
            $limits = [
                'time' => 8,
                'bytes' => $videoProxyMaxSize ?: -1
            ];
            $response = $this->app->http()->reader()->getUntrusted($url, $limits, $streamFile, $options, $error);
        } catch (\Exception $e) {
            $response = null;
            $error = $e->getMessage();
        }

        if ($response) {
            $response->getBody()->close();

            if ($response->getStatusCode() == 200) {
                $disposition = (string) $response->getHeader('Content-Disposition');
                if ($disposition && preg_match('/filename=(\'|"|)(.+)\\1/siU', $disposition, $match)) {
                    $fileName = $match[2];
                }
                if (!$fileName) {
                    $fileName = 'video';
                }

                $videoInfo = filesize($streamFile) ? @pathinfo($streamFile) : false;
                if ($videoInfo) {
                    $videoType = (string) $response->getHeader('content-type');

                    $extension = File::getFileExtension($fileName);
                    $extensionMap = [
                        'video/webm' => ['webm'],
                        'video/mp4' => ['mp4']
                    ];
                    if (isset($extensionMap[$videoType])) {
                        $mimeType = $videoType;
                        $validExtensions = $extensionMap[$videoType];
                        if (!in_array($extension, $validExtensions)) {
                            $extensionStart = strrpos($fileName, '.');
                            $fileName = (
                                $extensionStart
                                    ? substr($fileName, 0, $extensionStart)
                                    : $fileName
                                ) . '.' . $validExtensions[0];
                        }

                        $validVideo = true;
                    } else {
                        $error = XF::phraseDeferred('kl_em_video_is_invalid_type');
                    }
                } else {
                    $error = XF::phraseDeferred('kl_em_file_not_a_video');
                }
            } else {
                $error = XF::phraseDeferred('received_unexpected_response_code_x_message_y', [
                    'code' => $response->getStatusCode(),
                    'message' => $response->getReasonPhrase()
                ]);
            }
        }

        if (!$validVideo) {
            @unlink($streamFile);
        }

        return [
            'valid' => $validVideo,
            'error' => $error,
            'dataFile' => $validVideo ? $streamFile : null,
            'fileName' => $fileName,
            'mimeType' => $mimeType
        ];
    }

    /**
     * @param VideoProxyEntity $video
     * @param array $fetchResults
     * @throws PrintableException
     */
    protected function finalizeFromFetchResults(VideoProxyEntity $video, array $fetchResults)
    {
        $video->is_processing = 0;

        if ($fetchResults['valid']) {
            $newVideoPath = $video->getAbstractedVideoPath();

            if (File::copyFileToAbstractedPath($fetchResults['dataFile'], $newVideoPath)) {
                $video->fetch_date = time();
                $video->file_name = $fetchResults['fileName'];
                $video->file_size = filesize($fetchResults['dataFile']);
                $video->mime_type = $fetchResults['mimeType'];
                $video->pruned = false;
                $video->failed_date = 0;
                $video->fail_count = 0;
            } else {
                $video->pruned = true;
            }

            @unlink($fetchResults['dataFile']);
        } else {
            $video->failed_date = time();
            $video->fail_count++;
        }

        $video->save();
    }
}