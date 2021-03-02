<?php /** @noinspection PhpUnusedParameterInspection */

/*!
 * KL/EditorManager/Listener/BbCodeRules.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Listener;

use KL\EditorManager\EditorConfig;
use XF;
use XF\BbCode\RuleSet;

/**
 * Class BbCodeRules
 * @package KL\EditorManager\Listener
 */
class BbCodeRules
{
    /**
     * @param RuleSet $ruleSet
     * @param $context
     * @param $subContext
     */
    public static function extend(RuleSet $ruleSet, $context, $subContext)
    {
        $tags = [
            'bgcolor' => [
                'hasOption' => true,
                'optionMatch' => '/^(rgb\(\s*\d+%?\s*,\s*\d+%?\s*,\s*\d+%?\s*\)|#[a-f0-9]{6}|#[a-f0-9]{3}|[a-z]+)$/i'
            ],
            'fa' => [
                'supportOptionKeys' => $ruleSet::OPTION_KEYS_BOTH,
            ],
            'video' => ['stopAutoLink' => true],
            'audio' => ['stopAutoLink' => true],
            'justify' => [],
            'hide' => [],
            'hideposts' => [],
            'hidereply' => [],
            'hidethanks' => [],
            'hidereplythanks' => [],
            'hidemembers' => [],
            'hidegroup' => ['hasOption' => true],
            'hidetime' => ['hasOption' => true],
            'hidedate' => ['hasOption' => true],
            'sup' => [],
            'sub' => [],
            'parsehtml' => ['stopAutoLink' => true],
            'img' => [
                "supportOptionKeys" => RuleSet::OPTION_KEYS_BOTH,
                "plain" => true,
                "stopSmilies" => true,
                "stopAutoLink" => true
            ],

//            'chart' => [
//                'stopAutoLink' => true,
//                'supportOptionKeys' => RuleSet::OPTION_KEYS_BOTH,
//            ],
//            'chartdata' => [
//                'stopAutoLink' => true,
//                'supportOptionKeys' => RuleSet::OPTION_KEYS_BOTH,
//                'validParents' => [
//                    'chart'
//                ]
//            ]
        ];

        foreach ($tags as $name => $options) {
            $ruleSet->addTag($name, $options);
        }

        $tags = $ruleSet->getTags();

        $editorConfig = EditorConfig::getInstance();
        $bbCodes = $editorConfig->bbCodeSettings();

        /** Load aliases */
        foreach ($bbCodes as $bbCode => $config) {
            switch ($bbCode) {
                case 'bold':
                    $bbCode = 'b';
                    break;

                case 'italic':
                    $bbCode = 'i';
                    break;

                case 'underline':
                    $bbCode = 'u';
                    break;

                case 'strike':
                    $bbCode = 's';
                    break;

                case 'image':
                    $bbCode = 'img';
                    break;

                default:
                    break;
            }

            if (isset($tags[$bbCode])) {
                foreach ($config->aliases as $alias) {
                    $ruleSet->addTag(strtolower($alias), $tags[$bbCode]);
                }
            }
        }
    }
}