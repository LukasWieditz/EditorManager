<?php

/*!
 * KL/EditorManager/XF/Repository/Option.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Repository;

use XF\Mvc\Entity\ArrayCollection;
use XF\Util\Color;

/**
 * Class Option
 * @package KL\EditorManager\XF\Repository
 */
class Option extends XFCP_Option
{
    /**
     * @param array $values
     * @return ArrayCollection
     */
    public function updateOptions(array $values): ArrayCollection
    {
        foreach ($values as $key => $value) {
            if (in_array($key, ['klEMBGColors', 'klEMColors'])) {
                $values[$key] = $this->getKLEMColorValue($key);
            }
        }

        return parent::updateOptions($values);
    }

    /**
     * @param $name
     * @param $value
     * @return bool
     */
    public function updateOption($name, $value): bool
    {
        if (in_array($name, ['klEMBGColors', 'klEMColors'])) {
            $value = $this->getKLEMColorValue($name);
        }

        return parent::updateOption($name, $value);
    }

    /**
     * @param $key
     * @param $value
     * @return string
     */
    protected function getKLEMColorValue($key): string
    {
        $request = $this->app()->request();
        $value = array_filter($request->filter($key, 'array-str'));

        foreach ($value as &$color) {
            $color = '#' . Color::rgbToHex(Color::colorToRgb($color));
        }

        $value[] = 'REMOVE';

        return join(',', $value);
    }
}