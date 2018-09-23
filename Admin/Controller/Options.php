<?php

/*!
 * KL/EditorManager/Admin/Controller/Options.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Options extends AbstractController {
    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionIndex() {
        /** @var \KL\EditorManager\Repository\Option $repo */
        $repo = $this->repository('KL\EditorManager:Option');
		$finder = $repo->findOptions();
		$options = $finder->fetch();


		$sizes = $options['font_sizes']->value;
		sort($sizes);

		$viewParams = [
			'font_sizes' => join(', ', $sizes),
			'enabled_bbcodes' => $options['enabled_bbcodes']->value,
			'disable_smilies' => $options['disable_smilies']->value,
			'enable_fullscreen' => $options['enable_fullscreen']->value,
			'hide_default' => $options['hide_default']->value,
            'enable_charcounter' => $options['enable_charcounter']->value,
            'keep_format_on_delete' => $options['keep_format_on_delete']->value
			// 'extended_colors' => $options['extended_colors']->value
		];

		return $this->view('KL\EditorManager:Options', 'kl_em_options', $viewParams);
	}

	public function actionSave() {
		$this->assertPostOnly();

		$options = $this->filter([
			'font_sizes' => 'str',
			'enabled_bbcodes' => 'array',
			'disable_smilies' => 'bool',
			'enable_fullscreen' => 'bool',
			'hide_default' => 'str',
            'enable_charcounter' => 'bool',
			'keep_format_on_delete' => 'bool'
			// 'extended_colors' => 'uint'
		]);
		
		$options['font_sizes'] = array_map("trim", explode(',', $options['font_sizes']));

        /** @var \KL\EditorManager\Repository\Option $repository */
        $repository = $this->repository('KL\EditorManager:Option');
		$repository->updateOptions($options);

		return $this->redirect($this->getDynamicRedirect());
	}
}