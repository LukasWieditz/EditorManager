<?php

/*!
 * KL/EditorManager/Setup.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager;

use KL\EditorManager\Setup\Patch1010030;
use KL\EditorManager\Setup\Patch1020030;
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

    /* CREATE xf_kl_em_fonts */
    /**
     *
     */
    public function installStep1()
    {
        $this->schemaManager()->createTable(
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

    /* INSERT INTO xf_kl_em_fonts */
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
        ]);
    }

    /* CREATE xf_kl_em_templates */
    /**
     *
     */
    public function installStep3()
    {
        $this->schemaManager()->createTable(
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
            }
        );
    }

    /* CREATE xf_kl_em_google_fonts */
    /**
     *
     */
    public function installStep4()
    {
        $this->schemaManager()->createTable(
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

    /* CREATE xf_kl_em_bb_codes */
    /**
     *
     */
    public function installStep5()
    {
        $this->schemaManager()->createTable('xf_kl_em_bb_codes', function (Create $table) {
            $table->addColumn('bb_code_id', 'varbinary', 25);
            $table->addColumn('user_criteria', 'mediumblob')->nullable();
            $table->addColumn('aliases', 'mediumblob')->nullable();
            $table->addPrimaryKey('bb_code_id');
        });
    }

    /* CREATE xf_kl_em_special_chars_groups */
    /**
     *
     */
    public function installStep6()
    {
        $this->schemaManager()->createTable('xf_kl_em_special_chars_groups', function (Create $table) {
            $table->addColumn('group_id', 'int')->autoIncrement();
            $table->addColumn('user_criteria', 'mediumblob');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'bool')->setDefault(true);
        });
    }

    /* CREATE xf_kl_em_special_chars */
    /**
     *
     */
    public function installStep7()
    {
        $this->schemaManager()->createTable('xf_kl_em_special_chars', function (Create $table) {
            $table->addColumn('character_id', 'int')->autoIncrement();
            $table->addColumn('code', 'varchar', 25);
            $table->addColumn('group_id', 'int');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'bool')->setDefault(true);
        });
    }

    /* CREATE xf_kl_em_audio_proxy */
    /**
     *
     */
    public function installStep8()
    {
        $this->schemaManager()->createTable('xf_kl_em_audio_proxy', function (Create $table) {
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

    /* CREATE xf_kl_em_audio_proxy_referrer */
    /**
     *
     */
    public function installStep9()
    {
        $this->schemaManager()->createTable('xf_kl_em_audio_proxy_referrer', function (Create $table) {
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

    /* CREATE xf_kl_em_audio_proxy */
    /**
     *
     */
    public function installStep10()
    {
        $this->schemaManager()->createTable('xf_kl_em_video_proxy', function (Create $table) {
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

    /* CREATE xf_kl_em_video_proxy_referrer */
    /**
     *
     */
    public function installStep11()
    {
        $this->schemaManager()->createTable('xf_kl_em_video_proxy_referrer', function (Create $table) {
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

    /* ALTER xf_user */
    /**
     *
     */
    public function installStep12()
    {
        $this->schemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->addColumn('kl_em_wordcount_mode', 'enum', ['letter', 'word'])->setDefault('letter');
        });
    }

    /* INSERT INTO xf_option_group_relation */
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

    /* ALTER xf_smilie */
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
        $smilieRepo = \XF::repository('XF:Smilie');
        $smilieRepo->rebuildSmilieCache();
    }

    public function installStep15()
    {
        $this->schemaManager()->createTable('kl_em_custom_emote_prefix', function (\XF\Db\Schema\Create $table) {
            $table->addColumn('prefix_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int');
            $table->addColumn('prefix', 'varchar', 10);
        });
    }

    public function installStep16()
    {
        $this->schemaManager()->createTable('kl_em_custom_emotes', function(\XF\Db\Schema\Create $table)
        {
            $table->addColumn('emote_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('prefix_id', 'text');
            $table->addColumn('title', 'varchar', 100);
            $table->addColumn('replacement', 'varchar', 100);
            $table->addColumn('image_date', 'int')->setDefault(0);
            $table->addColumn('extension', 'enum')->values(['png', 'jpg', 'jpeg', 'gif'])->nullable();
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
        $smilieRepo = \XF::repository('XF:Smilie');
        $smilieRepo->rebuildSmilieCache();
    }

    /**
     * ----------------
     *  UNINSTALLATION
     * ----------------
     */

    /* DROP xf_kl_em_fonts */
    /**
     *
     */
    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_kl_em_fonts');
    }

    /* DROP xf_kl_em_templates */
    /**
     *
     */
    public function uninstallStep2()
    {
        $this->schemaManager()->dropTable('xf_kl_em_templates');
    }

    /* DROP xf_kl_em_google_fonts */
    /**
     *
     */
    public function uninstallStep3()
    {
        $this->schemaManager()->dropTable('xf_kl_em_google_fonts');
    }

    /* DROP xf_kl_em_bb_codes */
    /**
     *
     */
    public function uninstallStep4()
    {
        $this->schemaManager()->dropTable('xf_kl_em_bb_codes');
    }

    /* DROP xf_kl_em_special_chars_groups */
    /**
     *
     */
    public function uninstallStep5()
    {
        $this->schemaManager()->dropTable('xf_kl_em_special_chars_groups');
    }

    /* DROP xf_kl_em_special_chars */
    /**
     *
     */
    public function uninstallStep6()
    {
        $this->schemaManager()->dropTable('xf_kl_em_special_chars');
    }

    /* DROP xf_kl_em_audio_proxy */
    /**
     *
     */
    public function uninstallStep7()
    {
        $this->schemaManager()->dropTable('xf_kl_em_audio_proxy');
    }

    /* DROP xf_kl_em_audio_proxy_referrer */
    /**
     *
     */
    public function uninstallStep8()
    {
        $this->schemaManager()->dropTable('xf_kl_em_audio_proxy_referrer');
    }

    /* DROP xf_kl_em_audio_proxy */
    /**
     *
     */
    public function uninstallStep9()
    {
        $this->schemaManager()->dropTable('xf_kl_em_video_proxy');
    }

    /* DROP xf_kl_em_audio_proxy_referrer */
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
        $this->schemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->dropColumns(['kl_em_wordcount_mode']);
        });
    }
}