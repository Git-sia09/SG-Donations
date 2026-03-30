<?php

namespace SG\Donations\Repository;

use XF\Mvc\Entity\Repository;

class DonationLog extends Repository
{
    public function findDonationsForList(array $filters = []): \XF\Mvc\Entity\Finder
    {
        $finder = $this->finder('SG\Donations:DonationLog')
            ->with(['User', 'Campaign', 'Tier'])
            ->order('created_at', 'DESC');

        if (!empty($filters['campaign_id'])) {
            $finder->where('campaign_id', (int)$filters['campaign_id']);
        }
        if (!empty($filters['user_id'])) {
            $finder->where('user_id', (int)$filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $finder->where('status', $filters['status']);
        }

        return $finder;
    }

    public function findQueuedEligibleDonations(): \XF\Mvc\Entity\Finder
    {
        return $this->finder('SG\Donations:DonationLog')
            ->with(['User', 'Campaign', 'Tier'])
            ->where('status', 'queued')
            ->where('queued_until', '<=', \XF::$time)
            ->order('queued_until');
    }

    public function getUserDonationsForPreview(int $userId): array
    {
        return $this->finder('SG\Donations:DonationLog')
            ->with(['Campaign', 'Tier'])
            ->where('user_id', $userId)
            ->where('status', '!=', 'voided')
            ->order('created_at', 'DESC')
            ->fetch()
            ->toArray();
    }
}
