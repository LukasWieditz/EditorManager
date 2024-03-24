<?php /** @noinspection PhpUnusedParameterInspection */

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
use XF\Mvc\Entity\AbstractCollection;
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
    public function renderTagUrl(array $children, $option, array $tag, array $options)
    {
        if (!XF::options()->klEMshowLinksToGuests && !XF::visitor()->user_id) {
            return '<a href="' . XF::app()->router('public')->buildLink('login/login') . '" data-xf-click="overlay">'
                . XF::phrase('kl_em_you_must_be_logged_in_to_see_this_link') . '</a>';
        }

        return parent::renderTagUrl($children, $option, $tag, $options);
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagKLVideo(array $children, $option, array $tag, array $options)
    {
        $optionArray = explode(',', trim($option ?? ''));

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
            'hidemembers' => ['callback' => 'renderTagKLHideMembers', 'trimAfter' => 2],
            'hidedate' => ['callback' => 'renderTagKLHideDate', 'trimAfter' => 2],
            'hidetime' => ['callback' => 'renderTagKLHideDate', 'trimAfter' => 2],
            'hide' => ['callback' => 'RenderTagKLHide' . $config['hide_default'], 'trimAfter' => 2],
            'video' => ['callback' => 'renderTagKLVideo'],
            'audio' => ['callback' => 'renderTagKLAudio'],
            'fa' => ['callback' => 'renderTagKLFA'],
            // 'chart' => ['callback' => 'renderTagKLChart', 'trimAfter' => 2]
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

                'hidemembers' => $config['enabled_bbcodes']['hide'],
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

                $font = preg_replace('/[^A-Za-z0-9 ]+/', '', $option);
                $family = strtr($font, [' ' => '+']);

                /**
                 * Directly inject the font family css into the HTML structure, if request is made via XHR/AJAX,
                 * otherwise push it to the page container.
                 */
                if ($response->contentType() === 'application/json' || XF::app()->request()->isXhr()) {
                    $extra = '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . $family . '" />';
                } else {
                    $pageParams = XF::app()->templater()->pageParams;
                    $params = $pageParams['kl_em_webfonts'] ?? [];
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
    public function RenderTagKLHideDate(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTree($children, $options);
        if ($content === '') {
            return '';
        }

        $date = strtotime($option);

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_date', [
            'content' => new PreEscaped($content),
            'visible' => XF::$time >= $date || $this->isKLEMCreator($options),
            'visibleAt' => $date
        ]);
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
            $this->isKLEMCreator($options) ||
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
            $this->isKLEMCreator($options) ||
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
            $requiredPostCount = $visitor->hasPermission('klEM', 'klEMHidePostCount');

            $canView = $visitor->hasPermission('klEM', 'klEMBypassHide') ||
                ($requiredPostCount !== -1 && $requiredPostCount <= $visitor->message_count) ||
                $this->isKLEMCreator($options);

            $messageThreshold = $visitor->hasPermission('klEM', 'klEMHidePostCount');
        } else {
            $canView = false;
            $messageThreshold = -1;
        }

        if ($messageThreshold === -1) {
            return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_never', [
                'content' => new PreEscaped($content),
                'visible' => $canView,
            ]);
        }

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_posts', [
            'content' => new PreEscaped($content),
            'visible' => $canView,
            'messageThreshold' => $messageThreshold,
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
            $this->isKLEMCreator($options) ||
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
     * @return AbstractCollection
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
    public function renderTagKLHideMembers(array $children, $option, array $tag, array $options)
    {
        if (!$children) {
            return '';
        }

        $this->trimChildrenList($children);

        $content = $this->renderSubTree($children, $options);
        if ($content === '') {
            return '';
        }

        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_hide_members', [
            'content' => new PreEscaped($content),
            'visible' => (bool)XF::visitor()->user_id
        ]);
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
            if (
                !in_array($key, $groups)
                && !in_array(strtolower($userGroup->title), $groups)
                && !in_array(strtolower($userGroup->user_title), $groups)
            ) {
                $userGroups->offsetUnset($key);
            }
        }

        $canView = $this->isInGroup($options, $userGroups) ||
            $this->isKLEMCreator($options) ||
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
        if (!XF::visitor()->user_id) {
            return false;
        }

        if (isset($options['likes'])) {
            $likeIds = array_map(function ($v) {
                return $v['user_id'];
            }, $options['likes']);
        } else {
            if (isset($options['entity']['reaction_users'])) {
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
        if (!XF::visitor()->user_id) {
            return false;
        }
        $threadId = 0;
        if (isset($options['thread_id'])) {
            $threadId = $options['thread_id'];
        } else {
            if (isset($options['entity']['thread_id'])) {
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
    protected function isKLEMCreator($options)
    {
        if (!XF::visitor()->user_id) {
            return false;
        }

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
     * @param AbstractCollection $groups
     * @return bool
     */
    protected function isInGroup(array $options, AbstractCollection $groups)
    {
        if (!XF::visitor()->user_id) {
            return false;
        }

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

    protected function renderTagKLFA(array $children, $option, array $tag, array $options)
    {
        $text = $this->renderSubTreePlain($children);

        if (substr($text, 0, 3) !== 'fa-') {
            $text = 'fa-' . $text;
        }

        switch ($option) {
            case 'brand':
            case 'brands':
            case 'b':
                $text = 'fab ' . $text;
                break;

            case 's':
            case 'solid':
                $text = 'fas ' . $text;
                break;

            case 'light':
            case 'l':
                $text = 'fal ' . $text;
                break;

            case 'regular':
            case 'r':
                $text = 'far ' . $text;
                break;

            case 'duotone':
            case 'duo':
            case 'd':
                $text = 'fad ' . $text;
                break;
        }

        $templater = $this->getTemplater();
        return $templater->fontAwesome($text);
    }

//    /**
//     * @param $option
//     * @return array
//     */
//    protected function parseKLChartOptions($option): array
//    {
//        $defaultData = [
//            'type' => 'line',
//            'title' => null,
//            'timeseries' => false,
//            'datasetCount' => 0
//        ];
//
//        if (!is_array($option)) {
//            $defaultData['title'] = $option;
//            return $defaultData;
//        }
//
//        $data = [];
//
//        if (isset($option['type'])) {
//            switch ($option['type']) {
//                case 'line':
//                case 'pie':
//                case 'area':
//                case 'bar':
//                case 'horizontalBar':
//                case 'radar':
//                case 'doughnut':
//                case 'polar-area':
//                    $data['type'] = $option['type'];
//                    break;
//
//                case 'bar-horizontal':
//                    $data['type'] = 'horizontalBar';
//                    break;
//
//                default:
//                    $data['type'] = 'line';
//            }
//        } else {
//            $data['type'] = 'line';
//        }
//
//        if (isset($option['title'])) {
//            $data['title'] = $option['title'];
//        }
//
//        return array_replace($defaultData, $data);
//    }
//
//    /**
//     * @param array $children
//     * @param $option
//     * @param array $tag
//     * @param array $options
//     */
//    protected function renderTagKLChart(array $children, $option, array $tag, array $options)
//    {
//        $datasets = [];
//        $options['chartOptions'] = $this->parseKLChartOptions($option);
//
//        $lostAndFound = [];
//
//        foreach ($children as $child) {
//            if (is_array($child)) {
//                if ($child['tag'] === 'chartdata') {
//                    $datasets[] = $this->renderTagKLChartDataset($child['children'], $child['option'],
//                        $child['original'], $options);
//                } else {
//                    $lostAndFound[] = $this->renderSubTree([$child], $options);
//                }
//            } else {
//                if (trim($child) !== '') {
//                    $lostAndFound[] = $this->renderSubTree([$child], $options);
//                }
//            }
//        }
//
//        $options = $options['chartOptions'];
//        return $this->templater->renderTemplate('public:kl_em_bb_code_tag_chart', [
//                'chartConfig' => [
//                    'type' => $options['type'],
//                    'data' => [
//                        'datasets' => $datasets
//                    ],
//                    'options' => [
//                        'title' => [
//                            'display' => (bool)$options['title'],
//                            'text' => $options['title']
//                        ],
//                        'scales' => [
//                            'xAxes' => [
//                                array_replace(
//                                    [
//                                        // 'stacked' => true
//                                    ],
//                                    $options['timeseries'] ? [
//                                        'type' => 'time',
//                                        'time' => ['unit' => 'month']
//                                    ] : []),
//                            ],
//                            'yAxes' => [
//                                // 'stacked' => true,
//                                'ticks' => [
//                                    'suggestedMin' => 0
//                                ]
//                            ]
//                        ]
//                    ]
//                ]
//            ]) . implode("\n", $lostAndFound);
//    }
//
//    /**
//     * @param $option
//     * @param $chartOptions
//     * @return array
//     */
//    protected function parseKLChartDatasetOptions($option, $chartOptions): array
//    {
//        if (!is_array($option)) {
//            $option = [
//                'title' => (string)$option
//            ];
//        }
//
//        $options = [
//            'backgroundColor' => $this->klChartColors[$chartOptions['datasetCount'] % count($this->klChartColors)],
//            'borderColor' => $this->klChartColors[$chartOptions['datasetCount'] % count($this->klChartColors)],
//            'label' => $option['title'] ?? $option['label'],
//        ];
//
//        switch ($chartOptions['type']) {
//            case 'line':
//                $options['fill'] = false;
//                break;
//
//            case 'pie':
//            case 'area':
//            case 'bar':
//            case 'radar':
//            case 'doughnut':
//            case 'polar-area':
//                break;
//        }
//
//        return $options;
//    }
//
//    protected $klChartColors = [
//        '#d32f2f',
//        '#512da8',
//        '#0288d1',
//        '#388e3c',
//        '#ffa000',
//        '#5d4037',
//
//        '#c2185b',
//        '#d303f9f',
//        '#0097a7',
//        '#689f38',
//        '#f57c00',
//        '#616161',
//
//        '#7b1fa2',
//        '#1976d2',
//        '#00796b',
//        '#afb42b',
//        '#e64a19',
//        '#455a64'
//    ];
//
//    /**
//     * @param array $children
//     * @param $option
//     * @param array $tag
//     * @param array $options
//     * @return array
//     */
//    protected function renderTagKLChartDataset(array $children, $option, array $tag, array &$options): array
//    {
//        $options['chartOptions']['datasetCount']++;
//        $value = $this->renderSubTreePlain($children);
//        $datasetOptions = $this->parseKLChartDatasetOptions($option, $options['chartOptions']);
//
//        $timeseries = false;
//
//        $values = array_map(function ($value) use (&$timeseries) {
//            if (strpos($value, '|') !== false) {
//                $values = explode('|', $value);
//                $timeseries = true;
//                return [
//                    'y' => intval($values[1]),
//                    't' => date('c', strtotime($values[0]))
//                ];
//            } else {
//                return intval($value);
//            }
//        }, explode(';', $value));
//
//        $options['chartOptions']['timeseries'] |= $timeseries;
//        $datasetOptions['data'] = $values;
//        return $datasetOptions;
//    }
}