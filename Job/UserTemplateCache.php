<?php


namespace KL\EditorManager\Job;


use KL\EditorManager\Repository\Template;
use XF;
use XF\Entity\User;
use XF\Job\AbstractRebuildJob;
use XF\Phrase;
use XF\PrintableException;

/**
 * Class UserTemplateCache
 * @package KL\EditorManager\Job
 */
class UserTemplateCache extends AbstractRebuildJob
{
    /**
     * @param $start
     * @param $batch
     * @return array
     */
    protected function getNextIds($start, $batch): array
    {
        $db = $this->app->db();

        return $db->fetchAllColumn($db->limit(
            "
				SELECT user_id
				FROM xf_user
				WHERE user_id > ?
				ORDER BY user_id
			", $batch
        ), $start);
    }

    /**
     * @param $id
     * @throws PrintableException
     */
    protected function rebuildById($id): void
    {
        $em = $this->app->em();
        /** @var User $user */
        $user = $em->find('XF:User', $id);

        /** @var Template $repo */
        $repo = $em->getRepository('KL\EditorManager:Template');
        $repo->rebuildUserTemplateCache($user);
    }

    /**
     * @return Phrase
     */
    protected function getStatusType(): Phrase
    {
        return XF::phrase('kl_em_user_template_cache');
    }
}