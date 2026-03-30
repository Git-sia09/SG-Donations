<?php

namespace SG\Donations\Repository;

use SG\Donations\Entity\Campaign as CampaignEntity;
use XF\Mvc\Entity\Repository;

class Campaign extends Repository
{
    public function findCampaignsForList(bool $activeOnly = false): \XF\Mvc\Entity\Finder
    {
        $finder = $this->finder('SG\Donations:Campaign')
            ->order('display_order')
            ->order('title');

        if ($activeOnly) {
            $finder->where('active', 1);
        }

        return $finder;
    }

    public function findCampaignWithStats(): \XF\Mvc\Entity\Finder
    {
        return $this->finder('SG\Donations:Campaign')
            ->with('Stats')
            ->order('display_order');
    }

    /**
     * @return CampaignEntity[]
     */
    public function getActiveCampaignsWithStats(): array
    {
        return $this->finder('SG\Donations:Campaign')
            ->with('Stats')
            ->where('active', 1)
            ->order('display_order')
            ->fetch()
            ->toArray();
    }
}
