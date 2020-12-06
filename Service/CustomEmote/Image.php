<?php

/*!
 * KL/EditorManager/Service/CustomEmote/Image.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Service\CustomEmote;

use InvalidArgumentException;
use KL\EditorManager\Entity\CustomEmote;
use LogicException;
use RuntimeException;
use XF;
use XF\App;
use XF\Http\Upload;
use XF\PrintableException;
use XF\Repository\Ip;
use XF\Service\AbstractService;
use XF\Util\File;

/**
 * Class Image
 * @package KL\EditorManager\Service\CustomEmote
 */
class Image extends AbstractService
{
    /**
     * @var CustomEmote
     */
    protected $emote;

    /**
     * @var bool
     */
    protected $logIp = true;

    /**
     * @var
     */
    protected $fileName;

    /**
     * @var
     */
    protected $width;

    /**
     * @var
     */
    protected $height;

    /**
     * @var
     */
    protected $type;

    /**
     * @var null
     */
    protected $error = null;

    /**
     * @var array
     */
    protected $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];

    /**
     * Image constructor.
     * @param App $app
     * @param CustomEmote $emote
     */
    public function __construct(App $app, CustomEmote $emote)
    {
        parent::__construct($app);
        $this->emote = $emote;

        if ($this->emote->canUseGif()) {
            $this->allowedTypes[] = IMAGETYPE_GIF;
        }
    }

    /**
     * @return CustomEmote
     */
    public function getEmote(): CustomEmote
    {
        return $this->emote;
    }

    /**
     * @param $logIp
     */
    public function logIp($logIp): void
    {
        $this->logIp = $logIp;
    }

    /**
     * @return null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param $fileName
     * @return bool
     */
    public function setImage($fileName): bool
    {
        if (!$this->validateImage($fileName, $error)) {
            $this->error = $error;
            $this->fileName = null;
            return false;
        }

        $this->fileName = $fileName;
        return true;
    }

    /**
     * @param Upload $upload
     * @return bool
     */
    public function setImageFromUpload(Upload $upload): bool
    {
        $upload->requireImage();

        if (!$upload->isValid($errors)) {
            $this->error = reset($errors);
            return false;
        }

        return $this->setImage($upload->getTempFile());
    }

    /**
     * @param $fileName
     * @param null $error
     * @return bool
     */
    public function validateImage($fileName, &$error = null): bool
    {
        $error = null;

        if (!file_exists($fileName)) {
            throw new InvalidArgumentException("Invalid file '$fileName' passed to image service");
        }
        if (!is_readable($fileName)) {
            throw new InvalidArgumentException("'$fileName' passed to image service is not readable");
        }

        $imageInfo = filesize($fileName) ? getimagesize($fileName) : false;
        if (!$imageInfo) {
            $error = XF::phrase('provided_file_is_not_valid_image');
            return false;
        }

        $type = $imageInfo[2];
        if (!in_array($type, $this->allowedTypes)) {
            $error = XF::phrase('provided_file_is_not_valid_image');
            return false;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if (!$this->app->imageManager()->canResize($width, $height)) {
            $error = XF::phrase('uploaded_image_is_too_big');
            return false;
        }

        $this->width = $width;
        $this->height = $height;
        $this->type = $type;
        $this->emote->extension = explode('/', $imageInfo['mime'])[1];

        return true;
    }

    /**
     * @return bool
     * @throws PrintableException
     */
    public function updateImage(): bool
    {
        if (!$this->fileName) {
            throw new LogicException("No source file for image set");
        }

        $imageManager = $this->app->imageManager();
        $targetSize = 256;
        $outputFile = null;

        if ($this->width != $targetSize || $this->height != $targetSize) {
            $image = $imageManager->imageFromFile($this->fileName);
            if (!$image) {
                return false;
            }

            $image->resizeAndCrop($targetSize);

            $newTempFile = File::getTempFile();
            if ($newTempFile && $image->save($newTempFile)) {
                $outputFile = $newTempFile;
            }
        } else {
            $outputFile = $this->fileName;
        }

        if (!$outputFile) {
            throw new RuntimeException("Failed to save image to temporary file; check internal_data/data permissions");
        }

        $dataFile = $this->emote->getAbstractedPath();
        File::copyFileToAbstractedPath($outputFile, $dataFile);

        $this->emote->image_date = XF::$time;
        $this->emote->save();

        if ($this->logIp) {
            $ip = ($this->logIp === true ? $this->app->request()->getIp() : $this->logIp);
            $this->writeIpLog('update', $ip);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function deleteImageForEmoteDelete(): bool
    {
        $this->deleteImageFiles();
        return true;
    }

    /**
     *
     */
    protected function deleteImageFiles(): void
    {
        if ($this->emote->image_date) {
            File::deleteFromAbstractedPath($this->emote->getAbstractedPath());
        }
    }

    /**
     * @param $action
     * @param $ip
     */
    protected function writeIpLog($action, $ip): void
    {
        $emote = $this->emote;

        /** @var Ip $ipRepo */
        $ipRepo = $this->repository('XF:Ip');
        $ipRepo->logIp(XF::visitor()->user_id, $ip, 'kl_em_custom_emote', $emote->emote_id, 'image_' . $action);
    }
}