<?php

/*!
 * KL/EditorManager/Repository/CustomEmote.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use KL\EditorManager\Finder\CustomEmote as CustomEmoteFinder;
use KL\EditorManager\XF\Entity\UserProfile;
use XF\Entity\User;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use XF;

class CustomEmote extends Repository
{
    /**
     * Returns a finder for all templates.
     * @return Finder|CustomEmoteFinder
     */
    public function findCustomEmotes(): CustomEmoteFinder
    {
        $finder = $this->finder('KL\EditorManager:CustomEmote');
        $finder->setDefaultOrder('title', 'ASC');
        return $finder;
    }

    /**
     * @param User|null $user
     * @throws XF\PrintableException
     */
    public function rebuildUserCustomEmoteCache(User $user = null): void
    {
        if (!$user) {
            $user = XF::visitor();
        }

        $customEmotes = $this->findCustomEmotes()
            ->forUser($user)
            ->fetch();

        $customEmoteCache = [];
        foreach ($customEmotes as $customEmoteId => $customEmote) {
            $customEmoteCache[$customEmoteId] = $customEmote->toArray();
        }

        /** @var UserProfile $userProfile */
        $userProfile = $user->Profile;
        $userProfile->kl_em_custom_emote_cache = $customEmoteCache;
        $userProfile->save();
    }
}