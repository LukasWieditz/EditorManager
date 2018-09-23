<?php

/*!
 * KL/EditorManager/Html/Renderer/BbCode.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Html\Renderer;

use XF\Html\Tag;

class BbCode extends XFCP_BbCode
{
    protected $klEMFontSizes;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $app = \XF::app();
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

        /* Tables */
        $this->_handlers['table'] = ['filterCallback' => ['$this', 'handleKLEMTable']];
        $this->_handlers['tr'] = ['wrap' => '[TR]%s[/TR]'];
        $this->_handlers['th'] = ['filterCallback' => ['$this', 'handleKLEMTH']];
        $this->_handlers['td'] = ['filterCallback' => ['$this', 'handleKLEMTD']];

        $this->_cssHandlers['font-size'] = ['$this', 'handleKLEMCssFontSize'];
        $this->_cssHandlers['width'] = ['$this', 'handleKLEMCssWidth'];
        $this->_cssHandlers['height'] = ['$this', 'handleKLEMCssHeight'];
    }

    public function handleTagImg($text, Tag $tag)
    {
        $render = parent::handleTagImg($text, $tag);

        $classes = explode(' ', $tag->attribute('class'));
        $align = false;
        if (in_array('fr-fil', $classes)) {
            $align = 'left';
        } else {
            if (in_array('fr-fir', $classes)) {
                $align = 'right';
            }
        }
        if ($align) {
            $render = $this->handleKLEmCssFloat($render, $align);
        }

        return $render;
    }

    public function handleKLEMCssFloat($text, $align)
    {
        if (strpos($text, '[IMG') === 0) {
            return preg_replace('/\[IMG(?:=\')?(.*?)(?:\')?\]/', "[IMG='align:{$align};$1']", $text);
        }

        return $text;
    }

    public function handleKLEMCssWidth($text, $width)
    {
        if (preg_match("/^\d+(px|pt|%)$/", $width)) {
            if (strpos($text, '[IMG') === 0) {
                return preg_replace('/\[IMG(?:=\')?(.*?)(?:\')?\]/', "[IMG='width:{$width};$1']", $text);
            }
        }

        return $text;
    }

    public function handleKLEMCssHeight($text, $height)
    {
        if (preg_match("/!\d+(px|pt|%)$/", $height)) {
            if (strpos($text, '[IMG') === 0) {
                return preg_replace('/\[IMG(?:=\'(.*?)\')?\]/', "[IMG='height:{$height};$1']", $text);
            }
        }

        return $text;
    }

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
     * Handles table td elements.
     *
     * @param string $text Child text of the tag
     * @param Tag $tag The tag, containing all parser information
     *
     * @return string
     */
    public function handleKLEMTable($text, $tag)
    {
        $attributes = $tag->attributes();
        $classes = str_replace('post-table', '', isset($attributes['class']) ? $attributes['class'] : '');
        $classes = implode('-', array_filter(explode(' ', $classes)));
        $option = $classes ? "={$classes}" : '';

        return "[TABLE$option]{$text}[/TABLE]";
    }


    /**
     * Handles table td elements.
     *
     * @param string $text Child text of the tag
     * @param Tag $tag The tag, containing all parser information
     *
     * @return string
     */
    public function handleKLEMTD($text, $tag)
    {
        $styles = isset($tag->attributes()['style']) ? $tag->attributes()['style'] : [];

        $option = isset($styles['vertical-align']) ? "='" . $styles['vertical-align'] . "'" : '';

        $text = trim($text);

        if (strlen($text)) {
            return "[TD$option]\n{$text}\n[/TD]";
        } else {
            return "[TD][/TD]";
        }
    }

    /**
     * Handles table th elements.
     *
     * @param string $text Child text of the tag
     * @param Tag $tag The tag, containing all parser information
     *
     * @return string
     */
    public function handleKLEMTH($text, $tag)
    {
        $styles = isset($tag->attributes()['style']) ? $tag->attributes()['style'] : [];

        $option = isset($styles['vertical-align']) ? "='" . $styles['vertical-align'] . "'" : '';

        return "[TH$option]{$text}[/TH]";
    }

    /**
     * Handles CSS text-align rules.
     *
     * @param string $text Child text of the tag with the CSS
     * @param string $alignment Value of the CSS rule
     * @param Tag $tag
     *
     * @return string
     */
    public function handleCssTextAlign($text, $alignment, Tag $tag)
    {
        if (strtolower($alignment) === 'justify') {
            $styles = isset($tag->attributes()['style']) ? $tag->attributes()['style'] : [];
            $option = isset($styles['max-width']) ? "='" . $styles['max-width'] . "'" : '';

            return "[JUSTIFY$option]{$text}[/JUSTIFY]";
        } else {
            return parent::handleCssTextAlign($text, $alignment, $tag);
        }
    }

    /**
     * Handles CSS font-family rules. The first font is used.
     *
     * @param string $text Child text of the tag with the CSS
     * @param string $alignment Value of the CSS rule
     *
     * @return string
     */
    public function handleCssFontFamily($text, $cssValue)
    {
        list($fontFamily) = explode(',', $cssValue);
        if (preg_match('/^(\'|")(.*)\\1$/', $fontFamily, $match))
        {
            $fontFamily = $match[2];
            $fontFamilies = explode(',', $fontFamily);
            if(count($fontFamilies)) {
                $fontFamily = trim(array_shift($fontFamilies));
            }
        }

        if ($fontFamily && preg_match('/^[a-z0-9 \-]+$/i', $fontFamily))
        {
            return "[FONT=$fontFamily]{$text}[/FONT]";
        }
        else
        {
            return $text;
        }
    }
}