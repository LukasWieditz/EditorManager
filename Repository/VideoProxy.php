<?php

/*!
 * KL/EditorManager/Repository/VideoProxy.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use XF;

/**
 * Class VideoProxy
 * @package KL\EditorManager\Repository
 */
class VideoProxy extends AbstractProxy
{
    /**
     * @return string
     */
    protected function getEntityClass(): string
    {
        return 'KL\EditorManager:VideoProxy';
    }

    /**
     * @return string
     */
    protected function getReferrerEntityClass(): string
    {
        return 'KL\EditorManager:VideoProxyReferrer';
    }

    /**
     * @return \KL\EditorManager\Entity\VideoProxy
     */
    public function getPlaceholder() : \KL\EditorManager\Entity\AbstractProxy
    {
        // TODO: ability to customize path
        $path = XF::getRootDirectory() . '/styles/editor-manager/missing-video.mp4';

        /** @var \KL\EditorManager\Entity\VideoProxy $video */
        $video = $this->em->create('KL\EditorManager:VideoProxy');
        $video->setAsPlaceholder($path, 'video/mp4', 'missing-video.mp4');

        return $video;
    }

    /**
     * @return int
     */
    protected function getCacheTTL(): int
    {
        return \XF::options()->klEMVideoCacheTTL;
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