<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Listener;

use KL\EditorManager\Repository\BbCodes;
use XF\Data\Editor;

class EditorButtonData
{
    /**
     * @param array $buttons
     * @param Editor $editorData
     */
    public static function extend(array &$buttons, Editor $editorData)
    {
        $buttons += [
            'fullscreen' => [
                'fa' => 'fa-expand',
                'title' => \XF::phrase('kl_em_fullscreen')
            ],
            'specialCharacters' => [
                'fa' => 'fa-omega',
                'title' => \XF::phrase('kl_em_special_characters'),
                'type' => 'dropdown'
            ],
            'video' => [
                'fa' => 'fa-video',
                'title' => \XF::phrase('kl_em_video')
            ],
            'audio' => [
                'fa' => 'fa-music',
                'title' => \XF::phrase('kl_em_audio')
            ],
            'klUnlinkAll' => [
                'fa' => 'fa-unlink',
                'title' => \XF::phrase('kl_em_unlink_all')
            ],
            'klEMHide' => [
                'fa' => 'fa-eye-slash',
                'title' => \XF::phrase('kl_em_hide')
            ],
            'klEMHidePosts' => [
                'fa' => 'fa-minus-circle',
                'title' => \XF::phrase('kl_em_hide_posts')
            ],
            'klEMHideThanks' => [
                'fa' => 'fa-minus-hexagon',
                'title' => \XF::phrase('kl_em_hide_thanks')
            ],
            'klEMHideReply' => [
                'fa' => 'fa-minus-octagon',
                'title' => \XF::phrase('kl_em_hide_reply')
            ],
            'klEMHideReplyThanks' => [
                'fa' => 'fa-minus-square',
                'title' => \XF::phrase('kl_em_hide_reply_thanks')
            ],
            'klEMHideGroup' => [
                'fa' => 'fa-folder-minus',
                'title' => \XF::phrase('kl_em_hide_group')
            ],
            'klEMParseHtml' => [
                'fa' => 'fa-code',
                'title' => \XF::phrase('kl_em_parse_html')
            ],
            'klTemplates' => [
                'fa' => 'fa-paste',
                'title' => \XF::phrase('kl_em_templates'),
                'type' => 'dropdown'
            ],
            'gFontFamily' => [
                'fa' => 'fab fa-google',
                'title' => \XF::phrase('kl_em_google_font')
            ],
            'subscript' => [
                'fa' => 'far fa-subscript',
                'title' => \XF::phrase('kl_em_subscript')
            ],
            'superscript' => [
                'fa' => 'far fa-superscript',
                'title' => \XF::phrase('kl_em_superscript')
            ]
        ];

        $disabledBbCodes = \XF::options()->klEMEnabledBBCodes;
        /** @var BbCodes $repo */
        $repo = \XF::repository('KL\EditorManager:BbCodes');

        foreach ($disabledBbCodes as $name => $disabledBbCode) {
            if ($disabledBbCode) {
                continue;
            }

            $toolbarName = $repo->shortToButtonDataName($name);

            if (is_array($toolbarName)) {
                foreach ($toolbarName as $tN) {
                    unset($buttons[$tN]);
                }
            } else {
                unset($buttons[$toolbarName]);
            }
        }
    }
}