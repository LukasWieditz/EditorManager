<?php

namespace KL\EditorManager\Cli\Command\Rebuild;

use XF\Cli\Command\Rebuild\AbstractRebuildCommand;

/**
 * Class UserTemplateCache
 * @package Kl\EditorManager\Cli\Command\Rebuild
 */
class UserTemplateCache extends AbstractRebuildCommand
{
    /**
     * @return string
     */
    protected function getRebuildName(): string
    {
        return 'klem-user-template-cache';
    }

    /**
     * @return string
     */
    protected function getRebuildDescription(): string
    {
        return 'Rebuilds Editor Manager user private template caches.';
    }

    /**
     * @return string
     */
    protected function getRebuildClass(): string
    {
        return 'KL\EditorManager:UserTemplateCache';
    }
}