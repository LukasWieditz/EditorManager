<?php

namespace KL\EditorManager\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null referrer_id
 * @property int audio_id
 * @property string referrer_hash
 * @property string referrer_url
 * @property int hits
 * @property int first_date
 * @property int last_date
 *
 * RELATIONS
 * @property \KL\EditorManager\Entity\AudioProxy Audio
 */
class AudioProxyReferrer extends Entity
{
	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_kl_em_audio_proxy_referrer';
		$structure->shortName = 'KL\EditorManager:AudioProxyReferrer';
		$structure->primaryKey = 'referrer_id';
		$structure->columns = [
			'referrer_id' => ['type' => self::UINT, 'nullable' => true, 'autoIncrement' => true],
			'audio_id' => ['type' => self::UINT, 'required' => true],
			'referrer_hash' => ['type' => self::STR, 'maxLength' => 32, 'required' => true],
			'referrer_url' => ['type' => self::STR, 'required' => true],
			'hits' => ['type' => self::UINT, 'default' => 0],
			'first_date' => ['type' => self::UINT, 'default' => \XF::$time],
			'last_date' => ['type' => self::UINT, 'default' => \XF::$time],
		];
		$structure->getters = [];
		$structure->relations = [
			'Audio' => [
				'entity' => 'KL\EditorManager:AudioProxy',
				'type' => self::TO_ONE,
				'conditions' => 'audio_id',
				'primary' => true
			],
		];

		return $structure;
	}
}