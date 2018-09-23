<?php

namespace KL\EditorManager\XF\Pub\Controller;

/**
 * Class Editor
 * @package KL\EditorManager\Pub\Controller
 */
class Editor extends XFCP_Editor
{
    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionFindGFont() {
        $q = ltrim($this->filter('q', 'str', ['no-trim']));

        if ($q !== '' && utf8_strlen($q) >= 2)
        {
            /** @var \KL\EditorManager\Finder\GoogleFont $finder */
            $finder = $this->finder('KL\EditorManager:GoogleFont');

            $fonts = $finder
                ->where('font_id', 'like', $finder->escapeLike($q, '%?%'))
                ->active()
                ->fetch(10);
        }
        else
        {
            $fonts = [];
            $q = '';
        }

        $viewParams = [
            'q' => $q,
            'fonts' => $fonts
        ];
        return $this->view('KL\EditorManager:GFont\Find', '', $viewParams);
    }

    /**
     * @param $dialog
     * @return array
     */
    protected function loadDialog($dialog)
    {
        $view = 'XF:Editor\Dialog';
        $template = null;
        $params = [];

        switch ($dialog) {
            case 'ispoiler':
                $template = "editor_dialog_kl_em_ispoiler";
                break;

            case 'gfont':
                /** @var \KL\EditorManager\Finder\GoogleFont $finder */
                $finder = $this->em()->getFinder('KL\EditorManager:GoogleFont');

                $params['fonts'] = $finder->active()->fetch();

                $template = "editor_dialog_kl_em_gfont";
                break;

            default:
                break;
        }

        /* No template catched here, return parent */
        if (is_null($template)) {
            return parent::loadDialog($dialog);
        }

        /* Catched a template, continue with overlay render */
        $data = [
            'dialog' => $dialog,
            'view' => $view,
            'template' => $template,
            'params' => $params
        ];

        $this->app->fire('editor_dialog', [&$data, $this], $dialog);

        return $data;
    }

}