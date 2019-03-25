<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Repository;

use XF\Util\Color;

class Option extends XFCP_Option
{
    public function updateOptions(array $values)
    {
        foreach ($values as $key => $value) {
            if (in_array($key, ['klEMBGColors', 'klEMColors'])) {
                $values[$key] = $this->getKLEMColorValue($key, $value);
            }
        }

        return parent::updateOptions($values);
    }

    public function updateOption($name, $value)
    {
        if (in_array($name, ['klEMBGColors', 'klEMColors'])) {
            $value = $this->getKLEMColorValue($name, $value);
        }

        return parent::updateOption($name, $value);
    }

    protected function getKLEMColorValue($key, $value)
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