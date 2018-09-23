<?php

namespace KL\EditorManager\Admin\View\Log\AudioProxy;

use XF\Mvc\View;

class Audio extends View
{
	public function renderRaw()
	{
		/** @var \KL\EditorManager\Entity\AudioProxy $audio */
        $audio = $this->params['audio'];

        /** @var \KL\EditorManager\XF\Proxy\Controller $proxyController */
        $proxyController = \XF::app()->proxy()->controller();
		$proxyController->applyKLEMAudioResponseHeaders($this->response, $audio, null);

		if ($audio->isPlaceholder())
		{
			return $this->response->responseFile($audio->getPlaceholderPath());
		}
		else
		{
			$resource = \XF::fs()->readStream($audio->getAbstractedAudioPath());
			return $this->response->responseStream($resource, $audio->file_size);
		}
	}
}