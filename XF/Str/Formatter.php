<?php

/*!
 * KL/EditorManager/Str/Formatter.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Str;

class Formatter extends XFCP_Formatter
{
    /**
     * Ugly function override, might be a source of incompatibility.
     * @param $bbCode
     * @param $context
     * @return string
     */
    public function getBbCodeForQuote($bbCode, $context)
    {
        $bbCodeContainer = \XF::app()->bbCode();

        $processor = $bbCodeContainer->processor()
            ->addProcessorAction('quotes', $bbCodeContainer->processorAction('quotes'))
            ->addProcessorAction('censor', $bbCodeContainer->processorAction('censor'))
            ->addProcessorAction('stripHide', $bbCodeContainer->processorAction('\KL\EditorManager:StripHide'));

        return trim($processor->render($bbCode, $bbCodeContainer->parser(), $bbCodeContainer->rules($context)));
    }


    /**
     * Strips hide BB codes from snipptes to prevent them from being rendered plain.
     * @param $string
     * @param int $maxLength
     * @param array $options
     * @return mixed|string
     */
    public function snippetString($string, $maxLength = 0, array $options = [])
    {
        $string = preg_replace("#\[(HIDE(?:REPLY|POSTS|THANKS|REPLYTHANKS){0,1})].*?\[\/\g1]#si", \XF::phrase('kl_em_hidden_content'), $string);
        $string = parent::snippetString($string, $maxLength, $options);
        return $string;
    }
}