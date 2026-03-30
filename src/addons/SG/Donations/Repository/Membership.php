<?php

namespace SG\Donations\Repository;

use XF\Mvc\Entity\Repository;

class Membership extends Repository
{
    public function getActiveMembershipForUser(int $userId, int $campaignId): ?\SG\Donations\Entity\Membership
    {
        /** @var \SG\Donations\Entity\Membership|null $membership */
        $membership = $this->finder('SG\Donations:Membership')
            ->where('user_id', $userId)
            ->where('campaign_id', $campaignId)
            ->where('active', 1)
            ->where('end_date', '>', \XF::$time)
            ->order('end_date', 'DESC')
            ->fetchOne();

        return $membership;
    }

    public function getMembershipsForUser(int $userId): array
    {
        return $this->finder('SG\Donations:Membership')
            ->with(['Campaign', 'Tier'])
            ->where('user_id', $userId)
            ->order('created_at', 'DESC')
            ->fetch()
            ->toArray();
    }
}
