<?php

/*!
 * KL/EditorManager/BbCode/Renderer/Html.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\BbCode\Renderer;

use XF\PreEscaped;

/**
 * Class Html
 * @package KL\EditorManager\BbCode\Renderer
 */
class Html extends XFCP_Html
{
    /**
     * Editor Font List
     * @var array
     */
    protected $klFontList;

    /**
     * BB Code configuration Configuration
     * @var array
     */
    protected $klConfig;

    protected $klCriteria;

    /**
     * Returns font list from cache.
     * @return array
     */
    private function getKLFontList()
    {
        if (!$this->klFontList) {
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
     * @return array
     */
    private function getKLConfig()
    {
        if (!$this->klConfig) {
            $app = \XF::app();

            $options = $app->options();

            $config = [
                'enabled_bbcodes' => $options->klEMEnabledBBCodes,
                'font_sizes' => explode(', ', $options->klEMFontSizes),
                'toolbar_layout' => json_decode(trim($options->klEMLayout, "'")),
                'disable_smilies' => (bool)($options->klEMGeneralOptions['disable_smilies']),
                'enable_fullscreen' => (bool)($options->klEMGeneralOptions['enable_fullscreen']),
                'keep_format_on_delete' => (bool)($options->klEMGeneralOptions['keep_format_on_delete']),
                'enable_charcounter' => (bool)($options->klEMGeneralOptions['enable_charcounter']),
                'enable_templates' => true,
                'hide_default' => $options->klEMDefaultHide
            ];

            $this->klConfig = $config;
        }

        return $this->klConfig;
    }

    /**
     *
     */
    public function addDefaultTags()
    {
        parent::addDefaultTags();

        $config = $this->getKLConfig();

        /* Determine default hide */
        if (empty($config['hide_default'])) {
            $config['hide_default'] = 'Reply';
        }

        $tags = [
            'bgcolor' => ['callback' => 'renderTagBGColor'],
            'justify' => [
                'callback' => 'renderTagJustify',
                'trimAfter' => 1
            ],
            'ispoiler' => ['callback' => 'renderTagISpoiler', 'trimAfter' => 1],
            'sup' => ['callback' => 'renderTagSup'],
            'sub' => ['callback' => 'renderTagSub'],
            'parsehtml' => [
                'callback' => 'renderTagParseHtml',
                'stopBreakConversion' => true
            ],
            'table' => ['callback' => 'renderTagTable'],
            'tr' => ['callback' => 'renderTagTR'],
            'th' => ['callback' => 'renderTagTH'],
            'td' => ['callback' => 'renderTagTD'],
            'hidereply' => ['callback' => 'renderTagHideReply', 'trimAfter' => 2],
            'hideposts' => ['callback' => 'renderTagHidePosts', 'trimAfter' => 2],
            'hidethanks' => ['callback' => 'renderTagHideThanks', 'trimAfter' => 2],
            'hidereplythanks' => ['callback' => 'renderTagHideReplyThanks', 'trimAfter' => 2],
            'hide' => ['callback' => 'renderTagHide' . $config['hide_default'], 'trimAfter' => 2],
            'video' => ['callback' => 'renderTagVideo'],
            'audio' => ['callback' => 'renderTagAudio']
        ];

        foreach($tags as $name => $options) {
            $this->addTag($name, $options);
        }

        $this->modifyTag('font', ['callback' => 'renderTagKLFont']);

        /**
         * -- Process enabled/disabled codes
         */

        /* Merge default BB code aliases into config */
        $config['enabled_bbocdes'] = array_merge(
            $config['enabled_bbcodes'],
            [
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

                'email' => $config['enabled_bbcodes']['url'],

                'hidereply' => $config['enabled_bbcodes']['hide'],
                'hidethanks' => $config['enabled_bbcodes']['hide'],
                'hideposts' => $config['enabled_bbcodes']['hide'],
                'hidereplythanks' => $config['enabled_bbcodes']['hide'],
            ]
        );

        /* Strip disabled BB codes from renderer */
        foreach ($config['enabled_bbcodes'] as $code => $enabled) {
            if (!$enabled) {
                $this->removeTag($code);
            }
        }

        /** Load criteria */
        /** @noinspection PhpUndefinedMethodInspection */
        $this->klCriteria = \XF::repository('KL\EditorManager:BbCodes')->getBbCodeSettings();

        /** Load aliases */
        foreach ($this->klCriteria as $bbCode => $config) {
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
     * @param array $tag
     * @param array $options
     * @return mixed|string
     */
    public function renderTag(array $tag, array $options)
    {
        switch ($tag['tag']) {
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
                $tagName = $tag['tag'];
                break;
        }

        if (isset($this->klCriteria[$tagName]) && !empty($this->klCriteria[$tagName]->user_criteria)) {
            /** @var \KL\EditorManager\Entity\BbCode $criteria */
            $criteria = $this->klCriteria[$tagName];

            $userCriteria = \XF::app()->criteria('XF:User', $criteria->user_criteria ?: []);
            $userCriteria->setMatchOnEmpty(true);
            $user = false;

            if (!empty($options['entity']['User'])) {
                $user = $options['entity']['User'];
            } else {
                if (!empty($options['user'])) {
                    $user = $options['user'];
                }
            }

            $matched = false;
            if ($user) {
                if ($userCriteria->isMatched($user)) {
                    $matched = true;
                }
            }

            if (!$matched) {
                return $this->renderUnparsedTag($tag, $options);
            }
        }

        return parent::renderTag($tag, $options);
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagJustify(array $children, $option, array $tag, array $options)
    {
        $output = trim($this->renderSubTree($children, $options));

        $invisibleSpace = $this->endsInBlockTag($output) ? '' : '&#8203;';

        return $this->wrapHtml(
            '<div style="text-align: justify">',
            $output . $invisibleSpace,
            '</div>'
        );
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
    public function renderTagKLFont(array $children, $option, array $tag, array $options)
    {
        $fonts = $this->getKLFontList();
        $output = $this->renderSubTree($children, $options);

        /**
         * Returns here, if defined font is available from the editor dropdown menu.
         */
        if (isset($fonts[strtolower($option)])) {
            return $this->wrapHtml('<span style="font-family: ' . $fonts[strtolower($option)] . '">', $output,
                '</span>');
        }

        /**
         * If font is not found above, check whether google fonts is enabled and configured and the user has permission to use it.
         */
        $xfOptions = \XF::app()->options();
        if ($xfOptions['klEMExternalFontPolling']) {
            $user = false;
            if (!empty($options['entity']['User'])) {
                /** @var \XF\Entity\User $user */
                $user = $options['entity']['User'];
            } else {
                if (!empty($options['user'])) {
                    $user = $options['user'];
                }
            }

            if ($user && $user->hasPermission('klEM', 'klEMUseGoogleFonts')) {
                /** @var \XF\Http\Response $response */
                $response = \XF::app()->container('response');

                $font = preg_replace('/[^A-Za-z0-9 \+]/', '', $option);
                $family = strtr($font, [' ' => '+']);

                /**
                 * Directly inject the font family css into the HTML structure, if request is made via XHR/AJAX,
                 * otherwise push it to the page container.
                 */
                if ($response->contentType() === 'application/json' || \XF::app()->request()->isXhr()) {
                    return $this->wrapHtml("<link rel='stylesheet' href='https://fonts.googleapis.com/css?family={$family}' />" .
                        "<span style=\"font-family: '{$font}'\">", $output, '</span>');
                } else {
                    if (empty(\XF::app()->templater()->pageParams['webfonts'])) {
                        $params = [];
                    } else {
                        $params = \XF::app()->templater()->pageParams['webfonts'];
                    }

                    $params[] = $option;

                    \XF::app()->templater()->setPageParam('webfonts', $params);

                    return $this->wrapHtml("<span style='font-family: {$font}'>", $output, '</span>');
                }
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
        } else {
            $options['collapse'] = false;
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

        if (!$options['collapse']) {
            $style = "style='width: 100%'";
        } else {
            $style = '';
        }
        $classes = implode(' ', $option);


        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_table', [
            'content' => new PreEscaped($output),
            'classes' => $classes,
            'style' => $style
        ]);
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return array|mixed|string
     */
    public function renderTagTR(array $children, $option, array $tag, array $options)
    {
        $output = [];
        foreach ($children as $child) {
            if (is_array($child)) {
                if ($child['tag'] === 'td') {
                    $output[] = $this->renderTagTD($child['children'], $child['option'], $child, $options);
                } else {
                    if ($child['tag'] === 'th') {
                        $output[] = $this->renderTagTH($child['children'], $child['option'], $child, $options);
                    } else {
                        $f = false;
                        foreach ($child['children'] as $xchild) {
                            if (!is_array($xchild)) {
                                if (!preg_match('/^\s*$/', $xchild)) {
                                    $f = true;
                                    break;
                                }
                            }
                        }
                        if ($f) {
                            $output[] = $this->renderSubTree([$child], $options);
                        }
                    }
                }
            } else {
                if (!preg_match('/^\s*$/', $child)) {
                    $output[] = $this->renderTagTD([$child], '', [], $options);
                }
            }
        }

        if (isset($options['collapse']) && $options['collapse']) {
            $output = join('', $output);
        } else {
            if (count($output)) {
                $percentage = 100 / count($output);
            } else {
                $percentage = 100;
            }
            $output = preg_replace("/<t(d|h) style='vertical-align:([^']*);'>/i",
                "<t$1 style=\"width: $percentage%; vertical-align: $2;\">", join('', $output));
        }
        $output = "<tr>$output</tr>";
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
        $output = $this->renderSubTree($children, $options);
        /* Remove Line breaks from start and end */
        $output = preg_replace("/^(?:<br \/>\n)*(?!<br \/>\n)(.*)/s", "$1", $output);
        $output = preg_replace("/(?!<=<br \/>\n)(?:<br \/>\n)*$/s", "", $output);

        if (preg_match('/^(<br \/>|\n)*$/', $output)) {
            return "<th style='vertical-align: $option;'>&nbsp;</th>";
        } else {
            return "<th style='vertical-align: $option;'><p>$output</p></th>";
        }
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagTD(array $children, $option, array $tag, array $options)
    {
        $output = $this->renderSubTree($children, $options);

        /* Remove Line breaks from start and end */
        $output = preg_replace("/^(?:<br \/>\n)*(?!<br \/>\n)(.*)/s", "$1", $output);
        $output = preg_replace("/(?!<=<br \/>\n)(?:<br \/>\n)*$/s", "", $output);

        if (preg_match('/^(<br \/>|\n)*$/', $output)) {
            return "<td style='vertical-align: $option;'>&nbsp;</td>";
        } else {
            return "<td style='vertical-align: $option;'>$output</td>";
        }
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagISpoiler(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTree($children, $options);
        if ($content === '') {
            return '';
        }

        if ($option) {
            $title = $this->filterString($option, array_merge($options, [
                'stopSmilies' => 1,
                'stopBreakConversion' => 1
            ]));
        } else {
            $title = null;
        }

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_ispoiler', [
            'content' => new PreEscaped($content),
            'title' => $title ? new PreEscaped($title) : null
        ]);
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagHideReply(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTree($children, $options);
        if ($content === '') {
            return '';
        }

        $canView = $this->canReplyView($options) ||
            $this->isCreator($options) ||
            \XF::visitor()->hasPermission('klEM', 'klEMBypassHide');

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_reply', [
            'content' => new PreEscaped($content),
            'visible' => $canView
        ]);
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagHideThanks(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTree($children, $options);
        if ($content === '') {
            return '';
        }

        $canView = $this->canLikeView($options) ||
            $this->isCreator($options) ||
            \XF::visitor()->hasPermission('klEM', 'klEMBypassHide');

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_thanks', [
            'content' => new PreEscaped($content),
            'visible' => $canView
        ]);
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagHidePosts(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTree($children, $options);
        if ($content === '') {
            return '';
        }

        $visitor = \XF::visitor();
        if ($visitor->user_id) {
            $canView = $visitor->hasPermission('klEM', 'klEMBypassHide') ||
                ($visitor->hasPermission('klEM', 'klEMHidePostCount') !== -1 &&
                    $visitor->hasPermission('klEM', 'klEMHidePostCount') <= $visitor->message_count) ||
                $this->isCreator($options);

            $message_threshold = $visitor->hasPermission('klEM', 'klEMHidePostCount');
        } else {
            $canView = false;
            $message_threshold = -1;
        }
        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_posts', [
            'content' => new PreEscaped($content),
            'visible' => $canView,
            'message_threshold' => $message_threshold,
            'message_count' => $visitor->message_count
        ]);
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagHideReplyThanks(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTree($children, $options);
        if ($content === '') {
            return '';
        }

        $canView = $this->canLikeView($options) || $this->canReplyView($options) ||
            $this->isCreator($options) ||
            \XF::visitor()->hasPermission('klEM', 'klEMBypassHide');

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_reply_thanks', [
            'content' => new PreEscaped($content),
            'visible' => $canView
        ]);
    }

    /**
     * @param $options
     * @return bool
     */
    protected function canLikeView($options)
    {

        if (isset($options['likes'])) {
            $likeIds = array_map(function ($v) {
                return $v['user_id'];
            }, $options['likes']);
        } else {
            if (isset($options['entity']) && isset($options['entity']['like_users'])) {
                $likeIds = array_map(function ($v) {
                    return $v['user_id'];
                }, $options['entity']['like_users']);
            }
        }

        if (isset($likeIds)) {
            return in_array(\XF::visitor()->user_id, $likeIds);
        } else {
            return false;
        }
    }

    /**
     * @param $options
     * @return bool|int
     */
    protected function canReplyView($options)
    {
        $threadId = 0;
        if (isset($options['thread_id'])) {
            $threadId = $options['thread_id'];
        } else {
            if (isset($options['entity']) && isset($options['entity']['thread_id'])) {
                $threadId = $options['entity']['thread_id'];
            }
        }

        if ($threadId) {
            $finder = \XF::app()->em()->getFinder('XF:Post');
            $posts = $finder->where([
                ['thread_id', $threadId],
                ['user_id', \XF::visitor()->user_id],
                ['message_state', 'visible']
            ])->limit(1)->fetch();

            return $posts->count();
        } else {
            return false;
        }

    }

    /**
     * @param $options
     * @return bool
     */
    protected function isCreator($options)
    {
        if (isset($options['user_id'])) {
            return $options['user_id'] === \XF::visitor()->user_id;
        }
        if (!empty($options['entity']['user_id'])) {
            return $options['entity']['user_id'] === \XF::visitor()->user_id;
        } else {
            return false;
        }
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagParseHtml(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTreePlain($children);

        if ($content === '') {
            return '';
        }

        return html_entity_decode($content);
    }

    protected $maxTextSize = 0;

    /**
     * Unfortunately this has to be a complete override. Might be a source of incompatibility.
     * @param $inputSize
     * @return null|string
     */
    protected function getTextSize($inputSize)
    {
        $klConfig = $this->getKLConfig();
        $options = \XF::app()->options();

        if (strval(intval($inputSize)) == strval($inputSize)) {
            // int only, translate size
            if ($inputSize <= 0) {
                return null;
            }

            $size = (isset($klConfig['font_sizes'][$inputSize - 1]) ? $klConfig['font_sizes'][$inputSize - 1] : end($klConfig['font_sizes']));
            $this->maxTextSize = max($this->maxTextSize, $size);
            return  "{$size}px";
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

    public function renderTagBGColor(array $children, $option, array $tag, array $options) {
        $this->maxTextSize = 0;
        $content = $this->renderSubTree($children, $options);

        $size = $this->maxTextSize ? "font-size: {$this->maxTextSize}px;" : '';
        return "<span style=\"background-color: {$option}; {$size}\">{$content}</span>";
    }

    public function renderTagVideo(array $children, $option, array $tag, array $options) {
        $optionArray = explode(',', trim($option));

        $params = ['size' => ''];
        foreach($optionArray as $optionValue) {
            preg_match('/^\s*(width|height)\s*:\s*([0-9]+)\s*$/', $optionValue, $matches);
            if(count($matches) !== 3) {
                continue;
            }

            list($c, $optionName, $optionVal) = $matches;

            switch($optionName) {
                case 'width':
                    $params['size'] .= "max-width:{$optionVal}px;";
                    break;

                case 'height':
                    $params['size'] .= "max-height:{$optionVal}px;";
                    break;
            }
        }

        $sources = $this->renderSubTreePlain($children);
        return $this->renderVideoAudio("video", explode(',', $sources), $options, $params);
    }

    public function renderTagAudio(array $children, $option, array $tag, array $options) {
        $sources = $this->renderSubTreePlain($children);
        return $this->renderVideoAudio("audio", explode(',', $sources), $options);
    }

    protected function renderVideoAudio($type, array $sources, array $options, array $params = []) {
        foreach($sources as &$source) {
            $source = trim($source);
            $bits = explode('.', $source);
            $fileExt = end($bits);

            $validUrl = $this->getValidUrl($source);
            if (!$validUrl)
            {
                return $this->filterString($source, $options);
            }

            $censored = $this->formatter->censorText($validUrl);
            if ($censored != $validUrl)
            {
                return $this->filterString($source, $options);
            }

            if ($options['noProxy'])
            {
                $finalUrl = $validUrl;
            }
            else
            {
                $linkInfo = $this->formatter->getLinkClassTarget($validUrl);
                if ($linkInfo['local'])
                {
                    $finalUrl = $validUrl;
                }
                else
                {
                    $finalUrl = $this->formatter->getProxiedUrlIfActive($type, $validUrl);
                    if (!$finalUrl)
                    {
                        $finalUrl = $validUrl;
                    }
                }
            }

            $source = [
                'url' => $finalUrl,
                'type' => strtolower($fileExt)
            ];
        }

        $params['sources'] = $sources;
        return $this->templater->renderTemplate("public:kl_em_bb_code_tag_{$type}", $params);
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
        if (!$validUrl)
        {
            return $this->filterString($url, $options);
        }

        $censored = $this->formatter->censorText($validUrl);
        if ($censored != $validUrl)
        {
            return $this->filterString($url, $options);
        }

        if ($options['noProxy'])
        {
            $imageUrl = $validUrl;
        }
        else
        {
            $linkInfo = $this->formatter->getLinkClassTarget($validUrl);
            if ($linkInfo['local'])
            {
                $imageUrl = $validUrl;
            }
            else
            {
                $imageUrl = $this->formatter->getProxiedUrlIfActive('image', $validUrl);
                if (!$imageUrl)
                {
                    $imageUrl = $validUrl;
                }
            }
        }

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


        if (!empty($options['lightbox']))
        {
            return $this->templater->renderTemplate('public:bb_code_tag_img', [
                'imageUrl' => $imageUrl,
                'validUrl' => $validUrl,
                'style' => $style
            ]);
        }
        else
        {
            return sprintf($this->imageTemplate,
                htmlspecialchars($imageUrl),
                htmlspecialchars($validUrl),
                htmlspecialchars($style)
            );
        }
    }
}