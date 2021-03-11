<?php

/*!
 * KL/EditorManager/EditorConfig.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager;

use Doctrine\Common\Cache\CacheProvider;
use Exception;
use KL\EditorManager\Repository\BbCodes;
use KL\EditorManager\Repository\Font;
use KL\EditorManager\Repository\Template;
use KL\EditorManager\XF\Entity\UserOption;
use XF;
use XF\App;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Manager;

/**
 * Class EditorConfig
 * @package KL\EditorManager
 */
class EditorConfig
{
    /**
     * @return EditorConfig
     */
    public static function getInstance() : EditorConfig
    {
        $app = XF::app();
        if(!$app->offsetExists('klEmEditorConfig')) {
            try {
                $extendedClass = XF::extendClass(EditorConfig::class);
            } catch (Exception $e) {
                $extendedClass = EditorConfig::class;
            }
            $app->offsetSet('klEmEditorConfig', new $extendedClass($app));
        }

        return $app->offsetGet('klEmEditorConfig');
    }

    /**
     * @var App
     */
    protected $app;

    /**
     * @var CacheProvider|null
     */
    protected $cache;

    /**
     * EditorConfig constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->cache = $app->cache('KL/EditorManager');
    }

    /**
     * @return Manager
     */
    protected function em(): Manager
    {
        return $this->app->em();
    }

