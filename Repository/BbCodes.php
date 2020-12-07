<?php

/*!
 * KL/EditorManager/Repository/BbCodes.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use XF;
use XF\Mvc\Entity\Repository;

/**
 * Class BbCodes
 * @package KL\EditorManager\Repository
 */
class BbCodes extends Repository
{
    /**
     * @param string $bbCodeName
     * @return string
     */
    public function shortToFullName(string $bbCodeName): string
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
     * @param string $bbCodeName
     * @return array
     */
    public function shortToButtonDataName(string $bbCodeName): array
    {
        switch ($bbCodeName) {
            case 'sub':
                $tag = 'subscript';
                break;

            case 'sup':
                $tag = 'superscript';
                break;

            case 'bgcolor':
                $tag = 'backgroundColor';
                break;

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

        return is_array($tag) ? $tag : [$tag];
    }

    /**
     * @return array
     */
    public function getRelatedBbCodeOptions(): array
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
                    'klEMshowLinksToGuests',
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
                    'allowedCodeLanguages',
                    'CMTV_Code_defaultCodeLanguage',
                    'CMTV_Code_defaultInsertCodeLanguage',
                    'CMTV_Code_langOptions',
                    'CMTV_Code_lineHighlight_enabled',
                    'CMTV_Code_lineNumbers_enabled'
                ],
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