<?php

/*!
 * KL/EditorManager/Html/Renderer/BbCode.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Html\Renderer;

use XF;
use XF\Html\Tag;

/**
 * Class BbCode
 * @package KL\EditorManager\XF\Html\Renderer
 */
class BbCode extends XFCP_BbCode
{
    /**
     * @var array
     */
    protected $klEMFontSizes;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $app = XF::app();
        $options = $app->options()->klEMEnabledBBCodes;

        $this->klEMFontSizes = explode(',', $app->options()->klEMFontSizes);
        $this->klEMFontSizes = array_map('trim', $this->klEMFontSizes);


        if (isset($options['bgcolor']) && $options['bgcolor']) {
            /* Background Color */
            $this->_cssHandlers['background-color'] = ['$this', 'handleKLEMBGCssColor'];
        }

        /* Sub- & Superscript */
        $this->_handlers['sub'] = ['wrap' => '[SUB]%s[/SUB]'];
        $this->_handlers['sup'] = ['wrap' => '[SUP]%s[/SUP]'];

        $this->_cssHandlers['font-size'] = ['$this', 'handleKLEMCssFontSize'];
    }

    /**
     * @param $text
     * @param $fontSize
     * @return string
     */
    public function handleKLEMCssFontSize($text, $fontSize)
    {
        switch (strtolower($fontSize)) {
            case 'xx-small':
                $fontSize = 1;
                break;

            case 'x-small':
                $fontSize = 2;
                break;

            case 'small':
                $fontSize = 3;
                break;

            case 'medium':
            case '100%':
                $fontSize = 4;
                break;

            case 'large':
                $fontSize = 5;
                break;

            case 'x-large':
                $fontSize = 6;
                break;

            case 'xx-large':
                $fontSize = 7;
                break;

            default:
                $size = array_search($fontSize, $this->klEMFontSizes);
                if ($size !== false) {
                    $fontSize = $size;
                    break;
                }

                if (!preg_match('/^[0-9]+(px)?$/i', $fontSize)) {
                    $fontSize = 0;
                }
        }

        if ($fontSize) {
            return "[SIZE=$fontSize]{$text}[/SIZE]";
        } else {
            return $text;
        }
    }

    /**
     * Handles CSS (background) color rules.
     *
     * @param string $text Child text of the tag with the CSS
     * @param $color
     * @return string
     * @internal param string $alignment Value of the CSS rule
     *
     */
    public function handleKLEMBGCssColor($text, $color)
    {
        if ($color !== 'transparent') {
            return "[BGCOLOR=$color]{$text}[/BGCOLOR]";
        } else {
            return $text;
        }
    }

    /**
     * Handles CSS font-family rules. The first font is used.
     *
     * @param string $text Child text of the tag with the CSS
     * @param $cssValue
     * @return string
     */
    public function handleCssFontFamily($text, $cssValue)
    {
        list($fontFamily) = explode(',', $cssValue);
        if (preg_match('/^([\'"])(.*)\\1$/', $fontFamily, $match)) {
            $fontFamily = $match[2];
            $fontFamilies = explode(',', $fontFamily);
            if (count($fontFamilies)) {
                $fontFamily = trim(array_shift($fontFamilies));
            }
        }

        if ($fontFamily && preg_match('/^[a-z0-9 \-]+$/i', $fontFamily)) {
            return "[FONT=$fontFamily]{$text}[/FONT]";
        } else {
            return $text;
        }
    }

    /**
     * @param string $text
     * @param Tag $tag
     * @return string
     */
    public function handleTagTable($text, Tag $tag)
    {
        $option = str_replace(' ', ',', $tag->attribute('class'));
        if ($option) {
            $option = '=' . $option;
        }

        $output = "[TABLE{$option}]\n{$text}\n[/TABLE]";
        return $this->renderCss($tag, $output);
    }

    public function handleTagImg($text, Tag $tag)
    {
        if (($tag->hasClass('kl-em-emote') || $tag->attribute('data-emote')) && $tag->attribute('alt'))
        {

            return $this->renderCss($tag, trim($tag->attribute('alt')));
        }

        return parent::handleTagImg($text, $tag);
    }
}