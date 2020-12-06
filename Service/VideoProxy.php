<?php

/*!
 * KL/EditorManager/Service/VideoProxy.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Service;

use XF;

/**
 * Class VideoProxy
 * @package KL\EditorManager\Service
 */
class VideoProxy extends AbstractProxy
{
    /**
     * @return string
     */
    protected function getProxyClass(): string
    {
        return 'KL\EditorManager:VideoProxy';
    }

    /**
     * @return int
     */
    protected function getProxyMaxSize(): int
    {
        return XF::options()->klEMVideoProxyMaxSize;
    }

    /**
     * @return string[]
     */
    protected function getValidExtensionMap(): array
    {
        return [
            'video/webm' => ['webm'],
            'video/mp4' => ['mp4']
        ];
    }

    /**
     * @return string
     */
    protected function getType(): string
    {
        return 'video';
    }
}