    /**
     * @param string $key
     * @return bool
     */
    public function cacheExists(string $key): bool
    {
        $cache = $this->cache;
        return $cache ? $cache->contains('klem_' . $key) : false;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function cacheFetch($key)
    {
        $cache = $this->cache;
        return $cache ? $cache->fetch('klem_' . $key) : false;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $lifeTime
     */
    public function cacheSave(string $key, $value, int $lifeTime = 3600): void
    {
        $cache = $this->cache;
        $cache ? $cache->save('klem_' . $key, $value, $lifeTime) : null;
    }

    /**
     * @param string $key
     * @param $entities
     * @param int $lifeTime
     */
    public function cacheSaveEntities(string $key, $entities, int $lifeTime = 0): void
    {
        $cache = $this->cache;
        if ($cache) {
            if (empty($entities) || !$entities->first()) {
                $this->cacheSave($key, [
                    'entityType' => null,
                    'values' => []
                ], $lifeTime);
                return;
            }

            $entityType = $entities->first()->structure()->shortName;

            $entities = $entities instanceof AbstractCollection ? $entities->toArray() : $entities;
            foreach ($entities as &$entity) {
                $values = $entity->toArray();
                foreach ($values as &$value) {
                    $value = is_array($value) ? json_encode($value) : $value;
                }
                $entity = $values;
            }

            $this->cacheSave($key, [
                'entityType' => $entityType,
                'values' => $entities
            ], $lifeTime);
        }
    }

    /**
     * @param string $key
     * @return AbstractCollection
     */
    public function cacheFetchEntities(string $key): AbstractCollection
    {
        $cache = $this->cache;
        if ($cache) {
            $cacheValue = $this->cacheFetch($key);
            if ($cacheValue) {
                $entityType = $cacheValue['entityType'];

                if (!$entityType) {
                    return $this->em()->getEmptyCollection();
                }

                $entities = [];
                foreach ($cacheValue['values'] as $entityId => $entityValue) {
                    $entities[$entityId] = $this->em()->instantiateEntity($entityType, $entityValue);
                }

                return $this->em()->getBasicCollection($entities);
            }
        }
        return $this->em()->getEmptyCollection();
    }

    /**
     * @param string $key
     */
    public function cacheDelete(string $key): void
    {
        if ($this->cacheExists($key)) {
            $this->cache->delete('klem_' . $key);
        }
    }

    /**
     * @var AbstractCollection
     */
    protected $fonts;

    /**
     * @return AbstractCollection
     */
    public function fonts(): AbstractCollection
    {
        if (!$this->fonts) {
            if ($this->cacheExists('fonts')) {
                $fonts = $this->cacheFetchEntities('fonts');
            } else {
                /** @var Font $fontRepo */
                $fontRepo = $this->em()->getRepository('KL\EditorManager:Font');
                $fonts = $fontRepo->findFonts()->activeOnly()->fetch();

                if ($this->cache) {
                    $this->cacheSaveEntities('fonts', $fonts);
                }
            }

            $this->fonts = $fonts;
        }

        return $this->fonts;
    }

    /**
     * @var AbstractCollection
     */
    protected $bbCodeSettings;

    /**
     * @return AbstractCollection
     */
    public function bbCodeSettings(): AbstractCollection
    {
        if (!$this->bbCodeSettings) {
            if ($this->cacheExists('bbCodesSettings')) {
                $bbCodes = $this->cacheFetchEntities('bbCodeSettings');
            } else {
                $bbCodes = XF::finder('KL\EditorManager:BbCode')->fetch();

                if ($this->cache) {
                    $this->cacheSaveEntities('bbCodesSettings', $bbCodes);
                }
            }

            $this->bbCodeSettings = $bbCodes;
        }

        return $this->bbCodeSettings;
    }

    /**
     * @return array
     */
    public function hideControllers(): array
    {
        $value = XF::app()->options()->klEMHideControllers;
        return explode("\n", $value);
    }

    /**
     * @var array
     */
    protected $bbCodeStatus;

    /**
     * @return array[]
     */
    public function bbCodeStatus(): array
    {
        if (!$this->bbCodeStatus) {
            $bbCodes = XF::options()->klEMEnabledBBCodes;

            $enabledBbCodes = [];
            $disabledBbCodes = [];
            foreach ($bbCodes as $name => $bbCode) {
                if ($bbCode) {
                    $enabledBbCodes[$name] = true;
                    continue;
                }

                $disabledBbCodes[$name] = true;
            }

            $bbCodes = $this->bbCodeSettings();

            $visitor = XF::visitor();
            foreach ($bbCodes as $bbCodeId => $bbCode) {
                $userCriteria = XF::app()->criteria('XF:User', $bbCode->user_criteria ?: []);
                $userCriteria->setMatchOnEmpty(true);

                if (!$userCriteria->isMatched($visitor)) {
                    // self::removeBbCode($bbCode->bb_code_id, $toolbars, $dropdowns);
                    unset($enabledBbCodes[$bbCode->bb_code_id]);
                    $disabledBbCodes[$bbCode->bb_code_id] = true;
                }
            }

            if (isset($enabledBbCodes['hide'])) {
                try {
                    $threadPostRoute = false;
                    $route = XF::app()->router('public')->routeToController(XF::app()->request()->getRoutePath());
                    if ($route) {
                        $controller = $route->getController();
                        if (in_array($controller, $this->hideControllers())) {
                            $threadPostRoute = true;
                        }
                    }
                } catch (Exception $e) {
                    $threadPostRoute = false;
                }
                if (!$threadPostRoute) {
                    unset($enabledBbCodes['hide']);
                    $disabledBbCodes['hide'] = true;
                }
            }

            $this->bbCodeStatus = [
                'enabled' => $enabledBbCodes,
                'disabled' => $disabledBbCodes
            ];
        }

        return $this->bbCodeStatus;
    }

    /**
     * @var array
     */
    protected $editorPlugins;

    /**
     * @return array
     */
    public function editorPlugins(): array
    {
        if (!$this->editorPlugins) {
            $enabledBbCodes = $this->bbCodeStatus()['enabled'];

            $visitor = XF::visitor();
            $options = XF::options();

            $this->editorPlugins = array_keys(array_filter([
                'align' => isset($enabledBbCodes['align']),
                'bbCode' => true,
                'charCounter' => isset($options->klEMCharCounter) && $options->klEMCharCounter !== 'none',
                'colors' => isset($enabledBbCodes['color']) || isset($enabledBbCodes['bgcolor']),
                'contentPreview' => true,
                'draggable' => true,
                'file' => true,
                'fontFamily' => isset($enabledBbCodes['font']),
                'fontSize' => isset($enabledBbCodes['size']),
                'fullscreen' => true,
                'fontAwesomeSelector' => isset($enabledBbCodes['fa']),
                'gFontFamily' => $visitor->hasPermission('klEM',
                        'klEMUseGoogleFonts') && $options->klEMExternalFontPolling,
                'hide' => isset($enabledBbCodes['hide']),
                'image' => isset($enabledBbCodes['img']),
                'link' => isset($enabledBbCodes['url']),
                'lists' => isset($enabledBbCodes['list']),
                'parseHtml' => isset($enabledBbCodes['parsehtml']),
                'specialCharacters' => true,
                'table' => isset($enabledBbCodes['table']),
                'templates' => $visitor->hasPermission('klEM', 'klEMTemplates'),
                'unlinkAll' => isset($enabledBbCodes['url']),
                'xfInsertGif' => true,
                'xfSmilie' => true,
            ]));
        }

        return $this->editorPlugins;
    }

    /**
     * @var AbstractCollection
     */
    protected $publicTemplates;

    /**
     * @param array $pageParams
     * @return AbstractCollection
     */
    public function publicTemplates(array $pageParams = []): AbstractCollection
    {
        if (!$this->publicTemplates) {
            if ($this->cacheExists('publicTemplates')) {
                $templates = $this->cacheFetchEntities('publicTemplates');
            } else {
                /** @var Template $templateRepository */
                $templateRepository = XF::repository('KL\EditorManager:Template');
                $templates = $templateRepository->findTemplates()
                    ->publicOnly()
                    ->activeOnly()
                    ->fetch();

                if ($this->cache) {
                    $this->cacheSaveEntities('publicTemplates', $templates);
                }
            }

            $visitor = XF::visitor();
            foreach ($templates as $templateId => $template) {
                $userCriteria = XF::app()->criteria('XF:User', $template->user_criteria ?: []);
                $userCriteria->setMatchOnEmpty(true);
                if (!$userCriteria->isMatched($visitor)) {
                    $templates->offsetUnset($templateId);
                    continue;
                }

                $pageCriteria = XF::app()->criteria('XF:Page', $template->page_criteria ?: [], $pageParams);
                $pageCriteria->setMatchOnEmpty(true);
                if (!$pageCriteria->isMatched($visitor)) {
                    $templates->offsetUnset($templateId);
                }
            }

            $this->publicTemplates = $templates;
        }

        return $this->publicTemplates;
    }

    /**
     * @var AbstractCollection
     */
    protected $privateTemplates;

    /**
     * @return AbstractCollection
     */
    public function privateTemplates(): AbstractCollection
    {
        $user = XF::visitor();
        /** @var UserOption $userOption */

        if (!$user->user_id) {
            return $this->em()->getEmptyCollection();
        }

        $userOption = $user->Option;

        if (!$this->privateTemplates) {
            $templateCache = $userOption->kl_em_template_cache;

            if (!empty($templateCache)) {
                $templates = [];
                foreach ($templateCache as $templateId => $templateValues) {
                    $templateValues += [
                        'template_id' => $templateId,
                        'user_id' => $user->user_id
                    ];

                    $templates[$templateId] = $this->em()->instantiateEntity('KL\EditorManager:Template',
                        $templateValues);
                }
                $this->privateTemplates = $this->em()->getBasicCollection($templates);
            } else {
                $this->privateTemplates = $this->em()->getEmptyCollection();
            }
        }

        return $this->privateTemplates;
    }

    /**
     * @var array
     */
    protected $editorTemplates;

    /**
     * @param array $pageParams
     * @return array
     */
    public function editorTemplates(array $pageParams = []): array
    {
        if (!$this->editorTemplates) {
            $visitor = XF::visitor();
            /** @var Template $templateRepository */
            if ($visitor->hasPermission('klEM', 'klEMTemplates')) {
                $templateGroups = [
                    0 => [
                        'title' => XF::phrase('kl_em_template_type.public'),
                        'templates' => $this->publicTemplates($pageParams)->pluckNamed('editor_values')
                    ],
                    1 => [
                        'title' => XF::phrase('kl_em_template_type.private'),
                        'templates' => $this->privateTemplates()->pluckNamed('editor_values')
                    ]
                ];

                foreach($templateGroups as $templateGroupKey => $templateGroup) {
                    if(empty($templateGroup['templates'])) {
                        unset($templateGroups[$templateGroupKey]);
                    }
                }

                $this->editorTemplates = array_values($templateGroups);
            } else {
                $this->editorTemplates = [];
            }
        }
        return $this->editorTemplates;
    }

    /**
     * @return array
     */
    public function editorConfig(): array
    {
        $fonts = [];
        foreach ($this->fonts() as $font) {
            $fonts[str_replace('"', "'", $font->family)] = $font->title;
        }

        $options = XF::options();
        $visitor = XF::visitor();
        /** @var UserOption $visitorOption */
        $visitorOption = $visitor->Option;

        return [
            'pluginsEnabled' => $this->editorPlugins(),

            'initOnClick' => isset($options->klEMGeneralOptions['delay_load']) && (bool)($options->klEMGeneralOptions['delay_load']),
            'keepFormatOnDelete' => isset($options->klEMGeneralOptions['keep_format_on_delete']) && (bool)($options->klEMGeneralOptions['keep_format_on_delete']),
            'pastePlain' => isset($options->klEMGeneralOptions['paste_plain']) && (bool)($options->klEMGeneralOptions['paste_plain']),

            'fontFamily' => $fonts,
            'fontSize' => array_map('trim', explode(',', $options->klEMFontSizes)),

            'colorsText' => explode(',', preg_replace('/\s/', '', $options->klEMColors)),
            'colorsBackground' => explode(',', preg_replace('/\s/', '', $options->klEMBGColors)),
            'colorsHEXInput' => (bool)($options->klEMHexColor),
            'colorsStep' => (int)$options->klEMColorStep,

            'charCounterCount' => in_array('charCounter', $this->editorPlugins()),
            'charCounterMode' => $options->klEMCharCounter === 'user' ? $visitorOption->kl_em_wordcount_mode : $options->klEMCharCounter,

            'tableStyles' => [
                'noborder' => XF::phrase('kl_em_table_style.no_border'),
                'nobackground' => XF::phrase('kl_em_table_style.no_background'),
                'collapse' => XF::phrase('kl_em_table_style.collapse'),
                'alternate' => XF::phrase('kl_em_table_style.alternate_rows'),
                'centered' => XF::phrase('kl_em_table_style.centered'),
                'right' => XF::phrase('kl_em_table_style.right_aligned')
            ],

            'tableEditButtons' => ['tableHeader', 'tableRemove', '|', 'tableRows', 'tableColumns', 'tableStyle']
        ];
    }

    /**
     * @param array $templateParams
     */
    public function filterButtons(array &$templateParams): void
    {
        $toolbars = $templateParams['editorToolbars'];
        $dropdowns = $templateParams['editorDropdowns'];

        $editorConfig = EditorConfig::getInstance();
        $disabledBbCodes = $editorConfig->bbCodeStatus()['disabled'];

        foreach ($disabledBbCodes as $disabledBbCode) {
            $this->removeBbCode($disabledBbCode, $toolbars, $dropdowns);
        }

        foreach ($toolbars as &$toolbar) {
            foreach ($toolbar as &$toolbarGroup) {
                $toolbarGroup['buttons'] = array_values($toolbarGroup['buttons']);
            }
        }
        foreach ($dropdowns as &$dropdown) {
            $dropdown['buttons'] = array_values($dropdown['buttons']);
        }

        $templateParams['editorToolbars'] = $toolbars;
        $templateParams['editorDropdowns'] = $dropdowns;
    }

    /**
     * @param string $code
     * @param array $toolbars
     * @param array $dropdowns
     */
    protected function removeBbCode(string $code, array &$toolbars, array &$dropdowns): void
    {
        /** @var BbCodes $repo */
        $repo = XF::repository('KL\EditorManager:BbCodes');
        $toolbarNames = $repo->shortToButtonDataName($code);

        foreach ($toolbarNames as $toolbarName) {
            foreach ($toolbars as &$toolbar) {
                foreach ($toolbar as &$toolbarGroup) {
                    $toolbarGroup['buttons'] = array_filter($toolbarGroup['buttons'], function ($e) use ($toolbarName) {
                        return $toolbarName != $e && "xfCustom_" . $toolbarName != $e;
                    });
                }
            }

            foreach ($dropdowns as &$dropdown) {
                $dropdown['buttons'] = array_filter($dropdown['buttons'], function ($e) use ($toolbarName) {
                    return $toolbarName != $e && "xfCustom_" . $toolbarName != $e;
                });
            }
        }
    }
}