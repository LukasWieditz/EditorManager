<?php

namespace KL\EditorManager\Entity;

use KL\EditorManager\Repository\CustomEmote as CustomEmoteRepo;
use KL\EditorManager\Service\CustomEmote\Image;
use XF;
use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class CustomEmote
 * @package KL\EditorManager\Entity
 *
 * COLUMNS
 * @property integer emote_id
 * @property integer user_id
 * @property integer prefix_id
 * @property string title
 * @property string replacement
 * @property integer image_date
 * @property string extension
 *
 * RELATIONS
 * @property string replacement_code
 *
 * RELATIONS
 * @property CustomEmotePrefix Prefix
 * @property User User
 */
class CustomEmote extends Entity
{
    /**
     * @return string
     */
    public function getAbstractedPath(): string
    {
        $emoteId = $this->emote_id;

        return sprintf('data://kl_em_custom_emotes/%d/%d.%s',
            floor($emoteId / 1000),
            $emoteId,
            $this->extension
        );
    }

    /**
     * @param bool $canonical
     * @return string
     */
    public function getEmoteUrl($canonical = false): string
    {
        $app = XF::app();
        $group = floor($this->emote_id / 1000);
        return $app->applyExternalDataUrl(
            "kl_em_custom_emotes/{$group}/{$this->emote_id}.{$this->extension}?{$this->image_date}",
            $canonical
        );
    }

    /**
     * @return bool
     */
    public function canUseGif(): bool
    {
        // TODO: Animated emote permissions
        return true;
    }

    /**
     * @return bool
     */
    public function canEdit(): bool
    {
        if ($this->user_id == XF::visitor()->user_id) {
            return true;
        }

        // TODO: Moderator permissions
        return false;
    }

    /**
     *
     */
    protected function _postDelete(): void
    {
        /** @var Image $service */
        $service = $this->app()->service('KL\EditorManager:CustomEmote\Image', $this);
        $service->deleteImageForEmoteDelete();
    }

    /**
     *
     */
    protected function _preSave(): void
    {
        if ($this->isInsert() || $this->isChanged('replacement')) {
            $existingReplacement = $this->finder('KL\EditorManager:CustomEmote')
                ->where('user_id', '=', $this->user_id)
                ->where('replacement', '=', $this->replacement)
                ->where('emote_id', '<>', $this->emote_id)
                ->fetchOne();

            if ($existingReplacement) {
                $this->error(XF::phrase('kl_em_replacement_already_in_use'));
            }
        }
    }

    /**
     * @return CustomEmoteRepo|XF\Mvc\Entity\Repository
     */
    protected function getCustomEmoteRepo(): CustomEmoteRepo
    {
        return $this->repository('KL\EditorManager:CustomEmote');
    }

    /**
     * @throws XF\PrintableException
     */
    protected function _postSave()
    {
        $repo = $this->getCustomEmoteRepo();
        $repo->rebuildUserCustomEmoteCache($this->User);
    }

    /**
     * @return string
     */
    public function getReplacementCode(): string
    {
        $prefix = $this->Prefix;
        return ":{$prefix->prefix}{$this->replacement}:";
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_kl_em_custom_emotes';
        $structure->shortName = 'KL\EditorManager:CustomEmote';
        $structure->primaryKey = 'emote_id';
        $structure->columns = [
            'emote_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'default' => XF::visitor()->user_id],
            'prefix_id' => ['type' => self::STR, 'required' => true],

            'title' => ['type' => self::STR, 'required' => true, 'maxLength' => 100],
            'replacement' => ['type' => self::STR, 'required' => true, 'maxLength' => 100],
            'image_date' => ['type' => self::UINT, 'default' => 0],
            'extension' => ['type' => self::STR, 'allowedValues' => ['png', 'jpg', 'jpeg', 'gif'], 'nullable' => true]
        ];

        $structure->getters = [
            'replacement_code' => true
        ];

        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ],
            'Prefix' => [
                'entity' => 'KL\EditorManager:CustomEmotePrefix',
                'type' => self::TO_ONE,
                'conditions' => 'prefix_id',
                'primary' => true
            ],
        ];

        $structure->defaultWith = ['Prefix'];

        return $structure;
    }
}