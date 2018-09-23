<?php

namespace KL\EditorManager\Cron;

class GoogleFonts {
    /**
     *
     */
    public static function run() {
        /** @var \KL\EditorManager\Repository\GoogleFonts $repo */
        $repo = \XF::app()->em()->getRepository('KL\EditorManager:GoogleFonts');

        $repo->updateFontList();
    }
}