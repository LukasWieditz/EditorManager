<?php

/*!
 * KL/EditorManager/Setup.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

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

    /* CREATE xf_kl_em_dropdowns */
    public function installStep4()
    {
        $this->schemaManager()->createTable(
            'xf_kl_em_dropdowns',
            function (Create $table) {
                $table->addColumn('dropdown_id', 'INT', 10)->autoIncrement();
                $table->addColumn('title', 'VARCHAR', 50);
                $table->addColumn('icon', 'VARCHAR', 50);
                $table->addColumn('buttons', 'BLOB');
            }
        );
    }

    /* INSERT INTO xf_kl_em_dropdowns */
    public function installStep5()
    {
        $this->db()->insertBulk('xf_kl_em_dropdowns', [
            [
                'dropdown_id' => 1,
                'title' => 'Insert',
                'icon' => 'ellipsis-h',
                'buttons' => '["xfMedia","xfQuote","xfSpoiler","xfKLEMiSpoiler","xfCode","xfInlineCode"]'
            ],
            [
                'dropdown_id' => 2,
                'title' => 'Hide',
                'icon' => 'eye-slash',
                'buttons' => '["klEMHide","klEMHidePosts","klEMHideThanks","klEMHideReply","klEMHideReplyThanks"]'
            ]
        ]);
    }

    /* CREATE xf_kl_em_google_fonts */
    public function installStep6()
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
    public function installStep7()
    {
        $this->schemaManager()->createTable('xf_kl_em_bb_codes', function (Create $table) {
            $table->addColumn('bb_code_id', 'varbinary', 25);
            $table->addColumn('user_criteria', 'mediumblob')->nullable();
            $table->addColumn('aliases', 'mediumblob')->nullable();
            $table->addPrimaryKey('bb_code_id');
        });
    }

    /* CREATE xf_kl_em_special_chars_groups */
    public function installStep8()
    {
        $this->schemaManager()->createTable('xf_kl_em_special_chars_groups', function (Create $table) {
            $table->addColumn('group_id', 'int')->autoIncrement();
            $table->addColumn('user_criteria', 'mediumblob');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'bool')->setDefault(true);
        });
    }

    /* CREATE xf_kl_em_special_chars */
    public function installStep9()
    {
        $this->schemaManager()->createTable('xf_kl_em_special_chars', function (Create $table) {
            $table->addColumn('character_id', 'int')->autoIncrement();
            $table->addColumn('code', 'varchar', 25);
            $table->addColumn('group_id', 'int');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'bool')->setDefault(true);
        });
    }

    /* CREATE xf_kl_em_video_proxy */
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

    /* CREATE xf_kl_em_audio_proxy */
    public function installStep11()
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

    /* CREATE xf_kl_em_video_proxy_referrer */
    public function installStep12()
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

    /* CREATE xf_kl_em_audio_proxy_referrer */
    public function installStep13()
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

    public function installStep14()
    {
        $this->schemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->addColumn('kl_em_wordcount_mode', 'enum', ['letter', 'word'])->setDefault('letter');
        });
    }

    /**
     * ----------------
     *     UPGRADES
     * ----------------
     */

    /* 1.1.0 */
    /* CREATE xf_kl_em_bb_codes */
    public function upgrade1010031Step1()
    {
        $this->schemaManager()->createTable('xf_kl_em_bb_codes', function (Create $table) {
            $table->addColumn('bb_code_id', 'varbinary', 25);
            $table->addColumn('user_criteria', 'mediumblob');
            $table->addColumn('aliases', 'mediumblob');
            $table->addPrimaryKey('bb_code_id');
        });
    }

    /* CREATE xf_kl_em_bb_codes */
    public function upgrade1010031Step2()
    {
        $this->schemaManager()->alterTable('xf_kl_em_templates', function (Alter $table) {
            $table->addColumn('user_criteria', 'mediumblob');
        });
    }

    /* CREATE xf_kl_em_special_chars_groups */
    public function upgrade1010031Step3()
    {
        $this->schemaManager()->createTable('xf_kl_em_special_chars_groups', function (Create $table) {
            $table->addColumn('group_id', 'int')->autoIncrement();
            $table->addColumn('user_criteria', 'mediumblob');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'bool')->setDefault(true);
        });
    }

    /* CREATE xf_kl_em_special_chars */
    public function upgrade1010031Step4()
    {
        $this->schemaManager()->createTable('xf_kl_em_special_chars', function (Create $table) {
            $table->addColumn('character_id', 'int')->autoIncrement();
            $table->addColumn('code', 'varchar', 25);
            $table->addColumn('group_id', 'int');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'bool')->setDefault(true);
        });
    }

    /* CREATE xf_kl_em_special_chars */
    public function upgrade1010032Step1()
    {
        $this->schemaManager()->alterTable('xf_kl_em_bb_codes', function (Alter $table) {
            $table->changeColumn('user_criteria')->nullable();
            $table->changeColumn('aliases')->nullable();
        });
    }

    /* CREATE xf_kl_em_video_proxy */
    public function upgrade1010033Step1()
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

    /* CREATE xf_kl_em_audio_proxy */
    public function upgrade1010033Step2()
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

    /* CREATE xf_kl_em_video_proxy_referrer */
    public function upgrade1010033Step3()
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

    /* CREATE xf_kl_em_audio_proxy_referrer */
    public function upgrade1010033Step4()
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

    public function upgrade1010072Step1()
    {
        $this->schemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->addColumn('kl_em_wordcount_mode', 'enum', ['letter', 'word'])->setDefault('letter');
        });
    }

    /**
     * ----------------
     *  UNINSTALLATION
     * ----------------
     */

    /* DROP xf_kl_em_fonts */
    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_kl_em_fonts');
    }

    /* DROP xf_kl_em_templates */
    public function uninstallStep2()
    {
        $this->schemaManager()->dropTable('xf_kl_em_templates');
    }

    /* DROP xf_kl_em_dropdowns */
    public function uninstallStep3()
    {
        $this->schemaManager()->dropTable('xf_kl_em_dropdowns');
    }

    /* DROP xf_kl_em_google_fonts */
    public function uninstallStep4()
    {
        $this->schemaManager()->dropTable('xf_kl_em_google_fonts');
    }

    /* DROP xf_kl_em_bb_codes */
    public function uninstallStep5()
    {
        $this->schemaManager()->dropTable('xf_kl_em_bb_codes');
    }

    /* DROP xf_kl_em_special_chars_groups */
    public function uninstallStep6()
    {
        $this->schemaManager()->dropTable('xf_kl_em_special_chars_groups');
    }

    /* DROP xf_kl_em_special_chars */
    public function uninstallStep7()
    {
        $this->schemaManager()->dropTable('xf_kl_em_special_chars');
    }

    /* DROP xf_kl_em_video_proxy */
    public function uninstallStep8()
    {
        $this->schemaManager()->dropTable('xf_kl_em_video_proxy');
    }

    /* DROP xf_kl_em_audio_proxy */
    public function uninstallStep9()
    {
        $this->schemaManager()->dropTable('xf_kl_em_audio_proxy');
    }

    /* DROP xf_kl_em_video_proxy_referrer */
    public function uninstallStep10()
    {
        $this->schemaManager()->dropTable('xf_kl_em_video_proxy_referrer');
    }

    /* DROP xf_kl_em_audio_proxy_referrer */
    public function uninstallStep11()
    {
        $this->schemaManager()->dropTable('xf_kl_em_audio_proxy_referrer');
    }

    public function uninstallStep12()
    {
        $this->schemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->dropColumns(['kl_em_wordcount_mode']);
        });
    }
}