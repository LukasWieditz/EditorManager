<?php

/*!
 * KL/EditorManager/Setup/Patch200010.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Setup;

use XF;
use XF\Db\Schema\Alter;
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
    public function upgrade2000010Step2(array $stepParams): void
    {
        $position = empty($stepParams[0]) ? 0 : $stepParams[0];
        $this->entityColumnsToJson('KL\EditorManager:BbCode', ['user_criteria'], $position, $stepParams);
    }

    /**
     * Add heading and hr BB codes to default enabled BB codes
     * @param array $stepParams
     */
    public function upgrade2000010Step3(array $stepParams): void
    {
        $position = empty($stepParams[0]) ? 0 : $stepParams[0];
        $this->entityColumnsToJson('KL\EditorManager:SpecialCharacterGroup', ['user_criteria'], $position, $stepParams);
    }

    /**
     * Add heading and hr BB codes to default enabled BB codes
     * @param array $stepParams
     */
    public function upgrade2000010Step4(array $stepParams): void
    {
        $position = empty($stepParams[0]) ? 0 : $stepParams[0];
        $this->entityColumnsToJson('KL\EditorManager:Template', ['user_criteria'], $position, $stepParams);
    }

    /**
     *
     */
    public function upgrade2000010Step5(): void
    {
        $this->schemaManager()->alterTable('xf_user_option', function (Alter $table) {
            $table->addColumn('kl_em_wordcount_mode', 'enum', ['letter', 'word'])->setDefault('letter');
            $table->addColumn('kl_em_template_cache', 'blob')->nullable();
        });
    }

    /**
     * @throws XF\Db\Exception
     */
    public function upgrade2000010Step6(): void
    {
        /** @noinspection SqlWithoutWhere */
        /** @noinspection SqlResolve */
        XF::db()->query('
            UPDATE
                xf_user_option
            LEFT JOIN
                xf_user AS user USING (user_id)
            SET
                xf_user_option.kl_em_wordcount_mode = user.kl_em_wordcount_mode
        ');

        $this->schemaManager()->alterTable('xf_user_option', function (Alter $table) {
            $table->addColumn('kl_em_wordcount_mode', 'enum', ['letter', 'word'])->setDefault('letter');
            $table->addColumn('kl_em_template_cache', 'blob')->nullable();
        });
    }

    /**
     *
     */
    public function upgrade2000010Step7(): void
    {
        $this->schemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->dropColumns(['kl_em_wordcount_mode']);
        });
    }

    /**
     *
     */
    public function upgrade2000010Step8(): void
    {
        $this->schemaManager()->alterTable('xf_kl_em_templates', function (Alter $table) {
            $table->addColumn('page_criteria', 'mediumblob');
        });
    }
}