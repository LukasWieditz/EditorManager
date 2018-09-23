<?php

namespace KL\EditorManager\Finder;

use XF\Mvc\Entity\Finder;

class GoogleFont extends Finder {
    /**
     * @return $this
     */
    public function active() {
        $this->where('active', '=',1);

        $this->setDefaultOrder('font_id', 'ASC');

        return $this;
    }
}