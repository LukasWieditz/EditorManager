<?php

namespace KL\EditorManager\XF\Template;

use KL\EditorManager\Entity\CustomEmote;
use XF\App;
use XF\Language;

class Templater extends XFCP_Templater
{
    public function __construct(App $app, Language $language, $compiledPath)
    {
        parent::__construct($app, $language, $compiledPath);
        $this->addFunction('kl_em_custom_emote', 'fnKlEmCustomEmote');
    }

    public function fnKlEmCustomEmote($templater, &$escape, CustomEmote $emote)
    {
        $escape = false;

        $replacement = ':' . $emote->Prefix->prefix . $emote->replacement . ':';

        $options = \XF::options();
        $width = $options->kleditormanager_customEmoteWidth;
        $height = $options->kleditormanager_customEmoteHeight;


        return '<img src="' . $emote->getEmoteUrl() . '" class="kl-em-emote kl-em-emote--sprite kl-em-emote--sprite' . $emote->emote_id . '" alt="' . $replacement
            . '" title="' . $emote->title . '    ' . $replacement . '" style="width: ' . $width . 'px; height: ' . $height . 'px; background-size: contain;"'
            . 'data-shortname="' . $replacement . '" />';
    }
}
