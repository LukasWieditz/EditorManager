<?php

/*!
 * KL/EditorManager/XF/Finder/Post.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Finder;

/**
 * Class Post
 * @package KL\EditorManager\XF\Finder
 */
class Post extends XFCP_Post
{
    /**
     * @return Post
     */
    public function whereContainsKLHideBbCode(): Post
    {
        return $this
            ->whereSQL("LOWER(xf_post.message) LIKE '%[hide%'");
    }
}
