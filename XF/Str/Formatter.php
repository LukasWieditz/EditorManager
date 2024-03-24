<?php

/*!
 * KL/EditorManager/XF/Str/Formatter.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Str;

use KL\EditorManager\XF\Entity\Smilie;
use XF;
use XF\Entity\User;
use XF\Pub\App;

/**
 * Class Formatter
 * @package KL\EditorManager\XF\Str
 */
class Formatter extends XFCP_Formatter
{
    /**
     * @var User
     */
    protected $klEmContextUser = null;

    /**
     * @return User|null
     */
    public function getKlEmContextUser()
    {
        if(XF::app() instanceof App) {
            return $this->klEmContextUser ?: XF::visitor();
        }

        return null;
    }

    /**
     * @param User|null $klEmContextUser
     */
    public function setKlEmContextUser(?User $klEmContextUser = null): void
    {
        $this->klEmContextUser = $klEmContextUser;
    }

    /**
     * @param $text
     * @return string|string[]|null
     */
    public function replaceSmiliesHtml($text)
    {
        // TODO: Add custom smilie translate

        $cache = &$this->smilieCache;

        $replace = function ($id, $smilie) use (&$cache) {
            if (isset($cache[$id])) {
                return $cache[$id];
            }

            if ($contextUser = $this->getKlEmContextUser()) {
                if ($smilie && isset($smilie['kl_em_user_criteria']) && $smilie['kl_em_user_criteria']) {
                    /** @var Smilie $smilieEntity */
                    $smilieEntity = XF::em()->instantiateEntity('XF:Smilie', $smilie);
                    if (!$smilieEntity->canKLEMUse($error, $contextUser)) {
                        return $smilie['smilieText'][0];
                    }
                }
            }

            $html = $this->getDefaultSmilieHtml($id, $smilie);
            $cache[$id] = $html;
            return $html;
        };

        return $this->replaceSmiliesInText($text, $replace, 'htmlspecialchars');
    }

    /**
     * Ugly function override, might be a source of incompatibility.
     * @param $bbCode
     * @param $context
     * @return string
     */
    public function getBbCodeForQuote($bbCode, $context)
    {
        $bbCodeContainer = XF::app()->bbCode();

        $processor = $bbCodeContainer->processor()
            ->addProcessorAction('quotes', $bbCodeContainer->processorAction('quotes'))
            ->addProcessorAction('censor', $bbCodeContainer->processorAction('censor'))
            ->addProcessorAction('stripHide', $bbCodeContainer->processorAction('\KL\EditorManager:StripHide'));

        return trim($processor->render($bbCode, $bbCodeContainer->parser(), $bbCodeContainer->rules($context)));
    }


    /**
     * Strips hide BB codes from snippets to prevent them from being rendered plain.
     * @param $string
     * @param int $maxLength
     * @param array $options
     * @return string
     */
    public function snippetString($string, $maxLength = 0, array $options = [])
    {
        $string = preg_replace("#\[(HIDE(?:REPLY|POSTS|THANKS|REPLYTHANKS)?)].*?\[/\g1]#si",
            XF::phrase('kl_em_hidden_content'), $string);
        $string = parent::snippetString($string, $maxLength, $options);
        return $string;
    }
}