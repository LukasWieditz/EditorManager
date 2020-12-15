<?php

/*!
 * KL/EditorManager/Entity/AbstractProxy.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use InvalidArgumentException;
use KL\EditorManager\Repository\AbstractProxy as AbstractProxyRepo;
use LogicException;
use XF;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\PrintableException;
use XF\Util\File;

/**
 * Class AbstractProxy
 * @package KL\EditorManager\Entity
 */
abstract class AbstractProxy extends Entity
{
    /**
     * @var bool
     */
    protected $placeholderPath;

    /**
     * @param string $fileName
     * @return bool
     */
    protected function verifyFileName(string &$fileName): bool
    {
        if (!preg_match('/./u', $fileName)) {
            $fileName = preg_replace('/[\x80-\xFF]/', '?', $fileName);
        }

        $fileName = XF::cleanString($fileName);

        // ensure the filename fits -- if it's too long, take off from the beginning to keep extension
        $length = utf8_strlen($fileName);
        if ($length > 250) {
            $fileName = utf8_substr($fileName, $length - 250);
        }

        return true;
    }

    /**
     * @return string
     */
    abstract public function getAbstractedFilePath(): string;

    /**
     * @return bool
     */
    public function isPlaceholder(): bool
    {
        return (bool) $this->placeholderPath;
    }

    /**
     * @return string
     */
    abstract public function getPlaceholderPath(): string;

    /**
     * @param $url
     * @return bool
     */
    protected function verifyUrl(&$url): bool
    {
        $url = $this->getProxyRepo()
            ->cleanUrlForFetch($url);

        if (!preg_match('#^https?://#i', $url)) {
            $this->error('Developer: invalid URL', 'url');
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->pruned) {
            return false;
        }

        return $this->app()->fs()->has($this->getAbstractedFilePath());
    }

    /**
     * @return bool
     */
    public function isFailureRefreshRequired(): bool
    {
        if (!$this->failed_date || !$this->fail_count) {
            return false;
        }

        switch ($this->fail_count) {
            case 1:
                $delay = 60;
                break; // 1 minute
            case 2:
                $delay = 5 * 60;
                break; // 5 minutes
            case 3:
                $delay = 30 * 60;
                break; // 30 minutes
            case 4:
                $delay = 3600;
                break; // 1 hour
            case 5:
                $delay = 6 * 3600;
                break; // 6 hours

            default:
                $delay = ($this->fail_count - 5) * 86400; // 1, 2, 3... days
        }

        return XF::$time >= ($this->failed_date + $delay);
    }

    /**
     *
     */
    protected function _preSave(): void
    {
        if ($this->placeholderPath) {
            throw new LogicException("Cannot save placeholder file");
        }

        if ($this->isChanged('url')) {
            $this->url_hash = md5($this->url);
        }
    }

    /**
     * @return bool
     * @throws PrintableException
     */
    public function prune(): bool
    {
        if ($this->placeholderPath) {
            return false;
        }

        $this->pruned = true;
        $this->save();

        File::deleteFromAbstractedPath($this->getAbstractedFilePath());

        return true;
    }

    /**
     * @return null|string
     */
    public function getETagValue()
    {
        if ($this->isPlaceholder() || $this->fail_count || $this->pruned) {
            return null;
        }

        return sha1($this->url . $this->fetch_date);
    }

    /**
     * @param $filePath
     * @param $mimeType
     * @param null $fileName
     */
    public function setAsPlaceholder($filePath, $mimeType, $fileName = null): void
    {
        if ($this->placeholderPath) {
            throw new InvalidArgumentException("Once a resource is marked as a placeholder, it cannot be changed");
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException("Placeholder path '$filePath' doesn't exist or isn't readable");
        }

        $this->placeholderPath = $filePath;
        $this->file_name = $fileName ?: basename($filePath);
        $this->mime_type = $mimeType;
        $this->file_size = filesize($filePath);

        $this->setReadOnly(true);
    }

    /**
     * @return bool
     */
    abstract public function isRefreshRequired(): bool;

    /**
     * @return AbstractProxyRepo|Repository
     */
    protected function getProxyRepo(): AbstractProxyRepo
    {
        return $this->repository($this->structure()->shortName);
    }
}