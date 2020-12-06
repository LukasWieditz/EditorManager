<?php

/*!
 * KL/EditorManager/BbCode/Renderer/EditorHtml.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\BbCode\Renderer;

use KL\EditorManager\BbCode\EditorManagerInterface;
use KL\EditorManager\BbCode\EditorManagerTrait;
use XF;

/**
 * Class EditorHtml
 * @package KL\EditorManager\BbCode\Renderer
 */
/**
 * Class EditorHtml
 * @package KL\EditorManager\XF\BbCode\Renderer
 */
class EditorHtml extends XFCP_EditorHtml implements EditorManagerInterface
{
    use EditorManagerTrait;

    /**
     *
     */
    public function addDefaultTags()
    {
        parent::addDefaultTags();

        $tags = [
            'bgcolor' => ['callback' => 'renderTagKLBGColor'],
            'sup' => ['callback' => 'renderTagKLSup'],
            'sub' => ['callback' => 'renderTagKLSub']
        ];

        $this->modifyTag('font', ['callback' => 'renderTagFont']);

        $config = $this->getKLConfig();

        $config['enabled_bbocdes'] = array_merge($config['enabled_bbcodes'], [
            'b' => $config['enabled_bbcodes']['bold'],
            'i' => $config['enabled_bbcodes']['italic'],
            'u' => $config['enabled_bbcodes']['underline'],
            's' => $config['enabled_bbcodes']['strike'],
            'left' => $config['enabled_bbcodes']['align'],
            'center' => $config['enabled_bbcodes']['align'],
            'right' => $config['enabled_bbcodes']['align'],
            'justify' => $config['enabled_bbcodes']['align'],
            'tr' => $config['enabled_bbcodes']['table'],
            'th' => $config['enabled_bbcodes']['table'],
            'td' => $config['enabled_bbcodes']['table'],
        ]);

        $this->klConfig = $config;

        foreach ($tags as $name => $options) {
            $this->addTag($name, $options);
        }
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagFont(array $children, $option, array $tag, array $options)
    {
        $fonts = $this->getKLFontList();
        $output = $this->renderSubTree($children, $options);


        if (isset($fonts[strtolower($option)])) {
            return $this->wrapHtml('<span style="font-family: ' . $fonts[strtolower($option)] . '">', $output,
                '</span>');
        }

        $xfOptions = XF::app()->options();
        if ($xfOptions['klEMExternalFontPolling']) {
            $user = XF::visitor();

            if ($user->hasPermission('klEM', 'klEMUseGoogleFonts')) {
                $font = preg_replace('/[^A-Za-z0-9 +]/', '', $option);
                $family = strtr($font, [' ' => '+']);

                return $this->wrapHtml("<link rel='stylesheet' href='https://fonts.googleapis.com/css?family={$family}' />" .
                    "<span style=\"font-family: '{$font}'\">", $output, '</span>');
            }
        }

        return $output;
    }

    /**
     * @param $tableHtml
     * @param $tagOption
     * @param $extraContent
     * @return string
     */
    protected function renderFinalTableHtml($tableHtml, $tagOption, $extraContent)
    {
        $classes = str_replace(',', ' ', $tagOption);

        $output = "<table style='width: 100%' class='{$classes}'>$tableHtml</table>";

        if (strlen($extraContent)) {
            $output .= "<p>$extraContent</p>";
        }

        return $output;
    }
}