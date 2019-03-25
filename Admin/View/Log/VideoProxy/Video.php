<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\View\Log\VideoProxy;

use XF\Mvc\View;

class Video extends View
{
    /**
     * @return \XF\Http\ResponseFile|\XF\Http\ResponseStream
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function renderRaw()
    {
        /** @var \KL\EditorManager\Entity\VideoProxy $video */
        $video = $this->params['video'];

        /** @var \KL\EditorManager\XF\Proxy\Controller $proxyController */
        $proxyController = \XF::app()->proxy()->controller();
        $proxyController->applyKLEMVideoResponseHeaders($this->response, $video, null);

        if ($video->isPlaceholder()) {
            return $this->response->responseFile($video->getPlaceholderPath());
        } else {
            $resource = \XF::fs()->readStream($video->getAbstractedVideoPath());
            return $this->response->responseStream($resource, $video->file_size);
        }
    }
}