<?php

namespace KL\EditorManager\Service;

use XF\Service\AbstractService;
use XF\Util\File;

class AudioProxy extends AbstractService
{
	protected $forceRefresh = false;
	protected $maxConcurrent = 10;

	/**
	 * @var \KL\EditorManager\Repository\AudioProxy
	 */
	protected $proxyRepo;

	protected function setup()
	{
		$this->proxyRepo = $this->repository('KL\EditorManager:AudioProxy');
	}

	public function forceRefresh($value = true)
	{
		$this->forceRefresh = (bool)$value;
	}

	public function isRefreshForced()
	{
		return $this->forceRefresh;
	}

	public function getAudio($url)
	{
		$audio = $this->proxyRepo->getAudioByUrl($url);
		if ($audio)
		{
			if ($this->isRefreshRequired($audio))
			{
				$this->refetchAudio($audio);
			}
		}
		else
		{
			if ($this->canFetchAudio())
			{
                $audio = $this->fetchNewAudio($url);
			}
		}

		return $audio;
	}

	protected function isRefreshRequired(\KL\EditorManager\Entity\AudioProxy $audio)
	{
		if ($this->forceRefresh)
		{
			return true;
		}

		return $audio->isRefreshRequired() && $this->canFetchAudio();
	}

	public function canFetchAudio()
	{
		if ($this->forceRefresh)
		{
			return true;
		}

		$active = $this->proxyRepo->getTotalActiveFetches();
		return ($active < $this->maxConcurrent);
	}

	public function fetchNewAudio($url)
	{
		/** @var \KL\EditorManager\Entity\AudioProxy $audio */
        $audio = $this->em()->create('KL\EditorManager:AudioProxy');
        $audio->url = $url;
        $audio->pruned = true;
        $audio->is_processing = time(); // may have slept, need to set to now

		try
		{
            $audio->save();
		}
		catch (\XF\Db\Exception $e)
		{
			// this is mostly a duplicate key issue
			return null;
		}

		$fetchResults = $this->fetchAudioDataFromUrl($audio->url);
		$this->finalizeFromFetchResults($audio, $fetchResults);

		return $audio;
	}

	public function refetchAudio(\KL\EditorManager\Entity\AudioProxy $audio)
	{
        $audio->is_processing = time();
        $audio->save();

		$fetchResults = $this->fetchAudioDataFromUrl($audio->url);
		$this->finalizeFromFetchResults($audio, $fetchResults);

		return $audio;
	}

	public function testAudioFetch($url)
	{
		$results = $this->fetchAudioDataFromUrl($url);
		if ($results['dataFile'])
		{
			@unlink($results['dataFile']);
			$results['dataFile'] = null;
		}

		return $results;
	}

	protected function fetchAudioDataFromUrl($url)
	{
		$url = $this->proxyRepo->cleanUrlForFetch($url);
		if (!preg_match('#^https?://#i', $url))
		{
			throw new \InvalidArgumentException("URL must be http or https");
		}

		$urlParts = @parse_url($url);

		$validAudio = false;
		$fileName = !empty($urlParts['path']) ? basename($urlParts['path']) : null;
		$mimeType = null;
		$error = null;
		$streamFile = File::getTempDir() . '/' . strtr(md5($url) . '-' . uniqid(), '/\\.', '---') . '.temp';
		$audioProxyMaxSize = $this->app->options()->klEMAudioProxyMaxSize * 1024;

		try
		{
			$options = [
				'headers' => [
					'Accept' => 'audio/*,*'
				]
			];
			$limits = [
				'time' => 8,
				'bytes' => $audioProxyMaxSize ?: -1
			];
			$response = $this->app->http()->reader()->getUntrusted($url, $limits, $streamFile, $options, $error);
		}
		catch (\Exception $e)
		{
			$response = null;
			$error = $e->getMessage();
		}

		if ($response)
		{
			$response->getBody()->close();

			if ($response->getStatusCode() == 200)
			{
				$disposition = $response->getHeader('Content-Disposition');
				if ($disposition && preg_match('/filename=(\'|"|)(.+)\\1/siU', $disposition, $match))
				{
					$fileName = $match[2];
				}
				if (!$fileName)
				{
					$fileName = 'audio';
				}

				$audioInfo = filesize($streamFile) ? @pathinfo($streamFile) : false;
				if ($audioInfo)
				{
					$audioType = $response->getHeader('content-type');

					$extension = File::getFileExtension($fileName);
					$extensionMap = [
					    'audio/webm' => ['webm'],
						'audio/mp4' => ['mp4'],
                        'audio/mpeg' => ['mp3']
					];
					if (isset($extensionMap[$audioType]))
					{
						$mimeType = $audioType;

						$validExtensions = $extensionMap[$audioType];
						if (!in_array($extension, $validExtensions))
						{
							$extensionStart = strrpos($fileName, '.');
							$fileName = (
								$extensionStart
									? substr($fileName, 0, $extensionStart)
									: $fileName
								) . '.' . $validExtensions[0];
						}

						$validAudio = true;
					}
					else
					{
						$error = \XF::phraseDeferred('kl_em_audio_is_invalid_type');
					}
				}
				else
				{
					$error = \XF::phraseDeferred('kl_em_file_not_a_audio');
				}
			}
			else
			{
				$error = \XF::phraseDeferred('received_unexpected_response_code_x_message_y', [
					'code' => $response->getStatusCode(),
					'message' => $response->getReasonPhrase()
				]);
			}
		}

		if (!$validAudio)
		{
			@unlink($streamFile);
		}

		return [
			'valid' => $validAudio,
			'error' => $error,
			'dataFile' => $validAudio ? $streamFile : null,
			'fileName' => $fileName,
			'mimeType' => $mimeType
		];
	}

    /**
     * @param \KL\EditorManager\Entity\AudioProxy $audio
     * @param array $fetchResults
     * @throws \XF\PrintableException
     */
    protected function finalizeFromFetchResults(\KL\EditorManager\Entity\AudioProxy $audio, array $fetchResults)
	{
        $audio->is_processing = 0;

		if ($fetchResults['valid'])
		{
			$newAudioPath = $audio->getAbstractedAudioPath();

			if (File::copyFileToAbstractedPath($fetchResults['dataFile'], $newAudioPath))
			{
                $audio->fetch_date = time();
                $audio->file_name = $fetchResults['fileName'];
                $audio->file_size = filesize($fetchResults['dataFile']);
                $audio->mime_type = $fetchResults['mimeType'];
                $audio->pruned = false;
                $audio->failed_date = 0;
                $audio->fail_count = 0;
			}
			else
			{
                $audio->pruned = true;
			}

			@unlink($fetchResults['dataFile']);
		}
		else
		{
            $audio->failed_date = time();
            $audio->fail_count++;
		}

        $audio->save();
	}
}