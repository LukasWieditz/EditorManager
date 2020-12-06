<?php

/*!
 * KL/EditorManager/Service/AudioProxy.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Service;

use XF;

/**
 * Class AudioProxy
 * @package KL\EditorManager\Service
 */
class AudioProxy extends AbstractProxy
{
    /**
     * @return string
     */
    protected function getProxyClass(): string
    {
        return 'KL\EditorManager:AudioProxy';
    }

    /**
     * @return int
     */
    protected function getProxyMaxSize(): int
    {
        return XF::options()->klEMAudioProxyMaxSize;
    }

    /**
     * @return string[]
     */
    protected function getValidExtensionMap(): array
    {
        return [
            'audio/webm' => ['webm'],
            'audio/mp4' => ['mp4'],
            'audio/mpeg' => ['mp3']
        ];
    }

    /**
     * @return string
     */
    protected function getType(): string
    {
        return 'audio';
    }
}