<?php

/*!
 * KL/EditorManager/BbCode/Renderer/EditorHtml.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\BbCode\Renderer;

/**
 * Class EditorHtml
 * @package KL\EditorManager\BbCode\Renderer
 */
class EditorHtml extends XFCP_EditorHtml
{
    protected $klFontList = false;
    protected $klConfig = false;

    /**
     * Returns font list from cache.
     * @return array|bool
     */
    private function getKLFontList()
    {
        if ($this->klFontList === false) {
            /* Create Repository */
            $app = \XF::app();

            /** @var \KL\EditorManager\Repository\Font $repo */
            $repo = $app->em()->getRepository('KL\EditorManager:Font');

            /* Load fonts */
            $fontList = $repo->getFontsCached();

            foreach ($fontList as $font) {
                $ids = explode(',', $font->family);
                $id = strtolower(str_replace(["'", '"'], '', $ids[0]));
                $stack = explode(',', str_replace(["'", '"'], '', $font->family));
                foreach ($stack as &$stackFont) {
                    $stackFont = "'" . trim($stackFont) . "'";
                }

                $this->klFontList[$id] = join(', ', $stack);
            }
        }

        return $this->klFontList;
    }

    /**
     * Returns config from cache.
     * @return array|bool
     */
    private function getKLConfig()
    {
        if ($this->klConfig === false) {
            $app = \XF::app();

            $options = $app->options();

            $config = [
                'enabled_bbcodes' => $options['klEMEnabledBBCodes'],
                'font_sizes' => explode(', ', $options['klEMFontSizes']),
                'toolbar_layout' => json_decode(trim($options['klEMLayout'], "'")),
                'disable_smilies' => isset($options['klEMGeneralOptions']['disable_smilies']) && $options['klEMGeneralOptions']['disable_smilies'],
                'enable_fullscreen' => isset($options['klEMGeneralOptions']['enable_fullscreen']) && $options['klEMGeneralOptions']['enable_fullscreen'],
                'keep_format_on_delete' => isset($options['klEMGeneralOptions']['keep_format_on_delete']) && $options['klEMGeneralOptions']['keep_format_on_delete'],
                'enable_charcounter' => isset($options['klEMGeneralOptions']['enable_charcounter']) && $options['klEMGeneralOptions']['enable_charcounter'],
                'enable_templates' => true
            ];

            $this->klConfig = $config;
        }

        $this->undisplayableTags = array_merge($this->undisplayableTags, [
            'ispoiler',
            'hide',
            'hideposts',
            'hidethanks',
            'hidereply',
            'hidereplythanks'
        ]);

        return $this->klConfig;
    }

