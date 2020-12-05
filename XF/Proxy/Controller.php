<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Proxy;

use KL\EditorManager\Repository\VideoProxy;
use KL\EditorManager\Service\AudioProxy;
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

    /**
     * @param Request $request
     * @param $url
     * @return array|null
     */
    public function resolveKLEMVideoProxyRecursion(Request $request, $url)
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
                if (isset($subMatchParams['video'])
                    && is_scalar($subMatchParams['video'])
                    && isset($subMatchParams['hash'])
                    && is_scalar($subMatchParams['hash'])
                ) {
                    $subMatchUrl = trim(strval($subMatchParams['video']));
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
    public function resolveKLEMAudioProxyRecursion(Request $request, $url)
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
                if (isset($subMatchParams['audio'])
                    && is_scalar($subMatchParams['audio'])
                    && isset($subMatchParams['hash'])
                    && is_scalar($subMatchParams['hash'])
                ) {
                    $subMatchUrl = trim(strval($subMatchParams['audio']));
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
     * @param $url
     * @param $hash
     * @return Response
     * @throws FileNotFoundException
     * @throws Exception
     * @throws PrintableException
     * @throws PrintableException
     */
    public function outputKLEMVideo($url, $hash)
    {
        if ($this->validateKLEMVideoRequest($url, $hash, $error)) {
            /** @var \KL\EditorManager\Service\VideoProxy $videoProxy */
            $videoProxy = $this->app->service('KL\EditorManager:VideoProxy');
            $video = $videoProxy->getVideo($url);
            if (!$video || !$video->isValid()) {
                $video = null;
            }
        } else {
            $video = null;
        }

        if (!$video) {
            if (!$error) {
                $error = self::ERROR_FAILED;
            }

            /** @var VideoProxy $proxyRepo */
            $proxyRepo = $this->app->repository('KL\EditorManager:VideoProxy');
            $video = $proxyRepo->getPlaceholderVideo();
        }

        if (!$error) {
            $proxyRepo = $this->app->repository('KL\EditorManager:VideoProxy');

            $proxyRepo->logVideoView($video);
            if ($this->referrer && $this->app->options()->klEMVideoAudioProxyReferrer['enabled']) {
                $proxyRepo->logVideoReferrer($video, $this->referrer);
            }
        }

        $response = $this->app->response();
        $this->applyKLEMVideoResponseHeaders($response, $video, $error);

        if ($video->isPlaceholder()) {
            $body = $response->responseFile($video->getPlaceholderPath());
        } else {
            $stream = $this->app->fs()->readStream($video->getAbstractedVideoPath());
            $body = $response->responseStream($stream, $video->file_size);
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
     * @throws PrintableException
     */
    public function outputKLEMAudio($url, $hash)
    {
        if ($this->validateKLEMAudioRequest($url, $hash, $error)) {
            /** @var AudioProxy $audioProxy */
            $audioProxy = $this->app->service('KL\EditorManager:AudioProxy');
            $audio = $audioProxy->getAudio($url);
            if (!$audio || !$audio->isValid()) {
                $audio = null;
            }
        } else {
            $audio = null;
        }

        if (!$audio) {
            if (!$error) {
                $error = self::ERROR_FAILED;
            }

            /** @var \KL\EditorManager\Repository\AudioProxy $proxyRepo */
            $proxyRepo = $this->app->repository('KL\EditorManager:AudioProxy');
            $audio = $proxyRepo->getPlaceholderAudio();
        }

        if (!$error) {
            $proxyRepo = $this->app->repository('KL\EditorManager:AudioProxy');

            $proxyRepo->logAudioView($audio);
            if ($this->referrer && $this->app->options()->klEMVideoAudioProxyReferrer['enabled']) {
                $proxyRepo->logAudioReferrer($audio, $this->referrer);
            }
        }

        $response = $this->app->response();
        $this->applyKLEMAudioResponseHeaders($response, $audio, $error);

        if ($audio->isPlaceholder()) {
            $body = $response->responseFile($audio->getPlaceholderPath());
        } else {
            $stream = $this->app->fs()->readStream($audio->getAbstractedAudioPath());
            $body = $response->responseStream($stream, $audio->file_size);
        }

        $response->body($body);

        return $response;
    }

    /**
     * @param Response $response
     * @param \KL\EditorManager\Entity\VideoProxy $video
     * @param $error
     */
    public function applyKLEMVideoResponseHeaders(
        Response $response,
        \KL\EditorManager\Entity\VideoProxy $video,
        $error
    ) {
        if (!$error) {
            $response->header('Cache-Control', 'public');

            $expectedETag = $video->getETagValue();
            if ($expectedETag) {
                $response->header('ETag', '"' . $expectedETag . '"', true);

                if ($this->eTag && $this->eTag === "\"$expectedETag\"") {
                    $response->httpCode(304);
                    $response->removeHeader('Last-Modified');
                    return;
                }
            }
        }

        $videoTypes = [
            'video/mp4',
            'video/webm'
        ];

        if (in_array($video->mime_type, $videoTypes)) {
            $response->contentType($video->mime_type);
            $response->setDownloadFileName($video->file_name, true);
        } else {
            $response->contentType('application/octet-stream');
            $response->setDownloadFileName($video->file_name);
        }

        $response->header('X-Content-Type-Options', 'nosniff');

        if ($error) {
            $response->header('X-Proxy-Error', $error);
        }
    }

    /**
     * @param Response $response
     * @param \KL\EditorManager\Entity\AudioProxy $audio
     * @param $error
     */
    public function applyKLEMAudioResponseHeaders(
        Response $response,
        \KL\EditorManager\Entity\AudioProxy $audio,
        $error
    ) {
        if (!$error) {
            $response->header('Cache-Control', 'public');

            $expectedETag = $audio->getETagValue();
            if ($expectedETag) {
                $response->header('ETag', '"' . $expectedETag . '"', true);

                if ($this->eTag && $this->eTag === "\"$expectedETag\"") {
                    $response->httpCode(304);
                    $response->removeHeader('Last-Modified');
                    return;
                }
            }
        }

        $videoTypes = [
            'audio/mp4',
            'audio/mp3'
        ];

        if (in_array($audio->mime_type, $videoTypes)) {
            $response->contentType($audio->mime_type);
            $response->setDownloadFileName($audio->file_name, true);
        } else {
            $response->contentType('application/octet-stream');
            $response->setDownloadFileName($audio->file_name);
        }

        $response->header('X-Content-Type-Options', 'nosniff');

        if ($error) {
            $response->header('X-Proxy-Error', $error);
        }
    }

    /**
     * @param $url
     * @param $hash
     * @param null $error
     * @return bool
     */
    public function validateKLEMVideoRequest($url, $hash, &$error = null)
    {
        if (!$this->linker->isTypeEnabled('video')) {
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
    public function validateKLEMAudioRequest($url, $hash, &$error = null)
    {
        if (!$this->linker->isTypeEnabled('video')) {
            $error = self::ERROR_DISABLED;
            return false;
        }

        if (!$this->validateProxyRequestGeneric($url, $hash, $error)) {
            return false;
        }

        return true;
    }
}