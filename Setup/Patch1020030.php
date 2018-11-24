<?php

namespace KL\EditorManager\Setup;

use KL\EditorManager\Entity\Dropdown;
use XF\Db\SchemaManager;
use XF\Entity\EditorDropdown;
use XF\Entity\Option;
use XF\Entity\Phrase;

trait Patch1020030
{
    public function upgrade1020031Step1()
    {
        $oldValue = \XF::options()->klEMLayout;
        $newValue = str_replace("dropdown-", "kl_", str_replace('"|"', '"-vs"', $oldValue));

        /** @var \XF\Repository\Option $optionRepo */
        $optionRepo = \XF::repository('XF:Option');
        $optionRepo->updateOption('editorToolbarConfig', json_decode($newValue, true));
    }

    /**
     * @throws \XF\PrintableException
     */
    public function upgrade1020031Step2()
    {
        $klDropdowns = \XF::finder('KL\EditorManager:Dropdown')
            ->fetch();

        foreach($klDropdowns as $dropdown) {
            /** @var Dropdown $dropdown */
            /** @var EditorDropdown $xfDropdown */
            $xfDropdown = \XF::em()->create('XF:EditorDropdown');
            $xfDropdown->bulkSet([
                'icon' => "fa-{$dropdown->icon}",
                'active' => true,
                'buttons' => $dropdown->buttons,
                "cmd" => "kl_{$dropdown->dropdown_id}"
            ]);
            $xfDropdown->save();

            /** @var Phrase $masterTitle */
            $masterTitle = $xfDropdown->getMasterPhrase();
            $masterTitle->phrase_text = $dropdown->title;
            $masterTitle->save();
        }
    }

    /* DROP xf_kl_em_dropdowns */
    public function upgrade1020031Step3()
    {
        /** @var SchemaManager $schemaManager */
        $schemaManager = $this->schemaManager();
        $schemaManager->dropTable('xf_kl_em_dropdowns');
    }

    /* DROP xf_kl_em_video_proxy */
    public function upgrade1020031Step4()
    {
        /** @var SchemaManager $schemaManager */
        $schemaManager = $this->schemaManager();
        $schemaManager->dropTable('xf_kl_em_video_proxy');
    }

    /* DROP xf_kl_em_video_proxy_referrer */
    public function upgrade1020031Step5()
    {
        /** @var SchemaManager $schemaManager */
        $schemaManager = $this->schemaManager();
        $schemaManager->dropTable('xf_kl_em_video_proxy_referrer');
    }
}