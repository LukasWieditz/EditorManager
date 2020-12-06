<?php

/*!
 * KL/EditorManager/Repository/AbstractProxy.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use KL\EditorManager\Entity\AbstractProxy as AbstractProxyEntity;
use KL\EditorManager\Finder\AbstractProxy as AbstractProxyFinder;
use XF;
use XF\Db\DeadlockException;
use XF\Db\Exception;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

/**
 * Class AbstractProxy
 * @package KL\EditorManager\Repository
 */
abstract class AbstractProxy extends Repository
{
    /**
     * @return string
     */
    abstract protected function getEntityClass(): string;

    /**
     * @return string
     */
    abstract protected function getReferrerEntityClass(): string;

    /**
     * @var string
     */
    protected $referrerTable;

    /**
     * @return string
     */
    protected function getReferrerDbTable(): string
    {
        if (!$this->referrerTable) {
            $entity = $this->em->create($this->getReferrerEntityClass());
            $this->referrerTable = $entity->structure()->table;
        }
        return $this->referrerTable;
    }

    /**
     * @var string
     */
    protected $table;

    /**
     * @return string
     */
    protected function getDbTable(): string
    {
        if (!$this->table) {
            $entity = $this->em->create($this->getEntityClass());
            $this->table = $entity->structure()->table;
        }
        return $this->table;
    }

    /**
     * @var string
     */
    protected $primaryKeyField;

    /**
     * @return string
     */
    protected function getPrimaryKeyField(): string
    {
        if (!$this->primaryKeyField) {
            $entity = $this->em->create($this->getEntityClass());
            $this->primaryKeyField = $entity->structure()->primaryKey;
        }
        return $this->primaryKeyField;
    }

    /**
     * @param int $activeLength
     * @return int
     * @noinspection SqlResolve
     */
    public function getTotalActiveFetches(int $activeLength = 60): int
    {
        $table = $this->getDbTable();
        return (int)$this->db()->fetchOne("
			SELECT COUNT(*)
			FROM {$table}
			WHERE is_processing >= ?
		", XF::$time - $activeLength);
    }

    /**
     * @param string $url
     * @return string
     */
    public function cleanUrlForFetch(string $url): string
    {
        $url = preg_replace('/#.*$/s', '', $url);
        if (preg_match_all('/[^A-Za-z0-9._~:\/?#\[\]@!$&\'()*+,;=%-]/', $url, $matches)) {
            foreach ($matches[0] as $match) {
                $url = str_replace($match[0], '%' . strtoupper(dechex(ord($match[0]))), $url);
            }
        }
        $url = preg_replace('/%(?![a-fA-F0-9]{2})/', '%25', $url);

        return $url;
    }

    /**
     * @return AbstractProxyFinder|Finder
     */
    public function findProxyLogsForList(): AbstractProxyFinder
    {
        return $this->finder($this->getEntityClass())
            ->setDefaultOrder('last_request_date', 'DESC');
    }

    /**
     * @param AbstractProxyEntity $proxyEntity
     * @throws Exception
     * @noinspection SqlResolve
     */
    public function logView(AbstractProxyEntity $proxyEntity): void
    {
        $primaryKeyField = $this->getPrimaryKeyField();
        $table = $this->getDbTable();

        $this->db()->query("
			UPDATE {$table} SET
				views = views + 1,
				last_request_date = ?
			WHERE {$primaryKeyField} = ?
		", [XF::$time, $proxyEntity->$primaryKeyField]);
    }

    /**
     * @param string $url
     *
     * @return AbstractProxyEntity|XF\Mvc\Entity\Entity|null
     */
    public function getByUrl($url)
    {
        $url = $this->cleanUrlForFetch($url);
        $hash = md5($url);

        return $this->findProxyLogsForList()
            ->whereHash($hash)
            ->fetchOne();
    }

    /**
     * @return AbstractProxyEntity
     */
    abstract public function getPlaceholder(): AbstractProxyEntity;

    /**
     * @param AbstractProxyEntity $proxyEntity
     * @param $referrer
     * @return bool
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function logReferrer(AbstractProxyEntity $proxyEntity, $referrer): bool
    {
        if (!preg_match('#^https?://#i', $referrer)) {
            return false;
        }

        $primaryKeyField = $this->getPrimaryKeyField();
        $table = $this->getReferrerDbTable();

        try {
            $this->db()->insert($table, [
                $primaryKeyField => $proxyEntity->$primaryKeyField,
                'referrer_hash' => md5($referrer),
                'referrer_url' => $referrer,
                'hits' => 1,
                'first_date' => XF::$time,
                'last_date' => XF::$time
            ], false, 'hits = hits + 1, last_date = VALUES(last_date)');
        } catch (DeadlockException $e) {
            // ignore deadlocks here -- we're likely triggering a race condition within MySQL
        }

        return true;
    }

    /**
     * @return int
     */
    abstract protected function getCacheTTL(): int;

    /**
     * Prunes resources from the file system cache that have expired
     *
     * @param integer|null $pruneDate
     * @throws XF\PrintableException
     */
    public function pruneCache($pruneDate = null): void
    {
        $cacheTTL = $this->getCacheTTL();

        if ($pruneDate === null) {
            if (!$cacheTTL) {
                return;
            }

            $pruneDate = XF::$time - (86400 * $cacheTTL);
        }

        /** @var AbstractProxyEntity[] $proxies */
        $proxies = $this->findProxyLogsForList()
            ->whereNotProcessing()
            ->whereNotPruned()
            ->whereFetchedBefore($pruneDate)
            ->fetch(2000);

        foreach ($proxies as $proxy) {
            $proxy->prune();
        }
    }

    /**
     * @return int
     */
    abstract protected function getProxyLogLength(): int;

    /**
     * Prunes unused proxy log entries.
     *
     * @param null|int $pruneDate
     *
     * @return int
     */
    public function pruneProxyLogs($pruneDate = null): int
    {
        $cacheTTL = $this->getCacheTTL();
        $proxyLogLength = $this->getProxyLogLength();

        if ($pruneDate === null) {
            if (!$proxyLogLength) {
                return 0;
            }
            if (!$cacheTTL) {
                // we're keeping resources forever - can't prune
                return 0;
            }

            $maxTtl = max($proxyLogLength, $cacheTTL);
            $pruneDate = XF::$time - (86400 * $maxTtl);
        }

        // we can only remove logs where we've pruned the resource
        return (int)$this->db()->delete($this->getDbTable(),
            'pruned = 1 AND last_request_date < ?', $pruneDate
        );
    }

    /**
     * @return array
     */
    abstract protected function getReferrerOptions(): array;

    /**
     * @param null $pruneDate
     * @return int
     */
    public function pruneReferrerLogs($pruneDate = null): int
    {
        if ($pruneDate === null) {
            $referrerOptions = $this->getReferrerOptions();

            if (empty($referrerOptions['length'])) {
                // we're keeping referrer data forever
                return 0;
            }

            $pruneDate = XF::$time - (86400 * $referrerOptions['length']);
        }

        return (int)$this->db()->delete($this->getReferrerDbTable(),
            'last_date < ?', $pruneDate
        );
    }
}