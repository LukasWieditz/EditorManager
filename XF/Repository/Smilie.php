<?php

namespace KL\EditorManager\XF\Repository;

class Smilie extends XFCP_Smilie
{
    public function getSmilieListData($displayInEditorOnly = false)
    {
        $smilieData = parent::getSmilieListData($displayInEditorOnly);

        if ($displayInEditorOnly) {
            foreach ($smilieData['smilies'] as &$smilieCategory) {
                $this->filterSmilies($smilieCategory);
            }
        }

        return $smilieData;
    }

    public function filterSmilies(&$smilies)
    {
        foreach ($smilies as $id => $smilie) {
            if (isset($smilie['kl_em_active']) && !$smilie['kl_em_active']) {
                unset($smilies[$id]);
            }

            if (!empty($smilie['kl_em_user_criteria'])) {
                $criteriaCheck = \XF::app()->criteria('XF:User', $smilie['kl_em_user_criteria']);

                $criteriaCheck->setMatchOnEmpty(true);

                if (!$criteriaCheck->isMatched(\XF::visitor())) {
                    unset($smilies[$id]);
                }
            }
        }
    }
}
