<?php

namespace SG\Donations\Alert;

use XF\Alert\AbstractHandler;

class Donation extends AbstractHandler
{
    public function getEntityWith(): array
    {
        return [];
    }

    public function getDefaultOptOut(): array
    {
        return [];
    }

    /**
     * @param \XF\Mvc\Entity\Entity|null $content
     */
    public function canViewContent($content, &$error = null): bool
    {
        return true;
    }
}
