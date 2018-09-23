<?php

namespace KL\EditorManager\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null referrer_id
 * @property int video_id
 * @property string referrer_hash
 * @property string referrer_url
 * @property int hits
 * @property int first_date
 * @property int last_date
 *
 * RELATIONS
 * @property \KL\EditorManager\Entity\VideoProxy Video
 */
class VideoProxyReferrer extends Entity
{
	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_kl_em_video_proxy_referrer';
		$structure->shortName = 'KL\EditorManager:VideoProxyReferrer';
		$structure->primaryKey = 'referrer_id';
		$structure->columns = [
			'referrer_id' => ['type' => self::UINT, 'nullable' => true, 'autoIncrement' => true],
			'video_id' => ['type' => self::UINT, 'required' => true],
			'referrer_hash' => ['type' => self::STR, 'maxLength' => 32, 'required' => true],
			'referrer_url' => ['type' => self::STR, 'required' => true],
			'hits' => ['type' => self::UINT, 'default' => 0],
			'first_date' => ['type' => self::UINT, 'default' => \XF::$time],
			'last_date' => ['type' => self::UINT, 'default' => \XF::$time],
		];
		$structure->getters = [];
		$structure->relations = [
			'Video' => [
				'entity' => 'KL\EditorManager:VideoProxy',
				'type' => self::TO_ONE,
				'conditions' => 'video_id',
				'primary' => true
			],
		];

		return $structure;
	}
}