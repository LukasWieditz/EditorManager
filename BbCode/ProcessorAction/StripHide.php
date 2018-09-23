<?php

/*!
 * KL/EditorManager/BbCode/ProcessorAction/StripHide.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\BbCode\ProcessorAction;

use XF\App;
use XF\BbCode\ProcessorAction\FiltererInterface;
use XF\BbCode\ProcessorAction\FiltererHooks;
use XF\Str\Formatter;

/**
 * Class StripHide
 * @package KL\EditorManager\BbCode\ProcessorAction
 */
class StripHide implements FiltererInterface
{
    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * Constructor
     * StripHide constructor.
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Add hooks to filter hide tags.
     * @param FiltererHooks $hooks
     */
    public function addFiltererHooks(FiltererHooks $hooks)
    {
        $hooks->addTagHook('hide', 'filterHide');
        $hooks->addTagHook('hidereply', 'filterHide');
        $hooks->addTagHook('hideposts', 'filterHide');
        $hooks->addTagHook('hidereplythanks', 'filterHide');
        $hooks->addTagHook('hidethanks', 'filterHide');
    }

    /**
     * Replace hide tags with message.
     * @param $tag
     * @param $function
     * @return string
     */
    public function filterHide($tag, $function)
    {
        return '[B][' . \XF::phrase('kl_em_hidden_content') . '][/B]';
    }

    /**
     * @param App $app
     * @return static
     */
    public static function factory(App $app)
    {
        return new static($app->stringFormatter());
    }
}