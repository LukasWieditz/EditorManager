<?php

/*!
 * KL/EditorManager/Entity/VideoProxy.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use XF;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null video_id
 * @property string url
 * @property string url_hash
 * @property int file_size
 * @property string file_name
 * @property string mime_type
 * @property int fetch_date
 * @property int first_request_date
 * @property int last_request_date
 * @property int views
 * @property bool pruned
 * @property int is_processing
 * @property int failed_date
 * @property int fail_count
 *
 * RELATIONS
 * @property VideoProxyReferrer[] Referrers
 */
class VideoProxy extends AbstractProxy
{
    /**
     * @return string
     */
    public function getAbstractedFilePath(): string
    {
        return sprintf('internal-data://video_cache/%d/%d-%s.data',
            floor($this->video_id / 1000),
            $this->video_id,
            $this->url_hash
        );
    }

    public function getPlaceholderPath(): string
    {
        return XF::getRootDirectory() . '/styles/editor-manager/missing-video.mp4';
    }

    /**
     * @return bool
     */
    public function isRefreshRequired(): bool
    {
        if ($this->placeholderPath) {
            return false;
        }

        $filePath = $this->getAbstractedFilePath();
        $fs = $this->app()->fs();

        if ($this->is_processing && XF::$time - $this->is_processing < 5) {
            if ($fs->has($filePath)) {
                return false;
            }

            $maxSleep = 5 - (XF::$time - $this->is_processing);
            for ($i = 0; $i < $maxSleep; $i++) {
                if ($fs->has($filePath)) {
                    return false;
                }
            }
        }

        if ($this->failed_date && $this->fail_count) {
            return $this->isFailureRefreshRequired();
        }

        if ($this->pruned) {
            return true;
        }

        $ttl = $this->app()->options()->klEMVideoCacheTTL;
        if ($ttl && $this->fetch_date < XF::$time - $ttl * 86400) {
            return true;
        }

        if (!$fs->has($filePath)) {
            return true;
        }

        $refresh = $this->app()->options()->kLEMVideoCacheRefresh;
        if ($refresh && !$this->fail_count && $this->fetch_date < XF::$time - $refresh * 86400) {
            return true;
        }

        return false;
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_kl_em_video_proxy';
        $structure->shortName = 'KL\EditorManager:VideoProxy';
        $structure->primaryKey = 'video_id';
        $structure->columns = [
            'video_id' => ['type' => self::UINT, 'nullable' => true, 'autoIncrement' => true],
            'url' => ['type' => self::STR, 'required' => true],
            'url_hash' => ['type' => self::STR, 'maxLength' => 32, 'required' => true],
            'file_size' => ['type' => self::UINT, 'default' => 0],
            'file_name' => ['type' => self::STR, 'maxLength' => 250, 'default' => ''],
            'mime_type' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'fetch_date' => ['type' => self::UINT, 'default' => 0],
            'first_request_date' => ['type' => self::UINT, 'default' => XF::$time],
            'last_request_date' => ['type' => self::UINT, 'default' => XF::$time],
            'views' => ['type' => self::UINT, 'default' => 0],
            'pruned' => ['type' => self::BOOL, 'default' => false],
            'is_processing' => ['type' => self::UINT, 'default' => 0],
            'failed_date' => ['type' => self::UINT, 'default' => 0],
            'fail_count' => ['type' => self::UINT, 'default' => 0],
        ];
        $structure->getters = [];
        $structure->relations = [
            'Referrers' => [
                'entity' => 'KL\EditorManager:VideoProxyReferrer',
                'type' => self::TO_MANY,
                'conditions' => 'video_id',
                'order' => ['last_date', 'DESC']
            ]
        ];

        return $structure;
    }
}