<?php

/*!
 * KL/EditorManager/Repository/SiropuChatData.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

/**
 * Class SiropuChatData
 * @package KL\EditorManager\Repository
 */
class SiropuChatData extends XFCP_SiropuChatData {

    /**
     * Disable additional buttons for Siropus Chat.
     * @return array
     */
    public function getDisabledButtons()
    {
        $buttons = parent::getDisabledButtons();

        $buttons[] = 'hide';
        $buttons[] = 'table';
        $buttons[] = 'templates';

        if(in_array('xfInlineCode', $buttons)) {
            $buttons[] = 'ispoiler';
        }
        return $buttons;
    }
}