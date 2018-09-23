<?php

/*!
 * KL/EditorManager/Repository/Font.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

/**
 * Class Template
 * @package KL\EditorManager\Repository
 */
class Template extends Repository
{
    /**
     * Returns a finder for all templates.
     * @return Finder
     */
    public function findTemplates()
    {
        $finder = $this->finder('KL\EditorManager:Template');
        $finder
            ->setDefaultOrder('display_order', 'ASC');

        return $finder;
    }

    /**
     * Returns all editor templates for a given user, including public templates if requested.
     * @param int $userid
     * @param bool $getPublicTemplates
     * @param bool $filterInactive
     * @return \XF\Mvc\Entity\ArrayCollection
     */
    public function getTemplatesForUser($userid = 0, $getPublicTemplates = false, $filterInactive = false)
    {
        $finder = $this->findTemplates();

        if ($getPublicTemplates) {
            $finder->whereOR(['user_id', 0], ['user_id', $userid]);
        } else {
            $finder->where('user_id', $userid);
        }

        if ($filterInactive) {
            $finder->where('active', 1);
        }

        $templates = $finder->order(['user_id', 'display_order'])->fetch();

        if($userid) {
            $visitor = \XF::visitor();
            foreach ($templates as $key => $template) {
                $userCriteria = \XF::app()->criteria('XF:User', $template->user_criteria);
                $userCriteria->setMatchOnEmpty(true);

                if (!$userCriteria->isMatched($visitor)) {
                    unset($templates[$key]);
                }
            }
        }

        return $templates;
    }

    /**
     * Strips all unnecessary data and pre-renders the given templates for editor view.
     * @param $templates
     * @return mixed
     */
    public function filterForEditor($templates)
    {
        $i = 0;
        $bbCode = $this->app()->bbCode();

        $filteredTemplates = [];

        foreach ($templates as &$template) {
            $publicOrPrivate = $template->user_id === 0 ? 'public' : 'private';
            $filteredTemplates[$publicOrPrivate][] = [
                'id' => $i++,
                'title' => $template->title,
                'content' => $bbCode->render($template->content, 'editorHtml', 'editor', $template)
            ];
        }

        return $filteredTemplates;
    }
}