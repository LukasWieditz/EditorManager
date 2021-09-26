<?php /** @noinspection PhpUnusedParameterInspection */

/*!
 * KL/EditorManager/Listener/TemplaterTemplatePreRender.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Listener;

use KL\EditorManager\EditorConfig;
use XF;
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
    public static function pageContainer(Templater $templater, &$type, &$template, array &$params): void
    {
        $editorConfig = EditorConfig::getInstance();
        $enabledBbCodes = $editorConfig->bbCodeStatus()['enabled'];

        $app = XF::app();
        if (isset($enabledBbCodes['font']) && $enabledBbCodes['font']) {
            $googleFonts = [];
            $serverFonts = [];
            $fontDirectory = $app->get('config')['externalDataPath'] . '/fonts';
            $fileTypes = [
                'ttf' => 'truetype',
                'woff' => 'woff',
                'eot' => 'embedded-opentype',
                'woff2' => 'woff2',
                'otf' => 'opentype'
            ];

            foreach ($editorConfig->fonts() as $font) {
                if ($font->type === 'web') {
                    switch ($font->extra_data['web_service']) {
                        case 'gfonts':
                            $googleFonts[] = $font->extra_data['web_url'];
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
            $templater = $app->templater();
            $webfonts = $templater->pageParams['kl_em_webfonts'] ?? [];

            $googleFonts = array_unique(array_merge($googleFonts, $webfonts));
            $googleFonts = str_replace(' ', '+', join('&family=', $googleFonts));

            $params['em_gfonts'] = $googleFonts ? 'https://fonts.googleapis.com/css2?family=' . $googleFonts : false;
            $params['em_serverFonts'] = $serverFonts;
        }

        $params['em_templates'] = $editorConfig->editorTemplates($params);
    }

    /**
     * Pre-process color values for option template.
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function klEmAvailableColors(Templater $templater, &$type, &$template, array &$params): void
    {
        $colors = explode(',', $params['option']->option_value);
        $colors = array_map('trim', $colors);
        array_pop($colors);
        $params['colors'] = $colors;
    }

    /**
     * Push enabled BB code information to help page.
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function helpPageBbCode(Templater $templater, &$type, &$template, array &$params): void
    {
        /* Push bb code configuration to template */
        $options = XF::app()->options();
        $params['enabled_bb_codes'] = $options['klEMEnabledBBCodes'];
        $params['max_font_size'] = count(explode(', ', $options['klEMFontSizes'])) + 1;
        $params['hide'] = '[HIDE' . strtoupper($options['klEMDefaultHide']) . ']';
    }

    /**
     * @param Templater $templater
     * @param string $type
     * @param string $template
     * @param array $params
     */
    public static function editor(Templater $templater, string &$type, string &$template, array &$params)
    {
        $editorConfig = EditorConfig::getInstance();
        $editorConfig->filterButtons($params);

        $params['klEM'] = [
            'editorConfig' => $editorConfig->editorConfig(),
            'plugins' => $editorConfig->editorPlugins()
        ];
    }
}