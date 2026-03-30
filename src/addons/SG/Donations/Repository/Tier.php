<?php

namespace SG\Donations\Repository;

use XF\Mvc\Entity\Repository;

class Tier extends Repository
{
    public function findTiersByCampaign(int $campaignId, bool $activeOnly = false): \XF\Mvc\Entity\Finder
    {
        $finder = $this->finder('SG\Donations:Tier')
            ->where('campaign_id', $campaignId)
            ->order('display_order');

        if ($activeOnly) {
            $finder->where('active', 1);
        }

        return $finder;
    }

    public function getTiersForCampaignSelect(int $campaignId): array
    {
        return $this->finder('SG\Donations:Tier')
            ->where('campaign_id', $campaignId)
            ->where('active', 1)
            ->order('display_order')
            ->fetch()
            ->toArray();
    }

    /**
     * @throws \XF\PrintableException
     */
    public function assertTierDeletable(\SG\Donations\Entity\Tier $tier): void
    {
        $donationCount = $this->db()->fetchOne(
            'SELECT COUNT(*) FROM xf_sg_donations_log WHERE tier_id = ?',
            [$tier->tier_id]
        );

        if ($donationCount > 0) {
            throw new \LogicException(\XF::phrase('sg_donations_cannot_delete_tier_with_logs')->render());
        }
    }
}
