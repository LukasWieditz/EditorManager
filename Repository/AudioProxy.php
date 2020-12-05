<?php

/*!
 * KL/EditorManager/Repository/AudioProxy.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use XF;

/**
 * Class AudioProxy
 * @package KL\EditorManager\Repository
 */
class AudioProxy extends AbstractProxy
{
    /**
     * @return string
     */
    protected function getEntityClass(): string
    {
        return 'KL\EditorManager:AudioProxy';
    }

    /**
     * @return string
     */
    protected function getReferrerEntityClass(): string
    {
        return 'KL\EditorManager:AudioProxyReferrer';
    }

    /**
     * @return \KL\EditorManager\Entity\AudioProxy
     */
    public function getPlaceholder() : \KL\EditorManager\Entity\AbstractProxy
    {
        // TODO: ability to customize path
        $path = XF::getRootDirectory() . '/styles/editor-manager/missing-audio.mp3';

        /** @var \KL\EditorManager\Entity\AudioProxy $audio */
        $audio = $this->em->create('KL\EditorManager:AudioProxy');
        $audio->setAsPlaceholder($path, 'audio/mp4', 'missing-audio.mp3');

        return $audio;
    }

    /**
     * @return int
     */
    protected function getCacheTTL(): int
    {
        return \XF::options()->klEMAudioCacheTTL;
    }

    /**
     * @return int
     */
    protected function getProxyLogLength(): int
    {
        return \XF::options()->klEMVideoAudioProxyLogLength;
    }

    /**
     * @return array
     */
    protected function getReferrerOptions(): array
    {
        return \XF::options()->klEMVideoAudioProxyReferrer;
    }
}