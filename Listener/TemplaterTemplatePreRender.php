<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Listener;

use KL\EditorManager\Repository\BbCodes;
use KL\EditorManager\Repository\Font;
use XF\Template\Templater;

/**
 * Class TemplaterTemplatePreRender
 * @package KL\EditorManager\Listener
 */
class TemplaterTemplatePreRender
{
    /**
     * Push external fonts to page container head.
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function pageContainer(Templater $templater, &$type, &$template, array &$params)
    {
        /* Create Repository */
        $app = \XF::app();

        $options = $app->options();
        if (isset($options['klEMEnabledBBCodes']['font']) && $options['klEMEnabledBBCodes']['font']) {
            /** @var \KL\EditorManager\Repository\Font $repo */
            $repo = $app->em()->getRepository('KL\EditorManager:Font');

            $gfonts = [];
            $typekit = [];
            $webtype = [];
            $fonts = [];
            $serverFonts = [];
            $fontDirectory = $app->get('config')['externalDataPath'] . '/fonts';
            $fileTypes = [
                'ttf' => 'truetype',
                'woff' => 'woff',
                'eot' => 'embedded-opentype',
                'woff2' => 'woff2',
                'otf' => 'opentype'
            ];

            #\XF::dump($repo->getFontsCached());
            foreach ($repo->getFontsCached() as $font) {
                if ($font->type === 'web') {
                    switch ($font->extra_data['web_service']) {
                        case 'gfonts':
                            $gfonts[] = $font->extra_data['web_url'];
                            break;
                        case 'typekit':
                            $typekit[] = $font->extra_data['web_url'];
                            break;
                        case 'webtype':
                            $webtype[] = $font->extra_data['web_url'];
                            break;
                        case 'fonts':
                            $fonts[] = $font->extra_data['web_url'];
                            break;
                    }
                } else {
                    if ($font->type === 'upload') {
                        $src = [];

                        $filename = $font->extra_data['filename'];
                        foreach ($font->extra_data['filetypes'] as $filetype) {
                            if ($filetype === 'svg') {
                                $src[] = "url('/$fontDirectory/$filename.svg#$filename') format('svg')";
                            } else {
                                $src[] = "url('/$fontDirectory/$filename.$filetype') format('" . $fileTypes[$filetype] . "')";
                            }
                        }

                        $serverFonts[] = [
                            'filename' => $filename,
                            'src' => join(', ', $src)
                        ];
                    }
                }
            }

            /* Pre-load custom google webfonts */
            $templater = \XF::app()->templater();
            $webfonts = isset($templater->pageParams['kl_em_webfonts']) ? $templater->pageParams['kl_em_webfonts'] : [];

            $gfonts = array_unique(array_merge($gfonts, $webfonts));

            #\XF::dump($gfonts);

            $params['em_gfonts'] = $gfonts ? 'https://fonts.googleapis.com/css?' . http_build_query(['family' => join('|', $gfonts)]) : false;
            $params['em_typekit'] = $typekit;
            $params['em_webtype'] = $webtype;
            $params['em_fonts'] = $fonts;
            $params['em_serverFonts'] = $serverFonts;
        }
        $params['em_templates'] = self::getTemplates();
    }

    /**
     * Push enabled BB code informations to help page.
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function klEmAvailableColors(Templater $templater, &$type, &$template, array &$params)
    {
        $colors = explode(',', $params['option']->option_value);
        $colors = array_map('trim', $colors);
        array_pop($colors);
        $params['colors'] = $colors;
    }

    /**
     * Push enabled BB code informations to help page.
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function helpPageBbCode(Templater $templater, &$type, &$template, array &$params)
    {
        /* Push bb code configuration to template */
        $options = \XF::app()->options();
        $params['enabled_bb_codes'] = $options['klEMEnabledBBCodes'];
        $params['max_font_size'] = count(explode(', ', $options['klEMFontSizes'])) + 1;
        $params['hide'] = '[HIDE' . strtoupper($options['klEMDefaultHide']) . ']';
    }

    /*** EDITOR DATA ***/

    /**
     * @param $code
     * @param $toolbars
     * @param $dropdowns
     */
    protected static function removeBbCode($code, &$toolbars, &$dropdowns)
    {
        /** @var BbCodes $repo */
        $repo = \XF::repository('KL\EditorManager:BbCodes');
        $toolbarNames = $repo->shortToButtonDataName($code);
        if (!is_array($toolbarNames)) {
            $toolbarNames = [$toolbarNames];
        }

        foreach ($toolbarNames as $toolbarName) {
            foreach ($toolbars as &$toolbar) {
                $toolbar = array_filter($toolbar, function ($e) use ($toolbarName) {
                    return $toolbarName != $e && "xfCustom_" . $toolbarName != $e;
                });
            }

            foreach ($dropdowns as &$dropdown) {
                $dropdown['buttons'] = array_filter($dropdown['buttons'], function ($e) use ($toolbarName) {
                    return $toolbarName != $e && "xfCustom_" . $toolbarName != $e;
                });
            }
        }
    }

    /**
     * @param $params
     * @return array
     */
    protected static function filterButtons(&$params)
    {
        $toolbars = $params['editorToolbars'];
        $dropdowns = $params['editorDropdowns'];

        $bbCodes = \XF::options()->klEMEnabledBBCodes;

        $enabledBbCodes = [];
        $disabledBbCodes = [];
        foreach ($bbCodes as $name => $bbCode) {
            if ($bbCode) {
                $enabledBbCodes[$name] = true;
                continue;
            }

            $disabledBbCodes[$name] = true;
            self::removeBbCode($name, $toolbars, $dropdowns);
        }

        /** @var BbCodes $bbCodeRepo */
        $bbCodeRepo = \XF::repository('KL\EditorManager:BbCodes');
        $bbCodes = $bbCodeRepo->getBbCodeSettings();

        $visitor = \XF::visitor();
        foreach ($bbCodes as $bbCodeId => $bbCode) {
            $userCriteria = \XF::app()->criteria('XF:User', $bbCode->user_criteria);
            $userCriteria->setMatchOnEmpty(true);

            if (!$userCriteria->isMatched($visitor)) {
                self::removeBbCode($bbCode->bb_code_id, $toolbars, $dropdowns);
                unset($enabledBbCodes[$bbCode->bb_code_id]);
                $disabledBbCodes[$bbCode->bb_code_id] = true;
            }
        }

        if (isset($enabledBbCodes['hide'])) {
            try {
                $threadPostRoute = false;
                $route = \XF::app()->router('public')->routeToController(\XF::app()->request()->getRoutePath());
                if ($route) {
                    $controller = $route->getController();
                    /** @noinspection PhpUndefinedMethodInspection */
                    if (in_array($controller,
                        \XF::repository('KL\EditorManager:BbCodes')->getValidControllersForHide())) {
                        $threadPostRoute = true;
                    }
                }
            } catch (\Exception $e) {
                $threadPostRoute = false;
            }
            if (!$threadPostRoute) {
                unset($enabledBbCodes['hide']);
                $disabledBbCodes['hide'] = true;
                self::removeBbCode('hide', $toolbars, $dropdowns);
            }
        }

        foreach ($toolbars as &$toolbar) {
            $toolbar = array_values($toolbar);
        }
        foreach ($dropdowns as &$dropdown) {
            $dropdown['buttons'] = array_values($dropdown['buttons']);
        }

        $params['editorToolbars'] = $toolbars;
        $params['editorDropdowns'] = $dropdowns;

        return [
            'enabled' => $enabledBbCodes,
            'disabled' => $disabledBbCodes
        ];
    }

    /**
     * @param $bbCodeStates
     * @return array
     */
    protected static function getEnabledPlugins($bbCodeStates)
    {
        $visitor = \XF::visitor();
        $options = \XF::options();

        return array_keys(array_filter([
            'table' => isset($bbCodeStates['enabled']['table']),
            'fullscreen' => true,
            'hide' => isset($bbCodeStates['enabled']['hide']),
            'fontFamily' => isset($bbCodeStates['enabled']['font']),
            'gFontFamily' => $visitor->hasPermission('klEM', 'klEMUseGoogleFonts') && $options->klEMExternalFontPolling,
            'fontSize' => isset($bbCodeStates['enabled']['size']),
            'link' => isset($bbCodeStates['enabled']['url']),
            'image' => isset($bbCodeStates['enabled']['img']),
            'align' => isset($bbCodeStates['enabled']['align']),
            'lists' => isset($bbCodeStates['enabled']['list']),
            'parseHtml' => isset($bbCodeStates['enabled']['parsehtml']),
            'colors' => isset($bbCodeStates['enabled']['color']) || isset($bbCodeStates['enabled']['bgcolor']),
            'templates' => $visitor->hasPermission('klEM', 'klEMTemplates'),
            'draggable' => true,
            'file' => true,
            'bbCode' => true,
            'charCounter' => isset($options->klEMCharCounter) && $options->klEMCharCounter !== 'none',
            'specialCharacters' => true,
            'xfSmilie' => true,
            'unlinkAll' => isset($bbCodeStates['enabled']['url'])
        ]));
    }

    /**
     * @param $plugins
     * @param $bbCodeStates
     * @return array
     */
    protected static function getEditorConfig($plugins, $bbCodeStates)
    {
        /** @var Font $repo */
        $repo = \XF::repository('KL\EditorManager:Font');
        foreach ($repo->getFontsCached() as $font) {
            $fonts[str_replace('"', "'", $font->family)] = $font->title;
        }

        $options = \XF::options();

        $visitor = \XF::visitor();

        return [
            'pluginsEnabled' => $plugins,

            'initOnClick' => isset($options->klEMGeneralOptions['delay_load']) && (bool)($options->klEMGeneralOptions['delay_load']),
            'keepFormatOnDelete' => isset($options->klEMGeneralOptions['keep_format_on_delete']) && (bool)($options->klEMGeneralOptions['keep_format_on_delete']),
            'pastePlain' => isset($options->klEMGeneralOptions['paste_plain']) && (bool)($options->klEMGeneralOptions['paste_plain']),

            'fontFamily' => $fonts,
            'fontSize' => explode(', ', $options->klEMFontSizes),

            'colorsText' => explode(',', preg_replace('/\s/', '', $options->klEMColors)),
            'colorsBackground' => explode(',', preg_replace('/\s/', '', $options->klEMBGColors)),
            'colorsHEXInput' => (bool)($options->klEMHexColor),
            'colorsStep' => (int)$options->klEMColorStep,
            'colorTypes' => [
                'color' => isset($bbCodeStates['enabled']['color']),
                'bgcolor' => isset($bbCodeStates['enabled']['bgcolor'])
            ],

            'charCounterCount' => in_array('charCounter', $plugins),
            'charCounterMode' => $options->klEMCharCounter === 'user' ? $visitor->kl_em_wordcount_mode : $options->klEMCharCounter,

            'tableStyles' => [
                'noborder' => \XF::phrase('kl_em_no_border'),
                'nobackground' => \XF::phrase('kl_em_no_background'),
                'collapse' => \XF::phrase('kl_em_collapse'),
                'alternate' => \XF::phrase('kl_em_alternate_rows'),
                'centered' => \XF::phrase('kl_em_centered'),
                'right' => \XF::phrase('kl_em_right_aligned')
            ],

            'tableEditButtons' => ['tableHeader', 'tableRemove', '|', 'tableRows', 'tableColumns', 'tableStyle']
        ];
    }

    /**
     * @return array|bool|\XF\Mvc\Entity\ArrayCollection|\XF\Mvc\Entity\Entity[]
     */
    protected static function getTemplates()
    {
        $visitor = \XF::visitor();

        /* Load Templates */
        /** @var \KL\EditorManager\Repository\Template $templateRepository */
        $templates = false;
        if ($visitor->hasPermission('klEM', 'klEMTemplates')) {
            /** @var \KL\EditorManager\Repository\Template $templateRepository */
            $templateRepository = \XF::repository('KL\EditorManager:Template');
            $templates = $templateRepository->getTemplatesForUser(\XF::visitor()->user_id,
                $visitor->hasPermission('klEM', 'klEMPublicTemplates'), true);

            $templates = $templates->toArray();
            $templateGroups = [
                0 => [
                    'title' => \XF::phrase('kl_em_public'),
                    'templates' => []
                ],
                1 => [
                    'title' => \XF::phrase('kl_em_private'),
                    'templates' => []
                ]
            ];

            $bbCode = \XF::app()->bbCode();

            foreach ($templates as &$template) {
                $templateGroups[!!$template->user_id]['templates'][] = [
                    'title' => $template->title,
                    'content' => $bbCode->render($template->content, 'editorHtml', '', null)
                ];
            }
            return $templateGroups;
        }

        return $templates;
    }

    /**
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function editor(Templater $templater, &$type, &$template, array &$params)
    {
        $bbCodeStates = self::filterButtons($params);
        $plugins = self::getEnabledPlugins($bbCodeStates);

        $params['klEM'] = [
            'editorConfig' => self::getEditorConfig($plugins, $bbCodeStates),
            'plugins' => $plugins
        ];
    }
}