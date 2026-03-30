<?php

namespace SG\Donations\Service;

use SG\Donations\Entity\DonationLog;
use SG\Donations\Entity\Tier;
use SG\Donations\Entity\Membership;
use XF\Service\AbstractService;

/**
 * Applies a queued donation: grants membership/groups and sends alert.
 * Does NOT change campaign stats totals (queued already counted).
 */
class DonationApplier extends AbstractService
{
    protected DonationLog $donation;

    protected ?string $ipAddress = null;

    public function setDonation(DonationLog $donation): void
    {
        $this->donation = $donation;
    }

    public function setIpAddress(?string $ip): void
    {
        $this->ipAddress = $ip;
    }

    public function apply(): DonationLog
    {
        $donation = $this->donation;
        $before   = $this->donationToArray($donation);

        $db = $this->db();
        $db->beginTransaction();

        try {
            $donation->status     = 'applied';
            $donation->applied_at = \XF::$time;
            $donation->applied_by = 0; // cron
            $donation->applied_ip = null;
            $donation->save(false);

            $membership = $this->applyMembership($donation);

            // Audit log (no cache change needed — queued already counted)
            /** @var \SG\Donations\Repository\AuditLog $auditRepo */
            $auditRepo = $this->repository('SG\Donations:AuditLog');
            $auditRepo->writeLog(
                'donation',
                $donation->donation_id,
                'apply',
                $before,
                $this->donationToArray($donation),
                0,
                null
            );

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }

        // Send alert (outside transaction)
        $this->sendAlert($donation, $membership ?? null);

        return $donation;
    }

    protected function applyMembership(DonationLog $donation): ?Membership
    {
        /** @var \XF\Entity\User|null $user */
        $user = $this->em()->find('XF:User', $donation->user_id);
        if (!$user) {
            return null;
        }

        /** @var Tier|null $tier */
        $tier = $this->em()->find('SG\Donations:Tier', $donation->tier_id);
        if (!$tier) {
            return null;
        }

        /** @var \SG\Donations\Repository\Membership $membershipRepo */
        $membershipRepo = $this->repository('SG\Donations:Membership');
        $existingMembership = $membershipRepo->getActiveMembershipForUser(
            $donation->user_id,
            $donation->campaign_id
        );

        $startDate = \XF::$time;
        $baseDate  = $existingMembership ? max($existingMembership->end_date, \XF::$time) : $startDate;
        $endDate   = $this->addMonths($baseDate, $tier->duration_months);

        // Determine groups to grant
        $newGroupIds = $tier->user_group_ids ?: [];

        // If there's an existing membership with different tier, replace groups
        if ($existingMembership && $existingMembership->tier_id !== $donation->tier_id) {
            $this->removeGroupsFromUser($donation->user_id, $existingMembership->granted_group_ids);
        }

        // Deactivate old membership(s) for this campaign
        if ($existingMembership) {
            $existingMembership->active = false;
            $existingMembership->save(false);
        }

        // Create new membership
        /** @var Membership $membership */
        $membership = $this->em()->create('SG\Donations:Membership');
        $membership->user_id           = $donation->user_id;
        $membership->campaign_id       = $donation->campaign_id;
        $membership->tier_id           = $donation->tier_id;
        $membership->donation_id       = $donation->donation_id;
        $membership->start_date        = $startDate;
        $membership->end_date          = $endDate;
        $membership->granted_group_ids = $newGroupIds;
        $membership->active            = true;
        $membership->created_at        = \XF::$time;
        $membership->save(false);

        // Grant groups to user
        $this->addGroupsToUser($donation->user_id, $newGroupIds);

        return $membership;
    }

    protected function addGroupsToUser(int $userId, array $groupIds): void
    {
        if (empty($groupIds)) {
            return;
        }

        /** @var \XF\Entity\User|null $user */
        $user = $this->em()->find('XF:User', $userId);
        if (!$user) {
            return;
        }

        $currentSecondary = $user->secondary_group_ids ?? [];
        $newSecondary = array_values(array_unique(array_merge($currentSecondary, $groupIds)));

        if ($newSecondary !== $currentSecondary) {
            $this->db()->update(
                'xf_user',
                ['secondary_group_ids' => implode(',', $newSecondary)],
                'user_id = ?',
                [$userId]
            );
        }
    }

    protected function removeGroupsFromUser(int $userId, array $groupIds): void
    {
        if (empty($groupIds)) {
            return;
        }

        /** @var \XF\Entity\User|null $user */
        $user = $this->em()->find('XF:User', $userId);
        if (!$user) {
            return;
        }

        $currentSecondary = $user->secondary_group_ids ?? [];
        $newSecondary = array_values(array_diff($currentSecondary, $groupIds));

        if ($newSecondary !== $currentSecondary) {
            $this->db()->update(
                'xf_user',
                ['secondary_group_ids' => implode(',', $newSecondary)],
                'user_id = ?',
                [$userId]
            );
        }
    }

    protected function addMonths(int $timestamp, int $months): int
    {
        return (int)\DateTime::createFromFormat('U', (string)$timestamp)
            ->modify("+{$months} months")
            ->format('U');
    }

    protected function sendAlert(DonationLog $donation, ?Membership $membership): void
    {
        $user = $this->em()->find('XF:User', $donation->user_id);
        if (!$user) {
            return;
        }

        $campaign = $this->em()->find('SG\Donations:Campaign', $donation->campaign_id);
        $campaignTitle = $campaign ? $campaign->title : '';
        $benefitsUntil = $membership ? \XF::language()->date($membership->end_date) : '';

        /** @var \XF\Repository\UserAlert $alertRepo */
        $alertRepo = $this->repository('XF:UserAlert');
        $alertRepo->alert(
            $user,
            \XF::visitor(),
            '',
            'sg_donation',
            $donation->donation_id,
            'donation_applied',
            [
                'campaign_title' => $campaignTitle,
                'amount'         => $donation->amount,
                'benefits_until' => $benefitsUntil,
            ]
        );
    }

    protected function donationToArray(DonationLog $donation): array
    {
        return [
            'donation_id' => $donation->donation_id,
            'user_id'     => $donation->user_id,
            'campaign_id' => $donation->campaign_id,
            'tier_id'     => $donation->tier_id,
            'amount'      => $donation->amount,
            'status'      => $donation->status,
            'applied_at'  => $donation->applied_at,
        ];
    }
}
