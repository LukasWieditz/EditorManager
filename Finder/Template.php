<?php


namespace KL\EditorManager\Finder;


use XF\Entity\User;
use XF\Mvc\Entity\Finder;

class Template extends Finder
{
    public function activeOnly(): Template
    {
        return $this->where('active', '=', 1);
    }

    public function publicOnly(): Template
    {
        return $this->where('user_id', '=', 0);
    }

    public function forUser(User $user): Template
    {
        return $this->where('user_id', '=', $user->user_id);
    }
}