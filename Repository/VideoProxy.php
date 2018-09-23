<?php

namespace KL\EditorManager\Repository;

use XF\Db\DeadlockException;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class VideoProxy extends Repository
{
	/**
	 * @return Finder
	 */
	public function findVideoProxyLogsForList()
	{
		return $this->finder('KL\EditorManager:VideoProxy')->setDefaultOrder('last_request_date', 'DESC');
	}

	/**
	 * @param string $url
	 *
	 * @return null|\KL\EditorManager\Entity\VideoProxy
	 */
	public function getVideoByUrl($url)
	{
		$url = $this->cleanUrlForFetch($url);
		$hash = md5($url);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->finder('KL\EditorManager:VideoProxy')->where('url_hash', $hash)->fetchOne();
	}

	public function getTotalActiveFetches($activeLength = 60)
	{
		return $this->db()->fetchOne("
			SELECT COUNT(*)
			FROM xf_kl_em_video_proxy
			WHERE is_processing >= ?
		", time() - $activeLength);
	}

	public function logVideoView(\KL\EditorManager\Entity\VideoProxy $video)
	{
		$this->db()->query("
			UPDATE xf_kl_em_video_proxy SET
				views = views + 1,
				last_request_date = ?
			WHERE video_id = ?
		", [\XF::$time, $video->video_id]);
	}

	public function logVideoReferrer(\KL\EditorManager\Entity\VideoProxy $video, $referrer)
	{
		if (!preg_match('#^https?://#i', $referrer))
		{
			return false;
		}

		try
		{
			$this->db()->insert('xf_kl_em_video_proxy_referrer', [
				'video_id' => $video->video_id,
				'referrer_hash' => md5($referrer),
				'referrer_url' => $referrer,
				'hits' => 1,
				'first_date' => \XF::$time,
				'last_date' => \XF::$time
			], false, 'hits = hits + 1, last_date = VALUES(last_date)');
		}
		catch (DeadlockException $e)
		{
			// ignore deadlocks here -- we're likely triggering a race condition within MySQL
		}

		return true;
	}

	public function cleanUrlForFetch($url)
	{
		$url = preg_replace('/#.*$/s', '', $url);
		if (preg_match_all('/[^A-Za-z0-9._~:\/?#\[\]@!$&\'()*+,;=%-]/', $url, $matches))
		{
			foreach ($matches[0] AS $match)
			{
				$url = str_replace($match[0], '%' . strtoupper(dechex(ord($match[0]))), $url);
			}
		}
		$url = preg_replace('/%(?![a-fA-F0-9]{2})/', '%25', $url);

		return $url;
	}

	/**
	 * @return \KL\EditorManager\Entity\VideoProxy
	 */
	public function getPlaceholderVideo()
	{
		// TODO: ability to customize path
		$path = \XF::getRootDirectory() . '/styles/editor-manager/missing-video.mp4';

		/** @var \KL\EditorManager\Entity\VideoProxy $video */
		$video = $this->em->create('KL\EditorManager:VideoProxy');
        $video->setAsPlaceholder($path, 'video/mp4', 'missing-video.mp4');

		return $video;
	}

	/**
	 * Prunes videos from the file system cache that have expired
	 *
	 * @param integer|null $pruneDate
	 */
	public function pruneVideoCache($pruneDate = null)
	{
		if ($pruneDate === null)
		{
			if (!$this->options()->klEMVideoCacheTTL)
			{
				return;
			}

			$pruneDate = \XF::$time - (86400 * $this->options()->klEMVideoCacheTTL);
		}

		/** @var \KL\EditorManager\Entity\VideoProxy[] $videos */
		$videos = $this->finder('KL\EditorManager:VideoProxy')
			->where('fetch_date', '<', $pruneDate)
			->where('pruned', 0)
			->where('is_processing', 0)
			->fetch(2000);
		foreach ($videos AS $video)
		{
            $video->prune();
		}
	}

	/**
	 * Prunes unused video proxy log entries.
	 *
	 * @param null|int $pruneDate
	 *
	 * @return int
	 */
	public function pruneVideoProxyLogs($pruneDate = null)
	{
		if ($pruneDate === null)
		{
			$options = $this->options();

			if (!$options->klEMVideoAudioProxyLogLength)
			{
				return 0;
			}
			if (!$options->klEMVideoCacheTTL)
			{
				// we're keeping videos forever - can't prune
				return 0;
			}

			$maxTtl = max($options->klEMVideoAudioProxyLogLength, $options->klEMVideoCacheTTL);
			$pruneDate = \XF::$time - (86400 * $maxTtl);
		}

		// we can only remove logs where we've pruned the video
		return $this->db()->delete('xf_kl_em_video_proxy',
			'pruned = 1 AND last_request_date < ?', $pruneDate
		);
	}

	public function pruneVideoReferrerLogs($pruneDate = null)
	{
		if ($pruneDate === null)
		{
			$options = $this->options();

			if (empty($options->klEMVideoAudioProxyReferrer['length']))
			{
				// we're keeping referrer data forever
				return 0;
			}

			$pruneDate = \XF::$time - (86400 * $options->klEMVideoAudioProxyReferrer['length']);
		}

		return $this->db()->delete('xf_kl_em_video_proxy_referrer',
			'last_date < ?', $pruneDate
		);
	}
}