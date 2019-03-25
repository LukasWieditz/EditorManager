<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use XF\Mvc\Entity\Repository;

/**
 * Class BbCodes
 * @package KL\EditorManager\Repository
 */
class BbCodes extends Repository
{
    /**
     * @return array
     */
    public function getValidControllersForHide()
    {
        $value = \XF::app()->options()->klEMHideControllers;
        $controllers = explode("\n", $value);
        return $controllers;
    }

    protected $bbCodeSettings = false;

    /**
     * @return \XF\Mvc\Entity\ArrayCollection
     */
    public function getBbCodeSettings()
    {
        if (!$this->bbCodeSettings) {
            $this->bbCodeSettings = \XF::finder('KL\EditorManager:BbCode')->fetch();
        }

        return $this->bbCodeSettings;
    }

    /**
     * @param $bbCodeName
     * @return string
     */
    public function shortToFullName($bbCodeName)
    {
        switch ($bbCodeName) {
            case 'b':
                $tagName = 'bold';
                break;

            case 'i':
                $tagName = 'italic';
                break;

            case 's':
                $tagName = 'strike';
                break;

            case 'u':
                $tagName = 'underline';
                break;

            case 'img':
                $tagName = 'image';
                break;

            case 'hidereply':
            case 'hidethanks':
            case 'hidereplythanks':
            case 'hideposts':
                $tagName = 'hide';
                break;

            case 'tr':
            case 'td':
            case 'th':
                $tagName = 'table';
                break;

            case 'left':
            case 'right':
            case 'center':
            case 'justify':
                $tagName = 'align';
                break;

            case 'indent':
                $tagName = 'list';
                break;

            default:
                $tagName = $bbCodeName;
                break;
        }

        return $tagName;
    }

    /**
     * @param $bbCodeName
     * @return array|string
     */
    public function shortToButtonDataName($bbCodeName)
    {
        switch ($bbCodeName) {
            case 'parsehtml':
                $tag = 'klEMParseHtml';
                break;

            case 'hide':
                $tag = ['klEMHide', 'klEMHidePosts', 'klEMHideThanks', 'klEMHideReply', 'klEMHideReplyThanks'];
                break;

            case 'table':
                $tag = 'insertTable';
                break;

            case 'list':
                $tag = ['formatUL', 'formatOL', 'outdent', 'indent'];
                break;

            case 'icode':
                $tag = 'xfInlineCode';
                break;

            case 'code':
                $tag = 'xfCode';
                break;

            case 'ispoiler':
                $tag = 'xfInlineSpoiler';
                break;

            case 'spoiler':
                $tag = 'xfSpoiler';
                break;

            case 'quote':
                $tag = 'xfQuote';
                break;

            case 'media':
                $tag = 'xfMedia';
                break;

            case 'img':
                $tag = 'insertImage';
                break;

            case 'url':
                $tag = ['insertLink', 'klUnlinkAll'];
                break;

            case 'size':
                $tag = 'fontSize';
                break;

            case 'font':
                $tag = ['fontFamily', 'gFontFamily'];
                break;

            case 'strike':
                $tag = 'strikeThrough';
                break;

            case 'attach':
                $tag = 'insertVideo';
                break;

            default:
                $tag = $bbCodeName;
        }

        return $tag;
    }

    /**
     * @return array
     */
    public function getRelatedBbCodeOptions()
    {
        return [
            'font' => [
                'sort' => ['klEM'],
                'options' => ['klEMExternalFontPolling', 'klEMGoogleApiKey']
            ],
            'size' => [
                'sort' => ['klEM'],
                'options' => ['klEMFontSizes', 'klEMMinFontSize', 'klEMMaxFontSize']
            ],
            'hide' => [
                'sort' => ['klEM'],
                'options' => ['klEMDefaultHide', 'klEMHideControllers']
            ],
            'color' => [
                'sort' => ['klEM'],
                'options' => ['klEMColors', 'klEMColorStep', 'klEMHexColor']
            ],
            'bgcolor' => [
                'sort' => ['klEM'],
                'options' => ['klEMColors', 'klEMColorStep', 'klEMHexColor']
            ],
            'video' => [
                'sort' => ['klEM'],
                'options' => [
                    'klEMProxy',
                    'klEMVideoCacheTTL',
                    'kLEMVideoCacheRefresh',
                    'klEMVideoProxyMaxSize',
                    'klEMVideoAudioProxyReferrer',
                    'klEMVideoAudioProxyLogLength'
                ]
            ],
            'audio' => [
                'sort' => ['klEM'],
                'options' => [
                    'klEMProxy',
                    'klEMAudioCacheTTL',
                    'klEMAudioCacheRefresh',
                    'klEMAudioProxyMaxSize',
                    'klEMVideoAudioProxyReferrer',
                    'klEMVideoAudioProxyLogLength'
                ]
            ],
            'image' => [
                'sort' => ['klEM', 'imageLinkProxy'],
                'options' => [
                    'imageLinkProxy',
                    'imageLinkProxyKey',
                    'imageCacheTTL',
                    'imageCacheRefresh',
                    'imageProxyMaxSize',
                    'imageLinkProxyReferrer',
                    'imageLinkProxyLogLength'
                ]
            ],
            'url' => [
                'sort' => ['imageLinkProxy', 'messageOptions'],
                'options' => [
                    'imageLinkProxy',
                    'imageLinkProxyKey',
                    'imageLinkProxyReferrer',
                    'imageLinkProxyLogLength',
                    'urlToPageTitle',
                    'urlToRichPreview'
                ]
            ],
            'code' => [
                'sort' => ['messageOptions'],
                'options' => [
                    'allowedCodeLanguages'
                ]
            ],
            'media' => [
                'sort' => ['messageOptions'],
                'options' => [
                    'messageMaxMedia',
                    'autoEmbedMedia'
                ]
            ]
        ];
    }
}