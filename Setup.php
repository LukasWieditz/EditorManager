<?php

/*!
 * KL/EditorManager/Setup.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager;

use KL\EditorManager\Setup\Patch1010030;
use KL\EditorManager\Setup\Patch1020030;
use KL\EditorManager\Setup\Patch2000010;
use XF;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;
use XF\Repository\Smilie;

/**
 * Class Setup
 * @package KL\EditorManager
 */
class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    /**
     * ----------------
     *   INSTALLATION
     * ----------------
     */

    /**
     *
     */
    public function installStep1()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_fonts')) {
            $schemaManager->createTable(
                'xf_kl_em_fonts',
                function (Create $table) {
                    $table->addColumn('font_id', 'VARBINARY', 25)->primaryKey();
                    $table->addColumn('title', 'VARBINARY', 150);
                    $table->addColumn('type', 'ENUM', ['client', 'upload', 'web']);
                    $table->addColumn('family', 'VARBINARY', 255);
                    $table->addColumn('display_order', 'NUMERIC');
                    $table->addColumn('active', 'BOOL');
                    $table->addColumn('extra_data', 'BLOB');
                }
            );
        }
    }

    /**
     *
     */
    public function installStep2()
    {
        $this->db()->insertBulk('xf_kl_em_fonts', [
            [
                'font_id' => 'arial',
                'title' => 'Arial',
                'type' => 'client',
                'family' => '"Arial"',
                'display_order' => 10,
                'active' => 1,
                'extra_data' => '[]'
            ],
            [
                'font_id' => 'book-antiqua',
                'title' => 'Book Antiqua',
                'type' => 'client',
                'family' => '"Book Antiqua"',
                'display_order' => 20,
                'active' => 1,
                'extra_data' => '[]'
            ],
            [
                'font_id' => 'courier-new',
                'title' => 'Courier New',
                'type' => 'client',
                'family' => '"Courier New"',
                'display_order' => 30,
                'active' => 1,
                'extra_data' => '[]'
            ],
            [
                'font_id' => 'georgia',
                'title' => 'Georgia',
                'type' => 'client',
                'family' => '"Georgia"',
                'display_order' => 40,
                'active' => 1,
                'extra_data' => '[]'
            ],
            [
                'font_id' => 'tahoma',
                'title' => 'Tahoma',
                'type' => 'client',
                'family' => "Tahoma",
                'display_order' => 50,
                'active' => 1,
                'extra_data' => '[]'
            ],
            [
                'font_id' => 'times-new-roman',
                'title' => 'Times New Roman',
                'type' => 'client',
                'family' => '"Times New Roman"',
                'display_order' => 60,
                'active' => 1,
                'extra_data' => '[]'
            ],
            [
                'font_id' => 'trebuchet-ms',
                'title' => 'Trebuchet MS',
                'type' => 'client',
                'family' => '"Trebuchet MS"',
                'display_order' => 70,
                'active' => 1,
                'extra_data' => '[]'
            ],
            [
                'font_id' => 'verdana',
                'title' => 'Verdana',
                'type' => 'client',
                'family' => '"Verdana"',
                'display_order' => 80,
                'active' => 1,
                'extra_data' => '[]'
            ]
        ], true);
    }

    /**
     *
     */
    public function installStep3()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_templates')) {
            $schemaManager->createTable(
                'xf_kl_em_templates',
                function (Create $table) {
                    $table->addColumn('template_id', 'INT', 10)->autoIncrement();
                    $table->addColumn('title', 'VARBINARY', 150);
                    $table->addColumn('content', 'MEDIUMTEXT');
                    $table->addColumn('user_id', 'INT', 10);
                    $table->addColumn('display_order', 'NUMERIC');
                    $table->addColumn('active', 'BOOL');
                    $table->addColumn('extra_data', 'BLOB');
                    $table->addColumn('user_criteria', 'mediumblob');
                    $table->addColumn('page_criteria', 'mediumblob');
                }
            );
        }
    }

    /**
     *
     */
    public function installStep4()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_google_fonts')) {
            $schemaManager->createTable(
                'xf_kl_em_google_fonts',
                function (Create $table) {
                    $table->addColumn('font_id', 'VARCHAR', 100)->primaryKey();
                    $table->addColumn('category', 'VARCHAR', 100);
                    $table->addColumn('variants', 'BLOB')->nullable();
                    $table->addColumn('subsets', 'BLOB')->nullable();
                    $table->addColumn('active', 'BOOL')->setDefault(1);
                }
            );
        }
    }

    /**
     *
     */
    public function installStep5()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_bb_codes')) {
            $schemaManager->createTable('xf_kl_em_bb_codes', function (Create $table) {
                $table->addColumn('bb_code_id', 'varbinary', 25);
                $table->addColumn('user_criteria', 'mediumblob')->nullable();
                $table->addColumn('aliases', 'mediumblob')->nullable();
                $table->addPrimaryKey('bb_code_id');
            });
        }
    }


    /**
     *
     */
    public function installStep6()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_special_chars_groups')) {
            $schemaManager->createTable('xf_kl_em_special_chars_groups', function (Create $table) {
                $table->addColumn('group_id', 'int')->autoIncrement();
                $table->addColumn('user_criteria', 'mediumblob');
                $table->addColumn('display_order', 'int')->setDefault(10);
                $table->addColumn('active', 'bool')->setDefault(true);
            });
        }
    }

    /**
     *
     */
    public function installStep7()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_special_chars')) {
            $schemaManager->createTable('xf_kl_em_special_chars', function (Create $table) {
                $table->addColumn('character_id', 'int')->autoIncrement();
                $table->addColumn('code', 'varchar', 25);
                $table->addColumn('group_id', 'int');
                $table->addColumn('display_order', 'int')->setDefault(10);
                $table->addColumn('active', 'bool')->setDefault(true);
            });
        }
    }

    /**
     *
     */
    public function installStep8()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_audio_proxy')) {
            $schemaManager->createTable('xf_kl_em_audio_proxy', function (Create $table) {
                $table->addColumn('audio_id', 'int')->autoIncrement();
                $table->addColumn('url', 'text');
                $table->addColumn('url_hash', 'varbinary', 32);
                $table->addColumn('file_size', 'int')->setDefault(0);
                $table->addColumn('file_name', 'varchar', 250)->setDefault('');
                $table->addColumn('mime_type', 'varchar', 100)->setDefault('');
                $table->addColumn('fetch_date', 'int')->setDefault(0);
                $table->addColumn('first_request_date', 'int')->setDefault(0);
                $table->addColumn('last_request_date', 'int')->setDefault(0);
                $table->addColumn('views', 'int')->setDefault(0);
                $table->addColumn('pruned', 'int')->setDefault(0);
                $table->addColumn('is_processing', 'int')->setDefault(0);
                $table->addColumn('failed_date', 'int')->setDefault(0);
                $table->addColumn('fail_count', 'smallint', 5)->setDefault(0);
                $table->addUniqueKey('url_hash');
                $table->addKey(['pruned', 'fetch_date']);
                $table->addKey('last_request_date');
                $table->addKey('is_processing');
            });
        }
    }

    /**
     *
     */
    public function installStep9()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_audio_proxy_referrer')) {
            $schemaManager->createTable('xf_kl_em_audio_proxy_referrer', function (Create $table) {
                $table->addColumn('referrer_id', 'int')->autoIncrement();
                $table->addColumn('audio_id', 'int');
                $table->addColumn('referrer_hash', 'varbinary', 32);
                $table->addColumn('referrer_url', 'text');
                $table->addColumn('hits', 'int');
                $table->addColumn('first_date', 'int');
                $table->addColumn('last_date', 'int');
                $table->addUniqueKey(['audio_id', 'referrer_hash'], 'audio_id_hash');
                $table->addKey('last_date');
            });
        }
    }

    /**
     *
     */
    public function installStep10()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_video_proxy')) {
            $schemaManager->createTable('xf_kl_em_video_proxy', function (Create $table) {
                $table->addColumn('video_id', 'int')->autoIncrement();
                $table->addColumn('url', 'text');
                $table->addColumn('url_hash', 'varbinary', 32);
                $table->addColumn('file_size', 'int')->setDefault(0);
                $table->addColumn('file_name', 'varchar', 250)->setDefault('');
                $table->addColumn('mime_type', 'varchar', 100)->setDefault('');
                $table->addColumn('fetch_date', 'int')->setDefault(0);
                $table->addColumn('first_request_date', 'int')->setDefault(0);
                $table->addColumn('last_request_date', 'int')->setDefault(0);
                $table->addColumn('views', 'int')->setDefault(0);
                $table->addColumn('pruned', 'int')->setDefault(0);
                $table->addColumn('is_processing', 'int')->setDefault(0);
                $table->addColumn('failed_date', 'int')->setDefault(0);
                $table->addColumn('fail_count', 'smallint', 5)->setDefault(0);
                $table->addUniqueKey('url_hash');
                $table->addKey(['pruned', 'fetch_date']);
                $table->addKey('last_request_date');
                $table->addKey('is_processing');
            });
        }
    }

    /**
     *
     */
    public function installStep11()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_video_proxy_referrer')) {
            $schemaManager->createTable('xf_kl_em_video_proxy_referrer', function (Create $table) {
                $table->addColumn('referrer_id', 'int')->autoIncrement();
                $table->addColumn('video_id', 'int');
                $table->addColumn('referrer_hash', 'varbinary', 32);
                $table->addColumn('referrer_url', 'text');
                $table->addColumn('hits', 'int');
                $table->addColumn('first_date', 'int');
                $table->addColumn('last_date', 'int');
                $table->addUniqueKey(['video_id', 'referrer_hash'], 'video_id_hash');
                $table->addKey('last_date');
            });
        }
    }

    /**
     *
     */
    public function installStep12()
    {
        $this->schemaManager()->alterTable('xf_user_option', function (Alter $table) {
            $table->addColumn('kl_em_wordcount_mode', 'enum', ['letter', 'word'])->setDefault('letter');
            $table->addColumn('kl_em_template_cache', 'blob')->nullable();
        });
    }

    /**
     *
     */
    public function installStep13()
    {
        $this->db()->insertBulk('xf_option_group_relation', [
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
                'option_id' => 'showEmojiInSmilieMenu',
                'group_id' => 'klEM',
                'display_order' => 130
            ],
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
    public function installStep14()
    {
        $this->schemaManager()->alterTable('xf_smilie', function (Alter $table) {
            $table->addColumn('kl_em_active', 'bool')->setDefault(1);
            $table->addColumn('kl_em_user_criteria', 'blob')->nullable();
        });

        /** @var Smilie $smilieRepo */
        $smilieRepo = XF::repository('XF:Smilie');
        $smilieRepo->rebuildSmilieCache();
    }

    /**
     *
     */
    public function installStep15()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_custom_emote_prefix')) {
            $schemaManager->createTable('xf_kl_em_custom_emote_prefix', function (Create $table) {
                $table->addColumn('prefix_id', 'int')->autoIncrement();
                $table->addColumn('user_id', 'int');
                $table->addColumn('prefix', 'varchar', 10);
            });
        }
    }

    /**
     *
     */
    public function installStep16()
    {
        $schemaManager = $this->schemaManager();
        if (!$schemaManager->tableExists('xf_kl_em_custom_emotes')) {
            $schemaManager->createTable('xf_kl_em_custom_emotes', function (Create $table) {
                $table->addColumn('emote_id', 'int')->autoIncrement();
                $table->addColumn('user_id', 'int')->setDefault(0);
                $table->addColumn('prefix_id', 'text');
                $table->addColumn('title', 'varchar', 100);
                $table->addColumn('replacement', 'varchar', 100);
                $table->addColumn('image_date', 'int')->setDefault(0);
                $table->addColumn('extension', 'enum')->values(['png', 'jpg', 'jpeg', 'gif'])->nullable();
            });
        }
    }

    /**
     *
     */
    public function installStep17()
    {
        $this->schemaManager()->alterTable('xf_user_profile', function (Alter $table) {
            $table->addColumn('kl_em_custom_emote_cache', 'blob')->nullable();
        });
    }

    /**
     * ----------------
     *     UPGRADES
     * ----------------
     */

    /** Patch 1.1.0 **/
    use Patch1010030;

    /** Patch 1.2.0 **/
    use Patch1020030;

    /** Patch 2.0.0 */
    use Patch2000010;

    /**
     * @param $previousVersion
     * @param array $stateChanges
     */
    public function postUpgrade($previousVersion, array &$stateChanges): void
    {
        if ($previousVersion < 2000010) {
            XF::app()->jobManager()->enqueueUnique('klemRebuildUserTemplateCache',
                'KL\EditorManager:UserTemplateCache', [], 0);
        }

        $editorConfig = EditorConfig::getInstance();
        $editorConfig->cacheDelete('publicTemplates');
        $editorConfig->cacheDelete('bbCodesSettings');
        $editorConfig->cacheDelete('fonts');
    }

    /**
     * ----------------
     *  UNINSTALLATION
     * ----------------
     */

    /**
     *
     */
    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_kl_em_fonts');
    }

    /**
     *
     */
    public function uninstallStep2()
    {
        $this->schemaManager()->dropTable('xf_kl_em_templates');
    }

    /**
     *
     */
    public function uninstallStep3()
    {
        $this->schemaManager()->dropTable('xf_kl_em_google_fonts');
    }

    /**
     *
     */
    public function uninstallStep4()
    {
        $this->schemaManager()->dropTable('xf_kl_em_bb_codes');
    }

    /**
     *
     */
    public function uninstallStep5()
    {
        $this->schemaManager()->dropTable('xf_kl_em_special_chars_groups');
    }

    /**
     *
     */
    public function uninstallStep6()
    {
        $this->schemaManager()->dropTable('xf_kl_em_special_chars');
    }

    /**
     *
     */
    public function uninstallStep7()
    {
        $this->schemaManager()->dropTable('xf_kl_em_audio_proxy');
    }

    /**
     *
     */
    public function uninstallStep8()
    {
        $this->schemaManager()->dropTable('xf_kl_em_audio_proxy_referrer');
    }

    /**
     *
     */
    public function uninstallStep9()
    {
        $this->schemaManager()->dropTable('xf_kl_em_video_proxy');
    }

    /**
     *
     */
    public function uninstallStep10()
    {
        $this->schemaManager()->dropTable('xf_kl_em_video_proxy_referrer');
    }

    /**
     *
     */
    public function uninstallStep11()
    {
        $this->schemaManager()->alterTable('xf_user_option', function (Alter $table) {
            $table->dropColumns(['kl_em_wordcount_mode', 'kl_em_template_cache']);
        });
    }

    /**
     *
     */
    public function uninstallStep12()
    {
        $this->schemaManager()->alterTable('xf_user_profile', function (Alter $table) {
            $table->dropColumns(['kl_em_custom_emote_cache']);
        });
    }

    /**
     *
     */
    public function uninstallStep13()
    {
        $this->schemaManager()->alterTable('xf_smilie', function (Alter $table) {
            $table->dropColumns(['kl_em_active', 'kl_em_user_criteria']);
        });
    }


    /**
     *
     */
    public function uninstallStep14()
    {
        $this->schemaManager()->dropTable('xf_kl_em_custom_emotes');
    }

    /**
     *
     */
    public function uninstallStep15()
    {
        $this->schemaManager()->dropTable('xf_kl_em_custom_emote_prefix');
    }
}