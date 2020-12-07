<?php

/*!
 * KL/EditorManager/Setup/Patch1020030.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Setup;

use Exception;
use XF;
use XF\Db\Schema\Alter;
use XF\Db\SchemaManager;
use XF\Entity\EditorDropdown;
use XF\Entity\Phrase;
use XF\Repository\Option;
use XF\Repository\Smilie;

/**
 * Trait Patch1020030
 * @package KL\EditorManager\Setup
 */
trait Patch1020030
{
    /**
     *
     */
    public function upgrade1020031Step1() : void
    {
        $oldValue = XF::options()->klEMLayout;
        $newValue = str_replace("dropdown-", "kl_", str_replace('"|"', '"-vs"', $oldValue));

        /** @var Option $optionRepo */
        $optionRepo = XF::repository('XF:Option');
        $optionRepo->updateOption('editorToolbarConfig', json_decode($newValue, true));
    }

    /**
     * @noinspection SqlResolve
     */
    public function upgrade1020031Step2() : void
    {
        try {
            $klDropdowns = XF::db()->fetchAll('SELECT * FROM xf_kl_em_dropdowns');

            foreach ($klDropdowns as $dropdown) {
                /** @var EditorDropdown $xfDropdown */
                $xfDropdown = XF::em()->create('XF:EditorDropdown');
                $xfDropdown->bulkSet([
                    'icon' => "fa-{$dropdown['icon']}",
                    'active' => true,
                    'buttons' => json_decode($dropdown['buttons']) ?: [],
                    "cmd" => "kl_{$dropdown['dropdown_id']}"
                ]);
                $xfDropdown->save();

                /** @var Phrase $masterTitle */
                $masterTitle = $xfDropdown->getMasterPhrase();
                $masterTitle->phrase_text = $dropdown['title'];
                $masterTitle->save();
            }
        }
        catch (Exception $e) {

        }
    }

    /* DROP xf_kl_em_dropdowns */
    /**
     *
     */
    public function upgrade1020031Step3() : void
    {
        /** @var SchemaManager $schemaManager */
        $schemaManager = $this->schemaManager();
        $schemaManager->dropTable('xf_kl_em_dropdowns');
    }

    /**
     *
     */
    public function upgrade1020031Step4() : void
    {
        XF::db()->insertBulk('xf_option_group_relation', [
            [
                'option_id' => 'emojiStyle',
                'group_id' => 'klEM',
                'display_order' => 100
            ],
            [
                'option_id' => 'emojiSource',
                'group_id' => 'klEM',
                'display_order' => 110
            ],
            [
                'option_id' => 'emojiSource ',
                'group_id' => 'klEM',
                'display_order' => 120
            ],
            [
                'option_id' => 'showEmojiInSmilieMenu',
                'group_id' => 'klEM',
                'display_order' => 130
            ],
        ]);
    }

    /**
     *
     */
    public function upgrade1020093Step1()
    {
        $this->db()->insertBulk('xf_option_group_relation', [
            [
                'option_id' => 'convertMarkdownToBbCode',
                'group_id' => 'klEM',
                'display_order' => 210
            ]
        ]);
    }

    /**
     *
     */
    public function upgrade1030031Step1()
    {
        $this->schemaManager()->alterTable('xf_smilie', function (Alter $table) {
            $table->addColumn('kl_em_active', 'bool')->setDefault(1);
            $table->addColumn('kl_em_user_criteria', 'blob')->nullable();
        });

        /** @var Smilie $smilieRepo */
        $smilieRepo = XF::repository('XF:Smilie');
        $smilieRepo->rebuildSmilieCache();
    }
}