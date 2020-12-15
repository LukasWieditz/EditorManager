<?php

namespace KL\EditorManager\XF\Entity;

use KL\EditorManager\Entity\CustomEmotePrefix;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Structure;

/**
 * Class UserProfile
 * @package KL\EditorManager\XF\Entity
 *
 * COLUMNS
 * @property array kl_em_custom_emote_cache
 *
 * GETTERS
 * @property AbstractCollection KLEMCustomEmotes
 *
 * RELATIONS
 * @property CustomEmotePrefix KLEMCustomEmotePrefix
 */
class UserProfile extends XFCP_UserProfile
{
    /**
     * @var AbstractCollection
     */
    protected $klEMCustomEmotes;

    /**
     * @return AbstractCollection
     */
    public function getKLEMCustomEmotes(): AbstractCollection
    {
        if (!$this->klEMCustomEmotes) {
            $cache = $this->kl_em_custom_emote_cache ?: [];
            $em = $this->em();

            $emotes = [];
            foreach ($cache as $emoteId => $emote) {
                $emotes[$emoteId] = $em->instantiateEntity('Kl\EditorManager:CustomEmote', $emote, [
                    'Prefix' => $this->KLEMCustomEmotePrefix
                ]);
            }

            $this->klEMCustomEmotes = $em->getBasicCollection($emotes);
        }

        return $this->klEMCustomEmotes;
    }

    /**
     * @var array
     */
    protected $klEMCustomEmoteMap;

    /**
     * @return array
     */
    public function getKlEmCustomEmoteMap(): array
    {
        if(!$this->klEMCustomEmoteMap) {
            $customEmotes = $this->getKLEMCustomEmotes();

            $map = [];
            foreach($customEmotes as $customEmote) {
                $map[$customEmote->replacement_code] = $customEmote;
            }
        }

        return $this->klEMCustomEmoteMap;
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns += [
            'kl_em_custom_emote_cache' => ['type' => self::JSON_ARRAY, 'default' => []]
        ];

        $structure->getters += [
            'KLEMCustomEmotes' => true,
            'kl_em_custom_emote_map' => true
        ];

        $structure->relations += [
            'KLEMCustomEmotePrefix' => [
                'entity' => 'KL\EditorManager:CustomEmotePrefix',
                'conditions' => 'user_id',
                'type' => self::TO_ONE,
                'primary' => true
            ]
        ];

        $structure->defaultWith = ['KLEMCustomEmotePrefix'];

        return $structure;
    }
}
