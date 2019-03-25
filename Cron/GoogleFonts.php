<?php

namespace KL\EditorManager\Cron;

class GoogleFonts
{
    /**
     * @throws \XF\PrintableException
     */
    public static function run()
    {
        /** @var \KL\EditorManager\Repository\GoogleFonts $repo */
        $repo = \XF::app()->em()->getRepository('KL\EditorManager:GoogleFonts');

        $repo->updateFontList();
    }
}