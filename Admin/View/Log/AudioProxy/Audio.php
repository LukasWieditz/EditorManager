<?php

/*!
 * KL/EditorManager/Admin/View/Log/AudioProxy/Audio.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\View\Log\AudioProxy;

use KL\EditorManager\Entity\AudioProxy;
use KL\EditorManager\XF\Proxy\Controller;
use League\Flysystem\FileNotFoundException;
use XF;
use XF\Http\ResponseFile;
use XF\Http\ResponseStream;
use XF\Mvc\View;

/**
 * Class Audio
 * @package KL\EditorManager\Admin\View\Log\AudioProxy
 */
class Audio extends View
{
    /**
     * @return ResponseFile|ResponseStream
     * @throws FileNotFoundException
     */
    public function renderRaw()
    {
        /** @var AudioProxy $audio */
        $audio = $this->params['audio'];

        /** @var Controller $proxyController */
        $proxyController = XF::app()->proxy()->controller();
        $proxyController->applyKLEMAudioResponseHeaders($this->response, $audio, null);

        if ($audio->isPlaceholder()) {
            return $this->response->responseFile($audio->getPlaceholderPath());
        } else {
            $resource = XF::fs()->readStream($audio->getAbstractedFilePath());
            return $this->response->responseStream($resource, $audio->file_size);
        }
    }
}