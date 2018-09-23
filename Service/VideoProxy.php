<?php

namespace KL\EditorManager\Service;

use XF\Service\AbstractService;
use XF\Util\File;

class VideoProxy extends AbstractService
{
	protected $forceRefresh = false;
	protected $maxConcurrent = 10;

	/**
	 * @var \KL\EditorManager\Repository\VideoProxy
	 */
	protected $proxyRepo;

	protected function setup()
	{
		$this->proxyRepo = $this->repository('KL\EditorManager:VideoProxy');
	}

	public function forceRefresh($value = true)
	{
		$this->forceRefresh = (bool)$value;
	}

	public function isRefreshForced()
	{
		return $this->forceRefresh;
	}

	public function getVideo($url)
	{
		$video = $this->proxyRepo->getVideoByUrl($url);
		if ($video)
		{
			if ($this->isRefreshRequired($video))
			{
				$this->refetchVideo($video);
			}
		}
		else
		{
			if ($this->canFetchVideo())
			{
                $video = $this->fetchNewVideo($url);
			}
		}

		return $video;
	}

	protected function isRefreshRequired(\KL\EditorManager\Entity\VideoProxy $video)
	{
		if ($this->forceRefresh)
		{
			return true;
		}

		return $video->isRefreshRequired() && $this->canFetchVideo();
	}

	public function canFetchVideo()
	{
		if ($this->forceRefresh)
		{
			return true;
		}

		$active = $this->proxyRepo->getTotalActiveFetches();
		return ($active < $this->maxConcurrent);
	}

	public function fetchNewVideo($url)
	{
		/** @var \KL\EditorManager\Entity\VideoProxy $video */
        $video = $this->em()->create('KL\EditorManager:VideoProxy');
        $video->url = $url;
        $video->pruned = true;
        $video->is_processing = time(); // may have slept, need to set to now

		try
		{
            $video->save();
		}
		catch (\XF\Db\Exception $e)
		{
			// this is mostly a duplicate key issue
			return null;
		}

		$fetchResults = $this->fetchVideoDataFromUrl($video->url);
		$this->finalizeFromFetchResults($video, $fetchResults);

		return $video;
	}

	public function refetchVideo(\KL\EditorManager\Entity\VideoProxy $video)
	{
        $video->is_processing = time();
        $video->save();

		$fetchResults = $this->fetchVideoDataFromUrl($video->url);
		$this->finalizeFromFetchResults($video, $fetchResults);

		return $video;
	}

	public function testVideoFetch($url)
	{
		$results = $this->fetchVideoDataFromUrl($url);
		if ($results['dataFile'])
		{
			@unlink($results['dataFile']);
			$results['dataFile'] = null;
		}

		return $results;
	}

	protected function fetchVideoDataFromUrl($url)
	{
		$url = $this->proxyRepo->cleanUrlForFetch($url);
		if (!preg_match('#^https?://#i', $url))
		{
			throw new \InvalidArgumentException("URL must be http or https");
		}

		$urlParts = @parse_url($url);

		$validVideo = false;
		$fileName = !empty($urlParts['path']) ? basename($urlParts['path']) : null;
		$mimeType = null;
		$error = null;
		$streamFile = File::getTempDir() . '/' . strtr(md5($url) . '-' . uniqid(), '/\\.', '---') . '.temp';
		$videoProxyMaxSize = $this->app->options()->klEMVideoProxyMaxSize * 1024;

		try
		{
			$options = [
				'headers' => [
					'Accept' => 'video/*,*'
				]
			];
			$limits = [
				'time' => 8,
				'bytes' => $videoProxyMaxSize ?: -1
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
					$fileName = 'video';
				}

				$videoInfo = filesize($streamFile) ? @pathinfo($streamFile) : false;
				if ($videoInfo)
				{
					$videoType = $response->getHeader('content-type');

					$extension = File::getFileExtension($fileName);
					$extensionMap = [
						'video/webm' => ['webm'],
						'video/mp4' => ['mp4']
					];
					if (isset($extensionMap[$videoType]))
					{
						$mimeType = $videoType;
						$validExtensions = $extensionMap[$videoType];
						if (!in_array($extension, $validExtensions))
						{
							$extensionStart = strrpos($fileName, '.');
							$fileName = (
								$extensionStart
									? substr($fileName, 0, $extensionStart)
									: $fileName
								) . '.' . $validExtensions[0];
						}

						$validVideo = true;
					}
					else
					{
						$error = \XF::phraseDeferred('kl_em_video_is_invalid_type');
					}
				}
				else
				{
					$error = \XF::phraseDeferred('kl_em_file_not_a_video');
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

		if (!$validVideo)
		{
			@unlink($streamFile);
		}

		return [
			'valid' => $validVideo,
			'error' => $error,
			'dataFile' => $validVideo ? $streamFile : null,
			'fileName' => $fileName,
			'mimeType' => $mimeType
		];
	}

	protected function finalizeFromFetchResults(\KL\EditorManager\Entity\VideoProxy $video, array $fetchResults)
	{
        $video->is_processing = 0;

		if ($fetchResults['valid'])
		{
			$newVideoPath = $video->getAbstractedVideoPath();

			if (File::copyFileToAbstractedPath($fetchResults['dataFile'], $newVideoPath))
			{
                $video->fetch_date = time();
                $video->file_name = $fetchResults['fileName'];
                $video->file_size = filesize($fetchResults['dataFile']);
                $video->mime_type = $fetchResults['mimeType'];
                $video->pruned = false;
                $video->failed_date = 0;
                $video->fail_count = 0;
			}
			else
			{
                $video->pruned = true;
			}

			@unlink($fetchResults['dataFile']);
		}
		else
		{
            $video->failed_date = time();
            $video->fail_count++;
		}

        $video->save();
	}
}