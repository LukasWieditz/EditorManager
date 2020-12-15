<?php


namespace KL\EditorManager\Finder;


use XF\Entity\User;
use XF\Mvc\Entity\Finder;

class CustomEmote extends Finder
{
    public function forUser(User $user): CustomEmote
    {
        return $this->where('user_id', '=', $user->user_id);
    }
}