<?php

/*!
 * KL/EditorManager/Setup/Patch200010.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Setup;

use XF;
use XF\Repository\Option;

/**
 * Trait Patch2000010
 * @package KL\EditorManager\Setup
 */
trait Patch2000010
{
    /**
     * Add heading and hr BB codes to default enabled BB codes
     */
    public function upgrade2000010Step1(): void
    {
        /** @var Option $optionRepo */
        $optionRepo = XF::repository('XF:Option');

        $optionValue = XF::options()->klEMEnabledBBCodes;
        $optionValue['hr'] = 1;
        $optionValue['heading'] = 1;

        $optionRepo->updateOption('klEMEnabledBBCodes', $optionValue);
    }

    /**
     * Add heading and hr BB codes to default enabled BB codes
     * @param array $stepParams
     */
    public function upgrade2000010Step2(array $stepParams) : void
    {
        $position = empty($stepParams[0]) ? 0 : $stepParams[0];
        $this->entityColumnsToJson('KL\EditorManager:BbCode', ['user_criteria'], $position, $stepParams);
    }

    /**
     * Add heading and hr BB codes to default enabled BB codes
     * @param array $stepParams
     */
    public function upgrade2000010Step3(array $stepParams) : void
    {
        $position = empty($stepParams[0]) ? 0 : $stepParams[0];
        $this->entityColumnsToJson('KL\EditorManager:SpecialCharacterGroup', ['user_criteria'], $position, $stepParams);
    }

    /**
     * Add heading and hr BB codes to default enabled BB codes
     * @param array $stepParams
     */
    public function upgrade2000010Step4(array $stepParams) : void
    {
        $position = empty($stepParams[0]) ? 0 : $stepParams[0];
        $this->entityColumnsToJson('KL\EditorManager:Template', ['user_criteria'], $position, $stepParams);
    }

}