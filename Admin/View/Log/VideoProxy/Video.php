<?php

/*!
 * KL/EditorManager/Admin/View/Log/VideoProxy/Audio.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\View\Log\VideoProxy;

use KL\EditorManager\Entity\VideoProxy;
use KL\EditorManager\XF\Proxy\Controller;
use League\Flysystem\FileNotFoundException;
use XF;
use XF\Http\ResponseFile;
use XF\Http\ResponseStream;
use XF\Mvc\View;

/**
 * Class Video
 * @package KL\EditorManager\Admin\View\Log\VideoProxy
 */
class Video extends View
{
    /**
     * @return ResponseFile|ResponseStream
     * @throws FileNotFoundException
     */
    public function renderRaw()
    {
        /** @var VideoProxy $video */
        $video = $this->params['video'];

        /** @var Controller $proxyController */
        $proxyController = XF::app()->proxy()->controller();
        $proxyController->applyKLEMVideoResponseHeaders($this->response, $video, null);

        if ($video->isPlaceholder()) {
            return $this->response->responseFile($video->getPlaceholderPath());
        } else {
            $resource = XF::fs()->readStream($video->getAbstractedVideoPath());
            return $this->response->responseStream($resource, $video->file_size);
        }
    }
}