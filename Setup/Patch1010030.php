<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Setup;

use XF\Db\Schema\Create;
use XF\Db\Schema\Alter;

/**
 * Trait Patch1010030
 * @package KL\EditorManager\Setup
 */
trait Patch1010030 {

    /* 1.1.0 */
    /* CREATE xf_kl_em_bb_codes */
    /**
     *
     */
    public function upgrade1010031Step1()
    {
        \XF::db()->getSchemaManager()->createTable('xf_kl_em_bb_codes', function (Create $table) {
            $table->addColumn('bb_code_id', 'varbinary', 25);
            $table->addColumn('user_criteria', 'mediumblob');
            $table->addColumn('aliases', 'mediumblob');
            $table->addPrimaryKey('bb_code_id');
        });
    }

    /* CREATE xf_kl_em_bb_codes */
    /**
     *
     */
    public function upgrade1010031Step2()
    {
        \XF::db()->getSchemaManager()->alterTable('xf_kl_em_templates', function (Alter $table) {
            $table->addColumn('user_criteria', 'mediumblob');
        });
    }

    /* CREATE xf_kl_em_special_chars_groups */
    /**
     *
     */
    public function upgrade1010031Step3()
    {
        \XF::db()->getSchemaManager()->createTable('xf_kl_em_special_chars_groups', function (Create $table) {
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
    public function upgrade1010031Step4()
    {
        \XF::db()->getSchemaManager()->createTable('xf_kl_em_special_chars', function (Create $table) {
            $table->addColumn('character_id', 'int')->autoIncrement();
            $table->addColumn('code', 'varchar', 25);
            $table->addColumn('group_id', 'int');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'bool')->setDefault(true);
        });
    }

    /* CREATE xf_kl_em_special_chars */
    /**
     *
     */
    public function upgrade1010032Step1()
    {
        \XF::db()->getSchemaManager()->alterTable('xf_kl_em_bb_codes', function (Alter $table) {
            $table->changeColumn('user_criteria')->nullable();
            $table->changeColumn('aliases')->nullable();
        });
    }

    /* CREATE xf_kl_em_video_proxy */
    /**
     *
     */
    public function upgrade1010033Step1()
    {
        \XF::db()->getSchemaManager()->createTable('xf_kl_em_video_proxy', function (Create $table) {
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
    /**
     *
     */
    public function upgrade1010033Step2()
    {
        \XF::db()->getSchemaManager()->createTable('xf_kl_em_audio_proxy', function (Create $table) {
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
    /**
     *
     */
    public function upgrade1010033Step3()
    {
        \XF::db()->getSchemaManager()->createTable('xf_kl_em_video_proxy_referrer', function (Create $table) {
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
    /**
     *
     */
    public function upgrade1010033Step4()
    {
        \XF::db()->getSchemaManager()->createTable('xf_kl_em_audio_proxy_referrer', function (Create $table) {
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

    /**
     *
     */
    public function upgrade1010072Step1()
    {
        \XF::db()->getSchemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->addColumn('kl_em_wordcount_mode', 'enum', ['letter', 'word'])->setDefault('letter');
        });
    }
}