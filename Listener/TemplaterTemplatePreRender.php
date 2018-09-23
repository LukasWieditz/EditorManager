<?php

namespace KL\EditorManager\Listener;

use XF\Template\Templater;

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
            $webfonts = isset($templater->pageParams['webfonts']) ? $templater->pageParams['webfonts'] : [];

            $gfonts = array_unique(array_merge($gfonts, $webfonts));

            $params['em_gfonts'] = $gfonts ? 'https://fonts.googleapis.com/css?family=' . join('|', $gfonts) : false;
            $params['em_typekit'] = $typekit;
            $params['em_webtype'] = $webtype;
            $params['em_fonts'] = $fonts;
            $params['em_serverFonts'] = $serverFonts;
        }
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
        /* Create Repository */
        $em = \XF::app()->em();

        /* Push bb code configuration to template */
        $options = \XF::app()->options();
        $params['enabled_bb_codes'] = $options['klEMEnabledBBCodes'];
        $params['max_font_size'] = count(explode(', ', $options['klEMFontSizes'])) + 1;
        $params['hide'] = '[HIDE' . strtoupper($options['klEMDefaultHide']) . ']';
    }

    /**
     * Compute editor configutarion and push to template
     *
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function editor(Templater $templater, &$type, &$template, array &$params)
    {
        /* Get entity manager */
        $em = \XF::app()->em();

        $visitor = \XF::visitor();

        /** @var \KL\EditorManager\Repository\Font $repo */
        $repo = $em->getRepository('KL\EditorManager:Font');

        /* Load and process editor fonts */
        $fonts = [];
        foreach ($repo->getFontsCached() as $font) {
            $fonts[str_replace('"', "'", $font->family)] = $font->title;
        }

        /* Load editor configuration */
        $options = \XF::app()->options();

        /** @var \KL\EditorManager\Repository\SpecialChars $charRepo */
        $charRepo = \XF::app()->em()->getRepository('KL\EditorManager:SpecialChars');

        $editorConfig = [
            'enabled_bbcodes' => $options->klEMEnabledBBCodes,
            'font_sizes' => explode(', ', $options->klEMFontSizes),
            'toolbar_layout' => json_decode(trim($options->klEMLayout, "'")),
            'disable_smilies' => (bool)($options->klEMGeneralOptions['disable_smilies']),
            'enable_fullscreen' => (bool)($options->klEMGeneralOptions['enable_fullscreen']),
            'keep_format_on_delete' => (bool)($options->klEMGeneralOptions['keep_format_on_delete']),
            'enable_charcounter' => (bool)($options->klEMGeneralOptions['enable_charcounter']),
            'enable_templates' => true,
            'image_align' => (bool)($options->klEMGeneralOptions['image_align']),
            'special_characters' => (bool)($options->klEMGeneralOptions['special_chars']),
            'special_character_set' => $charRepo->getCharacters(),
            'colors' => explode(',', preg_replace('/\s/', '', $options->klEMColors)),
            'colors_hex' => (bool)($options->klEMHexColor),
            'colors_step' => (int)$options->klEMColorStep,
            'initOnClick' => (bool)($options->klEMLateLoad),
            'pastePlain' => (bool)($options->klEMGeneralOptions['paste_plain'])
        ];

        try {
            $threadPostRoute = false;
            $route = \XF::app()->router('public')->routeToController(\XF::app()->request()->getRoutePath());
            if ($route) {
                $controller = $route->getController();
                if (in_array($controller, \XF::repository('KL\EditorManager:BbCodes')->getValidControllersForHide())) {
                    $threadPostRoute = true;
                }
            }
        } catch (\Exception $e) {
            $threadPostRoute = false;
        }
        if (!$threadPostRoute) {
            $editorConfig['enabled_bbcodes']['hide'] = false;
        }

        /* LiamW Post Macros Patch */
        $addOns = array_keys(\XF::app()->get('addon.cache'));
        if (in_array('LiamW/PostMacros', $addOns)) {
            /** @noinspection PhpUndefinedNamespaceInspection */
            /** @noinspection PhpUndefinedClassInspection */
            /** @var \LiamW\PostMacros\Repository\Macro $macroRepo */
            $macroRepo = \XF::app()->em()->getRepository('LiamW\PostMacros:Macro');
            /** @noinspection PhpUndefinedMethodInspection */
            $macros = $macroRepo->getMacrosForEditor();

            if (!empty($macros)) {
                $editorConfig['enabled_bbcodes']['lwSelectMacro'] = "1";
            }
        }

        $editorConfig['enabled_bbcodes']['gFontFamily'] = $options['klEMExternalFontPolling']
            && $visitor->hasPermission('klEM', 'klEMUseGoogleFonts');

        /* Process removed buttons */
        if (isset($params['removeButtons'])) {
            foreach ($params['removeButtons'] as $button) {
                switch ($button) {
                    case '_align':
                    case '_indent':
                    case 'align':
                        $editorConfig['enabled_bbcodes']['align'] = false;
                        break;

                    case '_image':
                    case 'img':
                    case 'image':
                        $editorConfig['enabled_bbcodes']['img'] = false;
                        break;

                    case '_link':
                    case 'url':
                    case 'link':
                        $editorConfig['enabled_bbcodes']['url'] = false;
                        break;

                    case '_list':
                    case 'list':
                        $editorConfig['enabled_bbcodes']['list'] = false;
                        break;

                    case '_media':
                    case 'media':
                        $editorConfig['enabled_bbcodes']['media'] = false;
                        break;

                    case '_basic':
                        $editorConfig['enabled_bbcodes']['bold'] = false;
                        $editorConfig['enabled_bbcodes']['italic'] = false;
                        $editorConfig['enabled_bbcodes']['underline'] = false;
                        $editorConfig['enabled_bbcodes']['strike'] = false;
                        break;

                    case '_extended':
                        $editorConfig['enabled_bbcodes']['color'] = false;
                        $editorConfig['enabled_bbcodes']['bgcolor'] = false;
                        $editorConfig['enabled_bbcodes']['font'] = false;
                        $editorConfig['enabled_bbcodes']['size'] = false;
                        $editorConfig['enabled_bbcodes']['gFontFamily'] = false;
                        break;

                    case '_block':
                        $editorConfig['enabled_bbcodes']['quote'] = false;
                        $editorConfig['enabled_bbcodes']['code'] = false;
                        $editorConfig['enabled_bbcodes']['spoiler'] = false;
                        $editorConfig['enabled_bbcodes']['hide'] = false;
                        $editorConfig['enabled_bbcodes']['table'] = false;
                        $editorConfig['enabled_bbcodes']['parsehtml'] = false;
                        break;

                    case 'xfCode':
                    case 'code':
                        $editorConfig['enabled_bbcodes']['code'] = false;
                        break;

                    case 'xfQuote':
                    case 'quote':
                        $editorConfig['enabled_bbcodes']['quote'] = false;
                        break;

                    case 'xfInlineCode':
                    case 'inlineCode':
                    case 'icode':
                        $editorConfig['enabled_bbcodes']['icode'] = false;
                        break;

                    case 'xfSpoiler':
                    case 'spoiler':
                        $editorConfig['enabled_bbcodes']['spoiler'] = false;
                        break;

                    case 'templates':
                    case 'klTemplates':
                        $editorConfig['enable_templates'] = false;
                        break;

                    case 'color':
                        $editorConfig['enabled_bbcodes']['color'] = false;
                        $editorConfig['enabled_bbcodes']['bgcolor'] = false;
                        break;

                    case 'fontFamily':
                    case 'font':
                        $editorConfig['enabled_bbcodes']['font'] = false;
                        break;

                    case 'fontSize':
                    case 'size':
                        $editorConfig['enabled_bbcodes']['size'] = false;
                        break;

                    case 'strikeThrough':
                    case 'strike':
                        $editorConfig['enabled_bbcodes']['strike'] = false;
                        break;

                    case 'hide':
                        $editorConfig['enabled_bbcodes']['klEMHide'] = false;
                        $editorConfig['enabled_bbcodes']['klEMHidePosts'] = false;
                        $editorConfig['enabled_bbcodes']['klEMHideReply'] = false;
                        $editorConfig['enabled_bbcodes']['klEMHideReplyThanks'] = false;
                        $editorConfig['enabled_bbcodes']['klEMHideThanks'] = false;
                        break;

                    case '_smilies':
                    case 'xfSmilies':
                        $editorConfig['disable_smilies'] = true;
                        break;

                    default:
                        $editorConfig['enabled_bbcodes'][$button] = false;
                        break;
                }
            }
        }

        $criteria = \XF::finder('KL\EditorManager:BbCode')->fetch();
        foreach ($criteria as $bbCode => $criterion) {
            if (!empty($criterion->user_criteria)) {
                $userCriteria = \XF::app()->criteria('XF:User', $criterion->user_criteria);
                $userCriteria->setMatchOnEmpty(true);

                if (!$userCriteria->isMatched($visitor)) {
                    switch ($bbCode) {
                        case 'font':
                            $editorConfig['enabled_bbcodes']['gFontFamily'] = false;
                            $editorConfig['enabled_bbcodes'][$bbCode] = false;
                            break;

                        case 'url':
                            $editorConfig['enabled_bbcodes'][$bbCode] = false;
                            break;

                        default:
                            $editorConfig['enabled_bbcodes'][$bbCode] = false;
                            break;
                    }
                }
            }
        }

        /* Load Dropdowns */
        /** @var \KL\EditorManager\Repository\Dropdown $repo */
        $repo = $em->getRepository('KL\EditorManager:Dropdown');
        $dropdowns = $repo->getDropdownsCached();

        /* Load Templates */
        /** @var \KL\EditorManager\Repository\Template $templateRepository */
        $templates = false;
        if ($visitor->hasPermission('klEM', 'klEMTemplates')) {
            /** @var \KL\EditorManager\Repository\Template $templateRepository */
            $templateRepository = $em->getRepository('KL\EditorManager:Template');
            $templates = $templateRepository->getTemplatesForUser(\XF::visitor()->user_id,
                $visitor->hasPermission('klEM', 'klEMPublicTemplates'), true);
        }

        $usedButtons = array_unique(
            array_merge(
                $editorConfig['toolbar_layout']->toolbarButtons,
                $editorConfig['toolbar_layout']->toolbarButtonsMD,
                $editorConfig['toolbar_layout']->toolbarButtonsSM,
                $editorConfig['toolbar_layout']->toolbarButtonsXS
            )
        );

        /* Determine available plugins */
        $plugins = [
            'table' => $editorConfig['enabled_bbcodes']['table'],
            'fullscreen' => $editorConfig['enable_fullscreen'],
            'hide' => $editorConfig['enabled_bbcodes']['hide'],
            'fontFamily' => $editorConfig['enabled_bbcodes']['font'],
            'gFontFamily' => $options['klEMExternalFontPolling']
                && \XF::visitor()->hasPermission('klEM', 'klEMUseGoogleFonts')
                && $editorConfig['enabled_bbcodes']['gFontFamily'],
            'fontSize' => $editorConfig['enabled_bbcodes']['size'],
            'link' => $editorConfig['enabled_bbcodes']['url'],
            'image' => $editorConfig['enabled_bbcodes']['img'],
            'align' => $editorConfig['enabled_bbcodes']['align'],
            'justify' => $editorConfig['enabled_bbcodes']['align'],
            'lists' => $editorConfig['enabled_bbcodes']['list'],
            'ispoiler' => $editorConfig['enabled_bbcodes']['ispoiler'],
            'parseHtml' => $editorConfig['enabled_bbcodes']['parsehtml']
                && \XF::visitor()->hasPermission('klEM', 'klEMHTMLBBC'),
            'colors' => $editorConfig['enabled_bbcodes']['color'] || $editorConfig['enabled_bbcodes']['bgcolor'],
            'templates' => $editorConfig['enable_templates']
                && $visitor->hasPermission('klEM', 'klEMTemplates')
                && $templates
                && $templates->count(),
            'draggable' => true,
            'file' => true,
            'bbCode' => true,
            'dropdowns' => count($dropdowns) > 0,
            'charCounter' => $editorConfig['enable_charcounter'],
            'specialCharacters' => $editorConfig['special_characters'],
            'unlinkAll' => array_search('klUnlinkAll', $usedButtons) !== false
        ];

        /* Remove disabled plugins */
        $enabledPlugins = array_keys(array_filter($plugins, function ($plugin) {
            return $plugin;
        }));

        /* Parse toolbar layout */
        self::parseToolbars($editorConfig['toolbar_layout'], $dropdowns, $editorConfig, $plugins);

        $imageEditButtons = [
            'imageReplace', 'imageRemove', '|', 'imageLink', 'linkOpen', 'linkEdit', 'linkRemove'
        ];
        if($options->klEMImageAlignment) {
            array_unshift($imageEditButtons, 'imageAlign', '|');
        }
        if($options->klEMImageDimensions) {
            array_unshift($imageEditButtons, 'imageSize', '|');
        }

        /* Render final config */
        $config = [
            'pluginsEnabled' => $enabledPlugins,
            'fontSize' => $editorConfig['font_sizes'],
            'fontFamily' => $fonts,
            'toolbarButtons' => array_values($editorConfig['toolbar_layout']->toolbarButtons),
            'toolbarButtonsMD' => array_values($editorConfig['toolbar_layout']->toolbarButtonsMD),
            'toolbarButtonsSM' => array_values($editorConfig['toolbar_layout']->toolbarButtonsSM),
            'toolbarButtonsXS' => array_values($editorConfig['toolbar_layout']->toolbarButtonsXS),
            'tableEditButtons' => [
                'tableHeader',
                'tableRemove',
                '|',
                'tableRows',
                'tableColumns',
                'tableCellVerticalAlign',
                'tableCellHorizontalAlign'
            ],
            'colorTypes' => [
                'color' => $editorConfig['enabled_bbcodes']['color'],
                'bgcolor' => $editorConfig['enabled_bbcodes']['bgcolor']
            ],
            'charCounterCount' => $editorConfig['enable_charcounter'],
            'charCounterMode' => $options->klEMCharCounter === 'user' ? $visitor->kl_em_wordcount_mode : $options->klEMCharCounter,
            'keepFormatOnDelete' => $editorConfig['keep_format_on_delete'],
            'imageEditButtons' => $imageEditButtons,
            'imageResize' => $options->klEMImageDimensions,
            'colorsText' => $editorConfig['colors'],
            'colorsBackground' => $editorConfig['colors'],
            'colorsHEXInput' => $editorConfig['colors_hex'],
            'colorsStep' => $editorConfig['colors_step'],
            # Might add this in the future
            # 'toolbarBottom' => true,
            # 'toolbarSticky' => true,
            # 'toolbarStickyOffset' => 50
            # 'toolbarvisibleWithoutSelection' => true,
            # 'toolbarInline' => true
            'initOnClick' => $editorConfig['initOnClick'],
            'pastePlain' => $editorConfig['pastePlain'],
            'specialCharactersSets' => $editorConfig['special_character_set']
        ];

        $params = array_merge($params, [
            'plugins' => $plugins,
            'config' => $config,
            'dropdowns' => $dropdowns,
            'templates' => $templates ? $templateRepository->filterForEditor($templates) : []
        ]);
    }

    /**
     * Strip disabled button from toolbars and dropdowns
     *
     * @param $layouts
     * @param $dropdowns
     * @param $name
     */
    protected static function strip(&$layouts, &$dropdowns, $name)
    {
        foreach ($layouts as &$layout) {
            if (($key = array_search($name, $layout)) !== false) {
                unset($layout[$key]);
            }
        }

        foreach ($dropdowns as $dropdown) {
            if (($key = array_search($name, $dropdown->buttons)) !== false) {
                unset($dropdown->buttons[$key]);
            }
        }
    }

    /**
     * Parse toolbars and remove disabled buttons
     *
     * @param $layout
     * @param $dropdowns
     * @param $config
     * @param $plugins
     */
    protected static function parseToolbars(&$layout, &$dropdowns, $config, $plugins)
    {
        /**
         * Stip BB codes with disabled plugins (checks for them were already performed above).
         */
        if ($config['disable_smilies']) {
            self::strip($layout, $dropdowns, 'xfSmilie');
        }

        if (!$plugins['fullscreen']) {
            self::strip($layout, $dropdowns, 'fullscreen');
        }

        if (!$plugins['specialCharacters']) {
            self::strip($layout, $dropdowns, 'specialCharacters');
        }

        if (!$plugins['gFontFamily']) {
            self::strip($layout, $dropdowns, 'gFontFamily');
        }

        if (!$plugins['colors']) {
            self::strip($layout, $dropdowns, 'color');
        }

        if (!$plugins['hide']) {
            self::strip($layout, $dropdowns, 'klEMHide');
            self::strip($layout, $dropdowns, 'klEMHidePosts');
            self::strip($layout, $dropdowns, 'klEMHideReply');
            self::strip($layout, $dropdowns, 'klEMHideReplyThanks');
            self::strip($layout, $dropdowns, 'klEMHideThanks');
        }

        /**
         * Strip remainind BB codes if they are disabled. Array maps from
         * config name to editor toolbar name.
         */
        $codes = [
            'bold' => 'bold',
            'italic' => 'italic',
            'underline' => 'underline',
            'strike' => 'strikeThrough',
            'font' => 'fontFamily',
            'size' => 'fontSize',
            'url' => 'insertLink',
            'img' => 'insertImage',
            'align' => 'align',
            'list' => 'xfList',
            'table' => 'table',
            'media' => 'xfMedia',
            'quote' => 'xfQuote',
            'spoiler' => 'xfSpoiler',
            'icode' => 'xfInlineCode',
            'ispoiler' => 'xfKLEMiSpoiler',
            'code' => 'xfCode',
            'parsehtml' => 'klEMParseHtml',
            'lwSelectMacro' => 'lwSelectMacro'
        ];

        foreach ($codes as $code => $map) {
            if (!$config['enabled_bbcodes'][$code]) {
                self::strip($layout, $dropdowns, $map);
            }
        }

        /**
         * Remove empty toolbars from the layout.
         */
        foreach ($dropdowns as $dropdown) {
            if (!count($dropdown->buttons)) {
                $arr = [];
                self::strip($layout, $arr, "dropdown-{$dropdown->id}");
            } else {
                /**
                 * Prevents the editor from breaking because json format will render an array with missing
                 * indices as object.
                 */
                $dropdown->buttons = array_values($dropdown->buttons);
            }
        }
    }
}