    /**
     *
     */
    public function addDefaultTags()
    {
        parent::addDefaultTags();

        $tags = [
            'bgcolor' => ['callback' => 'renderTagBGColor'],
            'table' => [
                'callback' => 'renderTagTable',
                'trimAfter' => 1
            ],
            'tr' => [
                'callback' => 'renderTagTR',
                'trimAfter' => 1
            ],
            'th' => [
                'callback' => 'renderTagTH',
                'trimAfter' => 1
            ],
            'td' => [
                'callback' => 'renderTagTD',
                'trimAfter' => 1
            ],
            'justify' => ['replace' => ['<div style="text-align: justify; max-width: %s">', '</div>']],
            'sup' => [
                'callback' => 'renderTagSup'
            ],
            'sub' => [
                'callback' => 'renderTagSub'
            ]
        ];

        foreach ($tags as $name => $options) {
            $this->addTag($name, $options);
        }


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
            'td' => $config['enabled_bbcodes']['table']
        ]);

        foreach ($config['enabled_bbcodes'] as $code => $enabled) {
            if (!$enabled) {
                $this->removeTag($code);
            }
        }

        /** Load aliases */
        /** @noinspection PhpUndefinedMethodInspection */
        foreach (\XF::repository('KL\EditorManager:BbCodes')->getBbCodeSettings() as $bbCode => $config) {
            switch ($bbCode) {
                case 'bold':
                    $bbCode = 'b';
                    break;

                case 'italic':
                    $bbCode = 'i';
                    break;

                case 'underline':
                    $bbCode = 'u';
                    break;

                case 'strike':
                    $bbCode = 's';
                    break;

                case 'image':
                    $bbCode = 'img';
                    break;

                default:
                    break;
            }

            if (isset($this->tags[$bbCode])) {
                foreach ($config->aliases as $alias) {
                    $this->addTag($alias, $this->tags[$bbCode]);
                }
            }
        }
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagSub(array $children, $option, array $tag, array $options)
    {
        $output = $this->renderSubTree($children, $options);
        return "<sub>{$output}</sub>";
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagSup(array $children, $option, array $tag, array $options)
    {
        $output = $this->renderSubTree($children, $options);
        return "<sup>{$output}</sup>";
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

        $xfOptions = \XF::app()->options();
        if ($xfOptions['klEMExternalFontPolling']) {
            $user = \XF::visitor();

            if ($user->hasPermission('klEM', 'klEMUseGoogleFonts')) {
                $font = preg_replace('/[^A-Za-z0-9 \+]/', '', $option);
                $family = strtr($font, [' ' => '+']);

                return $this->wrapHtml("<link rel='stylesheet' href='https://fonts.googleapis.com/css?family={$family}' />" .
                    "<span style=\"font-family: '{$font}'\">", $output, '</span>');
            }
        }

        return $output;
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagTable(array $children, $option, array $tag, array $options)
    {
        $output = '';
        $storage = [];

        $option = preg_split('/(-|\s)/', $option);

        if (in_array('collapse', $option)) {
            $options['collapse'] = true;
        }

        foreach ($children as $child) {
            if (is_array($child)) {
                if ($child['tag'] === 'tr') {
                    if (count($storage)) {
                        $output .= $this->renderTagTR($storage, '', [], $options);
                        $storage = [];
                    }

                    $output .= $this->renderTagTR($child['children'], $child['option'], $child, $options);
                } else {
                    if ($child['tag'] === 'td' || $child['tag'] === 'th') {
                        $storage[] = $child;
                    }
                }

            }
        }

        if (count($storage)) {
            $output .= $this->renderTagTR($storage, '', [], $options);
        }

        $style = isset($options['collapse']) && $options['collapse'] ? '' : " style='width: 100%'";
        $classes = implode(' ', $option);

        return "<table class='post-table {$classes}' {$style}>{$output}</table>";

    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return array|null|string|string[]
     */
    public function renderTagTR(array $children, $option, array $tag, array $options)
    {
        $output = [];
        foreach ($children as $child) {
            if (is_array($child)) {
                if ($child['tag'] === 'td') {
                    $output[] = $this->renderTagTD($child['children'], $child['option'], $child, $options, 'td');
                } else {
                    if ($child['tag'] === 'th') {
                        $output[] = $this->renderTagTH($child['children'], $child['option'], $child, $options);
                    } else {
                        $f = false;
                        foreach ($child['children'] as $xchild) {
                            if (!is_array($xchild) && !preg_match('/^\s*$/', $xchild)) {
                                $f = true;
                                break;
                            }
                        }
                        if ($f) {
                            $output[] = $this->renderSubTree([$child], $options);
                        }
                    }
                }
            } else {
                if (!preg_match('/^\s*$/', $child)) {
                    $output[] = $this->renderTagTD([$child], '', [], $options, 'td');
                }
            }
        }

        if (isset($options['collapse']) && $options['collapse']) {
            $output = implode('', $output);
        } else {
            $percentage = 100 / max(1, count($output));
            $output = preg_replace("/<t(d|h)  style='vertical-align:([^']*)'>/i",
                "<t$1  style=\"width: {$percentage}%; vertical-align: $2;\">", join('', $output));
        }
        $output = "<tr>{$output}</tr>";
        return $output;
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagTH(array $children, $option, array $tag, array $options)
    {
        return $this->renderTagTD($children, $option, $tag, $options, 'th');
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagTD(array $children, $option, array $tag, array $options, $t = 'td')
    {
        $output = $this->renderSubTree($children, $options);

        if (!is_string($t)) {
            $t = 'td';
        }

        #$output = preg_replace("/^(?:<break-start \/>)*(.*?)(?:<break-start \/>)*$/s", "$1", $output);

        /* Fix line breaks being inserted between content of cells? */
        $output = str_replace('<break />', '<br />', $output);

        /* Remove Line breaks from start and end */
        $output = preg_replace("/^(?:<br \/>)*(?!<br \/>)(.*)/s", "$1", $output);
        $output = preg_replace("/(?!<=<br \/>)(?:<br \/>)*$/s", "", $output);

        $output = trim($output);
        $output = str_replace('<break-start />', '', $output);

        $optionString = '';
        if ($option) {
            $optionString = " style='vertical-align: $option;'";
        }

        if (preg_match('/^(<break \/>|\n)*$/', $output)) {
            return "<{$t}{$optionString}></{$t}>";
        } else {
            return "<{$t} {$optionString}>$output</{$t}>";
        }
    }

    protected $maxTextSize = 0;

    /**
     * Unfortunately this has to be a complete override. Might be a source of incompatibility.
     *
     * @param $inputSize
     * @return null|string
     */
    protected function getTextSize($inputSize)
    {
        $klConfig = $this->getKLConfig();
        $options = \XF::app()->options;

        if (strval(intval($inputSize)) == strval($inputSize)) {
            // int only, translate size
            if ($inputSize <= 0) {
                return null;
            }

            if (!empty($klConfig['font_sizes'][$inputSize - 1])) {
                $size = $klConfig['font_sizes'][$inputSize - 1];
            } else {
                $size = end($klConfig['font_sizes']);
            }

            $this->maxTextSize = max($this->maxTextSize, $size);
            return "{$size}px";
        } else {
            // int and unit
            if (!preg_match('/^([0-9]+)px$/i', $inputSize, $match)) {
                return null;
            }

            $max = $options['klEMMaxFontSize'];

            if (!$max) {
                $max = PHP_INT_MAX;
            }

            $min = $options['klEMMinFontSize'];

            list($min, $max) = [min($min, $max), max($min, $max)];

            $size = intval($match[1]);
            $size = max($min, min($size, $max));

            $this->maxTextSize = max($this->maxTextSize, $size);
            return $size . 'px';
        }
    }

    /**
     * @param $output
     * @return mixed|null|string|string[]
     */
    public function filterFinalOutput($output)
    {
        $output = parent::filterFinalOutput($output);

        /* Remove extra line break inserted before table */
        $output = preg_replace('/((?:<p><\/p>)?(<p>)?<table)/', "<table", $output);

        #$output = preg_replace('/(<td[^>]+>)((?:<p><\/p>)+)(<\/td>)/', '<td></td>', $output);

        return $output;
    }

    public function renderTagBGColor(array $children, $option, array $tag, array $options)
    {
        $this->maxTextSize = 0;
        $content = $this->renderSubTree($children, $options);

        $size = $this->maxTextSize ? "font-size: {$this->maxTextSize}px;" : '';
        return "<span style=\"background-color: {$option}; {$size}\">{$content}</span>";
    }

    protected $imageTemplate = '<img src="%1$s" class="bbImage" alt="" data-url="%2$s" style="%3$s" />';

    /**
     * Unfortunately this has to be a complete override. Might be a source of incompatibility.
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return null|string
     */
    public function renderTagImage(array $children, $option, array $tag, array $options)
    {
        $url = $this->renderSubTreePlain($children);

        $validUrl = $this->getValidUrl($url);
        if (!$validUrl) {
            return $this->filterString($url, $options);
        }

        $censored = $this->formatter->censorText($validUrl);
        if ($censored != $validUrl) {
            return $this->filterString($url, $options);
        }

        $imageUrl = $validUrl;

        $xfOptions = \XF::app()->options();

        $config = [];
        foreach (preg_split('/(,|;)/', $option) as $option) {
            $option = explode(':', $option);
            if (count($option) == 2) {
                $config[$option[0]] = $option[1];
            }
        }

        $style = '';
        if ($xfOptions->klEMImageDimensions) {
            if (isset($config['width'])) {
                if (preg_match('/^\d+(?:px|%|pt|em);?$/', $config['width'])) {
                    $value = trim($config['width'], ';');
                    $style .= "width:{$value};";
                }
            }
            if (isset($config['height'])) {
                if (preg_match('/^\d+(?:px|%|pt|em);?$/', $config['height'])) {
                    $value = trim($config['height'], ';');
                    $style .= "height:{$value};";
                }
            }
        }
        if ($xfOptions->klEMImageAlignment) {
            if (isset($config['align'])) {
                if (preg_match('/^(left|right);?$/', $config['align'])) {
                    $value = trim($config['align'], ';');
                    $style .= "float:{$value};";
                }
            }
        }


        return sprintf($this->imageTemplate,
            htmlspecialchars($imageUrl),
            htmlspecialchars($validUrl),
            htmlspecialchars($style)
        );
    }
}