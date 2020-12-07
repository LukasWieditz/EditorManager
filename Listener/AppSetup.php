<?php

namespace KL\EditorManager\Listener;

use Exception;
use KL\EditorManager\EditorConfig;
use XF;
use XF\App;

/**
 * Class AppSetup
 * @package KL\EditorManager\Listener
 */
class AppSetup
{
    /**
     * @param App $app
     * @throws Exception
     */
    public static function appSetup(App $app)
    {
        $baseClass = EditorConfig::class;
        $extendedClass = XF::extendClass($baseClass);
        $editorConfig = new $extendedClass($app);
        $app->offsetSet('klEmEditorConfig', $editorConfig);
    }
}
