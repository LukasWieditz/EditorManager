<?php

/*!
 * KL/EditorManager/BbCode/RuleSet.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\BbCode;

/**
 * Class RuleSet
 * @package KL\EditorManager\BbCode
 */
/**
 * Class RuleSet
 * @package KL\EditorManager\XF\BbCode
 */
class RuleSet extends XFCP_Ruleset
{
    /**
     * Push new tags to default ruleset array.
     */
    public function addDefaultTags()
    {
        parent::addDefaultTags();

//        $tags = [
//            'bgcolor' => [
//                'hasOption' => true,
//                'optionMatch' => '/^(rgb\(\s*\d+%?\s*,\s*\d+%?\s*,\s*\d+%?\s*\)|#[a-f0-9]{6}|#[a-f0-9]{3}|[a-z]+)$/i'
//            ],
//            'video' => ['stopAutoLink' => true],
//            'audio' => ['stopAutoLink' => true],
//            'justify' => [],
//            'hide' => [],
//            'hideposts' => [],
//            'hidereply' => [],
//            'hidethanks' => [],
//            'hidereplythanks' => [],
//            'hidegroup' => ['hasOption' => true],
//            'sup' => [],
//            'sub' => [],
//            'parsehtml' => ['stopAutoLink' => true],
//            'img' => [
//                "supportOptionKeys" => RuleSet::OPTION_KEYS_BOTH,
//                "plain" => true,
//                "stopSmilies" => true,
//                "stopAutoLink" => true
//            ]
//        ];
//
//        foreach ($tags as $name => $options) {
//            $this->addTag($name, $options);
//        }
//
//        /** Load aliases */
//        /** @noinspection PhpUndefinedMethodInspection */
//        foreach (\XF::repository('KL\EditorManager:BbCodes')->getBbCodeSettings() as $bbCode => $config) {
//            switch ($bbCode) {
//                case 'bold':
//                    $bbCode = 'b';
//                    break;
//
//                case 'italic':
//                    $bbCode = 'i';
//                    break;
//
//                case 'underline':
//                    $bbCode = 'u';
//                    break;
//
//                case 'strike':
//                    $bbCode = 's';
//                    break;
//
//                case 'image':
//                    $bbCode = 'img';
//                    break;
//
//                default:
//                    break;
//            }
//
//            if (isset($this->tags[$bbCode])) {
//                foreach ($config->aliases as $alias) {
//                    $this->addTag(strtolower($alias), $this->tags[$bbCode]);
//                }
//            }
//        }
    }
}