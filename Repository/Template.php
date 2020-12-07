<?php

/*!
 * KL/EditorManager/Repository/Font.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use KL\EditorManager\XF\Entity\UserOption;
use XF;
use XF\Entity\User;
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
     * @return \KL\EditorManager\Finder\Template|Finder
     */
    public function findTemplates()
    {
        $finder = $this->finder('KL\EditorManager:Template');
        $finder
            ->setDefaultOrder('display_order', 'ASC');

        return $finder;
    }

    /**
     * @param User|null $user
     * @throws XF\PrintableException
     */
    public function rebuildUserTemplateCache(User $user = null): void
    {
        if (!$user) {
            $user = XF::visitor();
        }

        $templates = $this->findTemplates()
            ->forUser($user)
            ->activeOnly()
            ->fetch();

        $templateCache = [];
        foreach ($templates as $templateId => $template) {
            /** @var \KL\EditorManager\Entity\Template $template */
            $templateCache[$templateId] = [
                'title' => $template->title,
                'content' => $template->content
            ];
        }

        /** @var UserOption $userOptions */
        $userOptions = $user->Option;
        $userOptions->kl_em_template_cache = $templateCache;
        $userOptions->save();
    }
}