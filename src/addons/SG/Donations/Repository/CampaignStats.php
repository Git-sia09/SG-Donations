<?php

namespace SG\Donations\Repository;

use SG\Donations\Entity\CampaignStats as CampaignStatsEntity;
use XF\Mvc\Entity\Repository;

class CampaignStats extends Repository
{
    /**
     * Rebuild stats for a single campaign.
     */
    public function rebuildForCampaign(int $campaignId): void
    {
        $db = $this->db();

        // Sum raised from all non-voided donations (queued + applied both count)
        $raised = (float)$db->fetchOne(
            'SELECT COALESCE(SUM(amount), 0) FROM xf_sg_donations_log
             WHERE campaign_id = ? AND status != ?',
            [$campaignId, 'voided']
        );

        // Latest 10 donors (non-voided), ordered by created_at DESC
        $latestRows = $db->fetchAll(
            'SELECT l.donation_id, l.user_id, l.amount, l.created_at, u.username
             FROM xf_sg_donations_log l
             LEFT JOIN xf_user u ON u.user_id = l.user_id
             WHERE l.campaign_id = ? AND l.status != ?
             ORDER BY l.created_at DESC
             LIMIT 10',
            [$campaignId, 'voided']
        );

        $db->insertOrUpdate('xf_sg_donations_campaign_stats', [
            'campaign_id'        => $campaignId,
            'raised_total'       => round($raised, 2),
            'latest_donors_json' => json_encode(array_values($latestRows)),
            'last_rebuild_at'    => \XF::$time,
        ], [
            'raised_total'       => round($raised, 2),
            'latest_donors_json' => json_encode(array_values($latestRows)),
            'last_rebuild_at'    => \XF::$time,
        ]);
    }

    /**
     * Rebuild stats for all campaigns.
     */
    public function rebuildAll(): int
    {
        $campaignIds = $this->db()->fetchAllColumn(
            'SELECT campaign_id FROM xf_sg_donations_campaign'
        );

        foreach ($campaignIds as $campaignId) {
            $this->rebuildForCampaign((int)$campaignId);
        }

        return count($campaignIds);
    }

    public function getStatsForCampaign(int $campaignId): ?CampaignStatsEntity
    {
        return $this->em->find('SG\Donations:CampaignStats', $campaignId);
    }
}
