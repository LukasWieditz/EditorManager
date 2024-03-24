<?php

namespace KL\EditorManager\XF\Service\Icon;

class UsageAnalyzer extends XFCP_UsageAnalyzer
{
    public function __construct(\XF\App $app, ?string $contentType = null)
    {
        parent::__construct($app, $contentType);
        $this->iconMetadata['editor-manager'] = [
            'is_brand' => false,
            'label' => 'Editor Manager',
        ];
    }
}
