<?php

namespace KL\EditorManager\Repository;

use XF\Db\DeadlockException;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class AudioProxy extends Repository
{
	/**
	 * @return Finder
	 */
	public function findAudioProxyLogsForList()
	{
		return $this->finder('KL\EditorManager:AudioProxy')->setDefaultOrder('last_request_date', 'DESC');
	}

	/**
	 * @param string $url
	 *
	 * @return null|\KL\EditorManager\Entity\AudioProxy
	 */
	public function getAudioByUrl($url)
	{
		$url = $this->cleanUrlForFetch($url);
		$hash = md5($url);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->finder('KL\EditorManager:AudioProxy')->where('url_hash', $hash)->fetchOne();
	}

	public function getTotalActiveFetches($activeLength = 60)
	{
		return $this->db()->fetchOne("
			SELECT COUNT(*)
			FROM xf_kl_em_audio_proxy
			WHERE is_processing >= ?
		", time() - $activeLength);
	}

	public function logAudioView(\KL\EditorManager\Entity\AudioProxy $audio)
	{
		$this->db()->query("
			UPDATE xf_kl_em_audio_proxy SET
				views = views + 1,
				last_request_date = ?
			WHERE audio_id = ?
		", [\XF::$time, $audio->audio_id]);
	}

	public function logAudioReferrer(\KL\EditorManager\Entity\AudioProxy $audio, $referrer)
	{
		if (!preg_match('#^https?://#i', $referrer))
		{
			return false;
		}

		try
		{
			$this->db()->insert('xf_kl_em_audio_proxy_referrer', [
				'audio_id' => $audio->audio_id,
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
	 * @return \KL\EditorManager\Entity\AudioProxy
	 */
	public function getPlaceholderAudio()
	{
		// TODO: ability to customize path
		$path = \XF::getRootDirectory() . '/styles/editor-manager/missing-audio.mp3';

		/** @var \KL\EditorManager\Entity\AudioProxy $audio */
		$audio = $this->em->create('KL\EditorManager:AudioProxy');
        $audio->setAsPlaceholder($path, 'audio/mp4', 'missing-audio.mp3');

		return $audio;
	}

	/**
	 * Prunes audios from the file system cache that have expired
	 *
	 * @param integer|null $pruneDate
	 */
	public function pruneAudioCache($pruneDate = null)
	{
		if ($pruneDate === null)
		{
			if (!$this->options()->klEMAudioCacheTTL)
			{
				return;
			}

			$pruneDate = \XF::$time - (86400 * $this->options()->klEMAudioCacheTTL);
		}

		/** @var \KL\EditorManager\Entity\AudioProxy[] $audios */
		$audios = $this->finder('KL\EditorManager:AudioProxy')
			->where('fetch_date', '<', $pruneDate)
			->where('pruned', 0)
			->where('is_processing', 0)
			->fetch(2000);
		foreach ($audios AS $audio)
		{
            $audio->prune();
		}
	}

	/**
	 * Prunes unused audio proxy log entries.
	 *
	 * @param null|int $pruneDate
	 *
	 * @return int
	 */
	public function pruneAudioProxyLogs($pruneDate = null)
	{
		if ($pruneDate === null)
		{
			$options = $this->options();

			if (!$options->klEMVideoAudioProxyLogLength)
			{
				return 0;
			}
			if (!$options->klEMAudioCacheTTL)
			{
				// we're keeping audios forever - can't prune
				return 0;
			}

			$maxTtl = max($options->klEMVideoAudioProxyLogLength, $options->klEMAudioCacheTTL);
			$pruneDate = \XF::$time - (86400 * $maxTtl);
		}

		// we can only remove logs where we've pruned the audio
		return $this->db()->delete('xf_kl_em_audio_proxy',
			'pruned = 1 AND last_request_date < ?', $pruneDate
		);
	}

	public function pruneAudioReferrerLogs($pruneDate = null)
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

		return $this->db()->delete('xf_kl_em_audio_proxy_referrer',
			'last_date < ?', $pruneDate
		);
	}
}