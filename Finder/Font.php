<?php


namespace KL\EditorManager\Finder;


use XF\Mvc\Entity\Finder;

class Font extends Finder
{
    public function activeOnly(): Font
    {
        return $this->where('active', '=', 1);
    }
}