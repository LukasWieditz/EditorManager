<?php

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

    /**
     * @return array
     */
    public function getBasicBbCodes()
    {
        $BbCodes = [
            /* Basic Text Format */
            'bold' => [
                'icon' => 'bold',
                'iconType' => 'fa',
                'toolbarName' => 'bold',
                'name' => \XF::phrase('bold'),
                'order' => 10
            ],
            'italic' => [
                'icon' => 'italic',
                'iconType' => 'fa',
                'toolbarName' => 'italic',
                'name' => \XF::phrase('italic'),
                'order' => 20
            ],
            'underline' => [
                'icon' => 'underline',
                'iconType' => 'fa',
                'toolbarName' => 'underline',
                'name' => \XF::phrase('underline'),
                'order' => 30
            ],
            'strike' => [
                'icon' => 'strikethrough',
                'iconType' => 'fa',
                'toolbarName' => 'strikeThrough',
                'name' => \XF::phrase('strike_through'),
                'order' => 40
            ],

            /* Advanced Text Format */
            'subscript' => [
                'icon' => 'subscript',
                'iconType' => 'fa',
                'toolbarName' => 'subscript',
                'name' => \XF::phrase('kl_em_subscript'),
                'order' => 210
            ],
            'superscript' => [
                'icon' => 'superscript',
                'iconType' => 'fa',
                'toolbarName' => 'superscript',
                'name' => \XF::phrase('kl_em_superscript'),
                'order' => 220
            ],

            /* ParseHTML */
            'parsehtml' => [
                'icon' => 'code',
                'iconType' => 'fa',
                'toolbarName' => 'klEMParseHtml',
                'name' => \XF::phrase('kl_em_parse_html'),
                'order' => 350
            ],

            /* Hides */
            'hide' => [
                'icon' => 'eye-slash',
                'iconType' => 'fa',
                'toolbarName' => 'klEMHide',
                'name' => \XF::phrase('kl_em_hide'),
                'order' => 290
            ],
            'hideposts' => [
                'icon' => 'eye-slash',
                'iconType' => 'fa',
                'toolbarName' => 'klEMHidePosts',
                'name' => \XF::phrase('kl_em_hide_posts'),
                'order' => 291

            ],
            'hidethanks' => [
                'icon' => 'eye-slash',
                'iconType' => 'fa',
                'toolbarName' => 'klEMHideThanks',
                'name' => \XF::phrase('kl_em_hide_thanks'),
                'order' => 292
            ],
            'hidereply' => [
                'icon' => 'eye-slash',
                'iconType' => 'fa',
                'toolbarName' => 'klEMHideReply',
                'name' => \XF::phrase('kl_em_hide_reply'),
                'order' => 293
            ],
            'hidereplythanks' => [
                'icon' => 'eye-slash',
                'iconType' => 'fa',
                'toolbarName' => 'klEMHideReplyThanks',
                'name' => \XF::phrase('kl_em_hide_reply_thanks'),
                'order' => 294
            ],

            /* Inserts */
            'media' => [
                'icon' => 'video-camera',
                'iconType' => 'fa',
                'toolbarName' => 'xfMedia',
                'name' => \XF::phrase('media'),
                'order' => 230
            ],
            'quote' => [
                'icon' => 'quote-right',
                'iconType' => 'fa',
                'toolbarName' => 'xfQuote',
                'name' => \XF::phrase('quote'),
                'order' => 240
            ],
            'spoiler' => [
                'icon' => 'flag',
                'iconType' => 'fa',
                'toolbarName' => 'xfSpoiler',
                'name' => \XF::phrase('spoiler'),
                'order' => 250
            ],
            'ispoiler' => [
                'icon' => 'eye-slash',
                'iconType' => 'fa',
                'toolbarName' => 'xfKLEMiSpoiler',
                'name' => \XF::phrase('kl_em_inline_spoiler'),
                'order' => 260
            ],
            'code' => [
                'icon' => 'code',
                'iconType' => 'fa',
                'toolbarName' => 'xfCode',
                'name' => \XF::phrase('code'),
                'order' => 270
            ],
            'icode' => [
                'icon' => 'terminal',
                'iconType' => 'fa',
                'toolbarName' => 'xfInlineCode',
                'name' => \XF::phrase('inline_code'),
                'order' => 280
            ],

            /* Misc */
            'smilie' => [
                'icon' => 'smile-o',
                'iconType' => 'fa',
                'toolbarName' => 'xfSmilie',
                'name' => \XF::phrase('smilies'),
                'order' => 330
            ],
            'removeFormatting' => [
                'icon' => 'eraser',
                'iconType' => 'fa',
                'toolbarName' => 'clearFormatting',
                'name' => \XF::phrase('remove_formatting'),
                'order' => 570
            ],
            'undo' => [
                'icon' => 'undo',
                'iconType' => 'fa',
                'toolbarName' => 'undo',
                'name' => \XF::phrase('undo'),
                'order' => 550
            ],
            'redo' => [
                'icon' => 'repeat',
                'iconType' => 'fa',
                'toolbarName' => 'redo',
                'name' => \XF::phrase('redo'),
                'order' => 555
            ],
            'specialCharacters' => [
                'toolbarName' => 'specialCharacters',
                'name' => \XF::phrase('kl_em_special_characters'),
                'iconType' => 'text',
                'icon' => '&#937;',
                'order' => 440
            ],
            'fullscreen' => [
                'toolbarName' => 'fullscreen',
                'name' => \XF::phrase('kl_em_fullscreen'),
                'iconType' => 'fa',
                'icon' => 'expand',
                'order' => 560
            ],
            'audio' => [
                'toolbarName' => 'audio',
                'name' => \XF::phrase('kl_em_audio'),
                'iconType' => 'fa',
                'icon' => 'volume-up',
                'order' => 290
            ],
            'video' => [
                'toolbarName' => 'video',
                'name' => \XF::phrase('kl_em_video'),
                'iconType' => 'fa',
                'icon' => 'play',
                'order' => 290
            ]
        ];

        $addOns = array_keys($this->app()->get('addon.cache'));

        if (in_array('ThemeHouse/Giphy', $addOns)) {
            $BbCodes['thGiphy'] = [
                'icon' => '/styles/themehouse/giphy/giphy.png',
                'iconType' => 'img',
                'toolbarName' => 'thGiphy',
                'name' => \XF::phrase('th_insert_giphy_giphy'),
                'order' => 1000
            ];
        }

        if (in_array('Math', $addOns)) {
            $BbCodes['Math_insertMath'] = [
                'icon' => ' MathAddonIcon-insertMathIcon',
                'iconType' => 'fa',
                'toolbarName' => 'Math_insertMath',
                'name' => \XF::phrase('Math_insert'),
                'order' => 1000
            ];
        }

        $options = $this->app()->options()->klEMGeneralOptions;

        if ($options['disable_smilies']) {
            unset($BbCodes['smilie']);
        }
        if (!$options['enable_fullscreen']) {
            unset($BbCodes['fullscreen']);
        }
        if (!$options['special_chars']) {
            unset($BbCodes['specialCharacters']);
        }

        return $BbCodes;
    }

    /**
     * @return array
     */
    public function getExtendedBbCodes()
    {
        $BbCodes = $this->getBasicBbCodes();

        $BbCodes = array_merge($BbCodes, [
            'color' => [
                'icon' => 'tint',
                'iconType' => 'fa',
                'toolbarName' => 'color',
                'name' => \XF::phrase('color'),
                'order' => 110
            ],
            'font' => [
                'icon' => 'font',
                'iconType' => 'fa',
                'toolbarName' => 'fontFamily',
                'name' => \XF::phrase('font_family'),
                'order' => 120
            ],
            'gfont' => [
                'icon' => 'google',
                'iconType' => 'fa',
                'toolbarName' => 'gFontFamily',
                'name' => \XF::phrase('kl_em_gfont'),
                'order' => 130
            ],
            'size' => [
                'icon' => 'text-height',
                'iconType' => 'fa',
                'toolbarName' => 'fontSize',
                'name' => \XF::phrase('font_size'),
                'order' => 140
            ],

            'url' => [
                'icon' => 'link',
                'iconType' => 'fa',
                'toolbarName' => 'insertLink',
                'name' => \XF::phrase('insert_link'),
                'order' => 310
            ],
            'unlinkAll' => [
                'toolbarName' => 'klUnlinkAll',
                'name' => \XF::phrase('kl_em_unlink_all'),
                'iconType' => 'fa',
                'icon' => 'unlink',
                'order' => 315
            ],
            'img' => [
                'icon' => 'picture-o',
                'iconType' => 'fa',
                'toolbarName' => 'insertImage',
                'name' => \XF::phrase('insert_image'),
                'order' => 320
            ],

            'align' => [
                'icon' => 'align-left',
                'iconType' => 'fa',
                'toolbarName' => 'align',
                'name' => \XF::phrase('alignment'),
                'order' => 410
            ],
            'list' => [
                'icon' => 'list',
                'iconType' => 'fa',
                'toolbarName' => 'xfList',
                'name' => \XF::phrase('list'),
                'order' => 420
            ],
            'table' => [
                'icon' => 'table',
                'iconType' => 'fa',
                'toolbarName' => 'insertTable',
                'name' => \XF::phrase('kl_em_table'),
                'order' => 420
            ],

            'drafts' => [
                'icon' => 'floppy-o',
                'iconType' => 'fa',
                'toolbarName' => 'xfDraft',
                'name' => \XF::phrase('drafts'),
                'order' => 510
            ],

            'toggleBbcode' => [
                'icon' => 'cog',
                'iconType' => 'fa',
                'toolbarName' => 'xfBbCode',
                'name' => \XF::phrase('toggle_bb_code'),
                'order' => 520
            ],

            'separator' => [
                'toolbarName' => '|',
                'name' => \XF::phrase('kl_em_separator'),
                'iconType' => 'separator',
                'order' => 530
            ],
            'templates' => [
                'toolbarName' => 'klTemplates',
                'name' => \XF::phrase('kl_em_templates'),
                'iconType' => 'fa',
                'icon' => 'clipboard',
                'order' => 540
            ]
        ]);

        $addOns = array_keys($this->app()->get('addon.cache'));

        if (in_array('LiamW/PostMacros', $addOns)) {
            $BbCodes['lwSelectMacro'] = [
                'icon' => 'flash',
                'iconType' => 'fa',
                'toolbarName' => 'lwSelectMacro',
                'name' => \XF::phrase('liamw_postmacros'),
                'order' => 1000
            ];
        }

        usort($BbCodes, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $BbCodes;
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
                    'klEMImageDimensions',
                    'klEMImageAlignment',
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
                'sort' => ['imageLinkProxy'],
                'options' => [
                    'imageLinkProxy',
                    'imageLinkProxyKey',
                    'imageLinkProxyReferrer',
                    'imageLinkProxyLogLength'
                ]
            ]
        ];
    }
}