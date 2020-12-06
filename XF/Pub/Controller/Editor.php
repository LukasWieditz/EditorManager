<?php

/*!
 * KL/EditorManager/Pub/Controller/Editor.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Pub\Controller;

use KL\EditorManager\Repository\GoogleFont;
use KL\EditorManager\Repository\SpecialChars;
use KL\EditorManager\XF\Repository\Smilie;
use XF\Mvc\Entity\Repository;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\View;

/**
 * Class Editor
 * @package KL\EditorManager\Pub\Controller
 */
class Editor extends XFCP_Editor
{
    /**
     * @return GoogleFont|Repository
     */
    protected function getKLGoogleFontRepo(): GoogleFont
    {
        return $this->repository('KL\EditorManager:GoogleFont');
    }

    /**
     * @return SpecialChars|Repository
     */
    protected function getKLSpecialCharacterRepo(): SpecialChars
    {
        return $this->repository('KL\EditorManager:SpecialChars');
    }

    /**
     * @return View
     */
    public function actionFindGFont(): AbstractReply
    {
        $q = ltrim($this->filter('q', 'str', ['no-trim']));

        if ($q !== '' && utf8_strlen($q) >= 2) {
            $finder = $this->getKLGoogleFontRepo()->findGoogleFonts();

            $fonts = $finder
                ->whereIdLike($finder->escapeLike($q, '%?%'))
                ->active()
                ->fetch(10);
        } else {
            $fonts = [];
            $q = '';
        }

        $viewParams = [
            'q' => $q,
            'fonts' => $fonts
        ];
        return $this->view('KL\EditorManager:GFont\Find', '', $viewParams);
    }

    /**
     * @param $dialog
     * @return array
     */
    protected function loadDialog($dialog): array
    {
        $view = 'XF:Editor\Dialog';
        $template = null;
        $params = [];

        switch ($dialog) {
            case 'gfont':
                $params['fonts'] = $this->getKLGoogleFontRepo()
                    ->findGoogleFonts()
                    ->active()
                    ->fetch();

                $template = "editor_dialog_kl_em_gfont";
                break;

            default:
                break;
        }

        /* No template catched here, return parent */
        if (is_null($template)) {
            return parent::loadDialog($dialog);
        }

        /* Catched a template, continue with overlay render */
        $data = [
            'dialog' => $dialog,
            'view' => $view,
            'template' => $template,
            'params' => $params
        ];

        $this->app->fire('editor_dialog', [&$data, $this], $dialog);

        return $data;
    }

    /**
     * @return View
     */
    public function actionKlEmSpecialChars(): AbstractReply
    {
        $repo = $this->getKLSpecialCharacterRepo();
        $categories = $repo->getCategoriesForList();
        $characters = $repo->getCharactersForList($categories->keys());
        $groupedCharacters = $characters->groupBy('group_id');

        $recent = [];
        $recentlyUsed = $this->request->getCookie('klem_specialcharacter_usage', '');
        if ($recentlyUsed) {
            $recentlyUsed = array_reverse(explode(',', $recentlyUsed));

            foreach ($recentlyUsed as $id) {
                if ($characters->offsetExists($id)) {
                    $recent[$id] = $characters->offsetGet($id);
                }
            }
        }

        $viewParams = [
            'recent' => $recent,
            'groupedCharacters' => $groupedCharacters,
            'categories' => $categories
        ];

        return $this->view('KL\EditorManager:Editor\SpecialCharacters', 'kl_em_editor_special_characters', $viewParams);
    }

    /**
     * @return View
     */
    public function actionKlEmSpecialCharsSearch(): AbstractReply
    {
        $q = ltrim($this->filter('q', 'str', ['no-trim']));

        if ($q !== '' && utf8_strlen($q) >= 2) {
            $results = $this->getKLSpecialCharacterRepo()
                ->getMatchingCharactersByString($q, [
                    'limit' => 20
                ]);
        } else {
            $results = [];
            $q = '';
        }

        $viewParams = [
            'q' => $q,
            'results' => $results
        ];
        return $this->view('KL\EditorManager:Editor\SpecialCharacters\Search',
            'kl_em_editor_special_characters_search_results', $viewParams);
    }

    /**
     * @return AbstractReply
     */
    public function actionSmiliesEmoji(): AbstractReply
    {
        $response = parent::actionSmiliesEmoji();

        if ($response instanceof View) {
            /** @var Smilie $smilieRepo */
            $smilieRepo = $this->repository('XF:Smilie');

            $smilies = $response->getParam('groupedSmilies');

            foreach ($smilies as &$smilieCategory) {
                $smilieRepo->filterSmilies($smilieCategory);
            }
            $response->setParam('groupedSmilies', $smilies);

            $recent = $response->getParam('recent');
            $smilieRepo->filterSmilies($recent);
            $response->setParam('recent', $recent);
        }

        return $response;
    }
}