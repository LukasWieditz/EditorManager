<?php

/*!
 * KL/EditorManager/Repository/Option.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Option extends Repository
{
    protected $defaultBbCodes = [
        'bold' => 0,
        'italic' => 0,
        'underline' => 0,
        'strike' => 0,
        'color' => 0,
        'bgcolor' => 0,
        'font' => 0,
        'size' => 0,
        'url' => 0,
        'img' => 0,
        'media' => 0,
        'quote' => 0,
        'spoiler' => 0,
        'code' => 0,
        'icode' => 0,
        'align' => 0,
        'list' => 0,
        'table' => 0,
        'ispoiler' => 0,
        'hide' => 0,
        'parsehtml' => 0
    ];

    /**
     * Returns a finder for all editor manager options.
     * @return Finder
     */
    public function findOptions()
    {
        $finder = $this->finder('KL\EditorManager:Option');

        return $finder;
    }

    /**
     * Returns the content of the options cache.
     * @return mixed|null
     */
    public function getOptionsCached()
    {
        if ($cache = \XF::app()->simpleCache()) {
            $data = $cache->getValue('KL/EditorManager', 'kl_em_options');
            if ($data) {
                return $data;
            }
        }

        return $this->rebuildOptionCache();
    }

    /**
     * Rebuilds the option cache.
     * @return mixed
     */
    public function rebuildOptionCache()
    {
        $data = $this->stripData($this->findOptions()->fetch());
        $data['enabled_bbcodes'] = array_merge($this->defaultBbCodes, $data['enabled_bbcodes']);

        $data['enabled_bbcodes']['hidereply'] = $data['enabled_bbcodes']['hide'];
        $data['enabled_bbcodes']['hidereplythanks'] = $data['enabled_bbcodes']['hide'];
        $data['enabled_bbcodes']['hidethanks'] = $data['enabled_bbcodes']['hide'];
        $data['enabled_bbcodes']['hideposts'] = $data['enabled_bbcodes']['hide'];

        if ($cache = \XF::app()->simpleCache()) {
            $cache->setValue('KL/EditorManager', 'kl_em_options', $data);
        }
        return $data;
    }

    /**
     * Updates all ooptions and rebuilds the cache afterwards.
     * @param array $values
     * @return \XF\Mvc\Entity\ArrayCollection
     */
    public function updateOptions(array $values)
    {
        $options = $this->em->findByIds('KL\EditorManager:Option', array_keys($values));

        $this->em->beginTransaction();

        foreach ($options AS $option) {
            $option->value = $values[$option->option_id];
            $option->save();
        }

        $this->em->commit();
        $this->rebuildOptionCache();

        return $options;
    }

    /**
     * Removes all unnecessary data from the options cache and stores options as array.
     * @param $data
     * @return mixed
     */
    private function stripData($data)
    {
        $options = [];

        foreach ($data as $key => $value) {
            $options[$key] = $value->value;
        }

        return $options;
    }
}