<?php

/*!
 * KL/EditorManager/BbCode/Renderer/Html.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\BbCode\Renderer;

use KL\EditorManager\BbCode\EditorManagerInterface;
use KL\EditorManager\BbCode\EditorManagerTrait;
use XF;
use XF\Entity\User;
use XF\Http\Response;
use XF\Mvc\Entity\ArrayCollection;
use XF\PreEscaped;

/**
 * Class Html
 * @package KL\EditorManager\BbCode\Renderer
 */
class Html extends XFCP_Html implements EditorManagerInterface
{
    use EditorManagerTrait;

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagKLVideo(array $children, $option, array $tag, array $options)
    {
        $optionArray = explode(',', trim($option));

        $params = ['size' => ''];
        foreach ($optionArray as $optionValue) {
            preg_match('/^\s*(width|height)\s*:\s*([0-9]+)\s*$/', $optionValue, $matches);
            if (count($matches) !== 3) {
                continue;
            }

            array_shift($matches);
            list($optionName, $optionVal) = $matches;

            switch ($optionName) {
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

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagKLAudio(array $children, $option, array $tag, array $options)
    {
        $sources = $this->renderSubTreePlain($children);
        return $this->renderVideoAudio("audio", explode(',', $sources), $options);
    }

    /**
     * @param $type
     * @param array $sources
     * @param array $options
     * @param array $params
     * @return null|string|string[]
     */
    protected function renderVideoAudio($type, array $sources, array $options, array $params = [])
    {
        foreach ($sources as &$source) {
            $source = trim($source);
            $bits = explode('.', $source);
            $fileExt = end($bits);

            $validUrl = $this->getValidUrl($source);
            if (!$validUrl) {
                return $this->filterString($source, $options);
            }

            $censored = $this->formatter->censorText($validUrl);
            if ($censored != $validUrl) {
                return $this->filterString($source, $options);
            }

            if ($options['noProxy']) {
                $finalUrl = $validUrl;
            } else {
                $linkInfo = $this->formatter->getLinkClassTarget($validUrl);
                if ($linkInfo['local']) {
                    $finalUrl = $validUrl;
                } else {
                    $finalUrl = $this->formatter->getProxiedUrlIfActive($type, $validUrl);
                    if (!$finalUrl) {
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
            'bgcolor' => ['callback' => 'renderTagKLBGColor'],
            'sup' => ['callback' => 'renderTagKLSup'],
            'sub' => ['callback' => 'renderTagKLSub'],
            'parsehtml' => ['callback' => 'renderTagParseHtml', 'stopBreakConversion' => true],
            'hidereply' => ['callback' => 'RenderTagKLHideReply', 'trimAfter' => 2],
            'hideposts' => ['callback' => 'RenderTagKLHidePosts', 'trimAfter' => 2],
            'hidethanks' => ['callback' => 'RenderTagKLHideThanks', 'trimAfter' => 2],
            'hidereplythanks' => ['callback' => 'RenderTagKLHideReplyThanks', 'trimAfter' => 2],
            'hidegroup' => ['callback' => 'RenderTagKLHideGroup', 'trimAfter' => 2],
            'hide' => ['callback' => 'RenderTagKLHide' . $config['hide_default'], 'trimAfter' => 2],
            'video' => ['callback' => 'renderTagKLVideo'],
            'audio' => ['callback' => 'renderTagKLAudio']
        ];

        /* Merge default BB code aliases into config */
        $config['enabled_bbcodes'] = array_merge(
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

        $this->klConfig = $config;

        foreach ($tags as $name => $options) {
            $this->addTag($name, $options);
        }
    }

    /**
     * Retrieve user object from BB code options
     *
     * @param $options
     * @return null|User
     */
    private function getUserFromOptions($options)
    {
        $user = null;
        if (!empty($options['entity']['User'])) {
            /** @var User $user */
            $user = $options['entity']['User'];
        } else {
            if (!empty($options['user'])) {
                $user = $options['user'];
            }
        }

        return $user;
    }

    /**
     * Custom font family support
     *
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

        /**
         * Returns here, if defined font is available from the editor dropdown menu.
         */
        if (isset($fonts[strtolower($option)])) {
            return $this->wrapHtml('<span style="font-family: ' . $fonts[strtolower($option)] . '">', $output,
                '</span>');
        }

        /**
         * If font is not found above, check whether google fonts is enabled and
         * configured and the user has permission to use it.
         */
        if (XF::app()->options()->klEMExternalFontPolling) {
            $user = $this->getUserFromOptions($options);

            if ($user && $user->hasPermission('klEM', 'klEMUseGoogleFonts')) {
                /** @var Response $response */
                $response = XF::app()->container('response');

                $font = preg_replace('/[^A-Za-z0-9 +/', '', $option);
                $family = strtr($font, [' ' => '+']);

                /**
                 * Directly inject the font family css into the HTML structure, if request is made via XHR/AJAX,
                 * otherwise push it to the page container.
                 */
                if ($response->contentType() === 'application/json' || XF::app()->request()->isXhr()) {
                    $extra = '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=' . $family . '" />';
                } else {
                    $pageParams = XF::app()->templater()->pageParams;
                    $params = isset($pageParams['kl_em_webfonts']) ? $pageParams['kl_em_webfonts'] : [];
                    $params[] = $option;
                    XF::app()->templater()->setPageParam('kl_em_webfonts', $params);
                    $extra = '';
                }
                return $this->wrapHtml($extra . '<span style="font-family:' . $font . '">', $output, '</span>');
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

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function RenderTagKLHideReply(array $children, $option, array $tag, array $options)
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
            XF::visitor()->hasPermission('klEM', 'klEMBypassHide');

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
    public function RenderTagKLHideThanks(array $children, $option, array $tag, array $options)
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
            XF::visitor()->hasPermission('klEM', 'klEMBypassHide');

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
    public function RenderTagKLHidePosts(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTree($children, $options);
        if ($content === '') {
            return '';
        }

        $visitor = XF::visitor();
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
    public function RenderTagKLHideReplyThanks(array $children, $option, array $tag, array $options)
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
            XF::visitor()->hasPermission('klEM', 'klEMBypassHide');

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_reply_thanks', [
            'content' => new PreEscaped($content),
            'visible' => $canView
        ]);
    }

    /**
     * @var
     */
    protected $userGroups;

    /**
     * @return ArrayCollection
     */
    protected function getUserGroups()
    {
        if (!$this->userGroups) {
            $this->userGroups = XF::finder('XF:UserGroup')->fetch();
        }
        return $this->userGroups;
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagKLHideGroup(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTree($children, $options);
        if ($content === '') {
            return '';
        }

        $groups = array_map('trim', explode(',', strtolower($option)));
        $userGroups = $this->getUserGroups();
        foreach ($userGroups as $key => $userGroup) {
            if (!in_array($key, $groups) && !in_array(strtolower($userGroup->title),
                    $groups) && !in_array(strtolower($userGroup->user_title), $groups)) {
                $userGroups->offsetUnset($key);
            }
        }

        $canView = $this->isInGroup($options, $userGroups) ||
            $this->isCreator($options) ||
            XF::visitor()->hasPermission('klEM', 'klEMBypassHide');

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_group', [
            'content' => new PreEscaped($content),
            'visible' => $canView,
            'groupString' => join(', ', $userGroups->pluckNamed('title'))
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
            if (isset($options['entity']) && isset($options['entity']['reaction_users'])) {
                $likeIds = array_map(function ($v) {
                    return $v['user_id'];
                }, $options['entity']['reaction_users']);
            }
        }

        if (isset($likeIds)) {
            return in_array(XF::visitor()->user_id, $likeIds);
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
            $finder = XF::app()->em()->getFinder('XF:Post');
            $posts = $finder->where([
                ['thread_id', $threadId],
                ['user_id', XF::visitor()->user_id],
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
        if (isset($options['user'])) {
            return $options['user']->user_id === XF::visitor()->user_id;
        }

        if (isset($options['user_id'])) {
            return $options['user_id'] === XF::visitor()->user_id;
        }

        if (!empty($options['entity']->User)) {
            return $options['entity']->User->user_id === XF::visitor()->user_id;
        }

        if (!empty($options['entity']['user_id'])) {
            return $options['entity']['user_id'] === XF::visitor()->user_id;
        }

        return false;
    }

    /**
     * @param array $options
     * @param ArrayCollection $groups
     * @return bool
     */
    protected function isInGroup(array $options, ArrayCollection $groups)
    {
        $ids = $groups->keys();
        $user = XF::visitor();
        /** @var User $user */
        $usergroups = $user->secondary_group_ids;
        $usergroups[] = $user->user_group_id;

        $match = array_intersect($usergroups, $ids);
        return count($match) >= 1;
    }

    /**
     * @param $tableHtml
     * @param $tagOption
     * @param $extraContent
     * @return string
     */
    protected function renderFinalTableHtml($tableHtml, $tagOption, $extraContent)
    {
        $tagOption = preg_split('/\s|,|-/', $tagOption);
        $classes = array_map('htmlspecialchars', $tagOption);

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_table', [
            'content' => new PreEscaped($tableHtml),
            'extraContent' => new PreEscaped($extraContent),
            'classes' => join(' ', $classes)
        ]);
    }
}