<?php

/*!
 * KL/EditorManager/Finder/AbstractProxy.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Finder;

use XF\Mvc\Entity\Finder;

/**
 * Class AbstractProxy
 * @package KL\EditorManager\Finder
 */
abstract class AbstractProxy extends Finder
{
    /**
     * @param string $hash
     * @return AbstractProxy
     */
    public function whereHash(string $hash): AbstractProxy
    {
        return $this->where('url_hash', '=', $hash);
    }

    /**
     * @param int $date
     * @return AbstractProxy
     */
    public function whereFetchedBefore(int $date): AbstractProxy
    {
        return $this->where('fetch_date', '<', $date);
    }

    /**
     * @return AbstractProxy
     */
    public function whereNotProcessing(): AbstractProxy
    {
        return $this->where('processing', '<>', 1);
    }

    /**
     * @return AbstractProxy
     */
    public function whereNotPruned(): AbstractProxy
    {
        return $this->where('pruned', '<>', 1);
    }
}