<?php

/*!
 * KL/EditorManager/XF/Template/Templater.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Template;

use KL\EditorManager\Entity\CustomEmote;
use XF;
use XF\App;
use XF\Language;

/**
 * Class Templater
 * @package KL\EditorManager\XF\Template
 */
class Templater extends XFCP_Templater
{
    /**
     * Templater constructor.
     * @param App $app
     * @param Language $language
     * @param $compiledPath
     */
    public function __construct(App $app, Language $language, $compiledPath)
    {
        parent::__construct($app, $language, $compiledPath);
        $this->addFunction('kl_em_custom_emote', 'fnKlEmCustomEmote');
    }

    /**
     * @param $templater
     * @param $escape
     * @param CustomEmote $emote
     * @return string
     * @noinspection PhpUnusedParameterInspection
     */
    public function fnKlEmCustomEmote($templater, &$escape, CustomEmote $emote): string
    {
        $escape = false;

        $replacement = ':' . $emote->Prefix->prefix . $emote->replacement . ':';

        $options = XF::options();
        $width = $options->kleditormanager_customEmoteWidth;
        $height = $options->kleditormanager_customEmoteHeight;


        return '<img src="' . $emote->getEmoteUrl() . '" class="kl-em-emote kl-em-emote--sprite kl-em-emote--sprite' . $emote->emote_id . '" alt="' . $replacement
            . '" title="' . $emote->title . '    ' . $replacement . '" style="width: ' . $width . 'px; height: ' . $height . 'px; background-size: contain;"'
            . 'data-shortname="' . $replacement . '" />';
    }
}
