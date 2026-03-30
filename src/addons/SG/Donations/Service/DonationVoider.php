<?php

namespace SG\Donations\Service;

use SG\Donations\Entity\DonationLog;
use XF\Service\AbstractService;

class DonationVoider extends AbstractService
{
    protected DonationLog $donation;

    protected string $voidReason = '';

    protected ?string $ipAddress = null;

    public function setDonation(DonationLog $donation): void
    {
        $this->donation = $donation;
    }

    public function setVoidReason(string $reason): void
    {
        $this->voidReason = trim($reason);
    }

    public function setIpAddress(?string $ip): void
    {
        $this->ipAddress = $ip;
    }

    public function validate(array &$errors = []): bool
    {
        if ($this->donation->isVoided()) {
            $errors[] = \XF::phrase('sg_donations_already_voided');
            return false;
        }

        if (empty($this->voidReason)) {
            $errors[] = \XF::phrase('sg_donations_void_reason_required');
            return false;
        }

        return true;
    }

    public function void(): DonationLog
    {
        $donation    = $this->donation;
        $before      = $this->donationToArray($donation);
        $wasApplied  = $donation->isApplied(); // capture before status change

        $db = $this->db();
        $db->beginTransaction();

        try {
            $donation->status     = 'voided';
            $donation->voided_at  = \XF::$time;
            $donation->voided_by  = \XF::visitor() ? \XF::visitor()->user_id : 0;
            $donation->voided_ip  = $this->ipAddress ? inet_pton($this->ipAddress) : null;
            $donation->void_reason = $this->voidReason;
            $donation->save(false);

            // Roll back membership/group effects if the donation had been applied
            if ($wasApplied) {
                $this->rollbackMembership($donation);
            }

            // Update campaign stats
            /** @var \SG\Donations\Repository\CampaignStats $statsRepo */
            $statsRepo = $this->repository('SG\Donations:CampaignStats');
            $statsRepo->rebuildForCampaign($donation->campaign_id);

            // Audit log
            /** @var \SG\Donations\Repository\AuditLog $auditRepo */
            $auditRepo = $this->repository('SG\Donations:AuditLog');
            $auditRepo->writeLog(
                'donation',
                $donation->donation_id,
                'void',
                $before,
                $this->donationToArray($donation),
                $donation->voided_by,
                $this->ipAddress
            );

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }

        // Send alert (do NOT include void reason)
        $this->sendAlert($donation);

        return $donation;
    }

    protected function rollbackMembership(DonationLog $donation): void
    {
        // Deactivate membership record for this donation
        $membership = $this->finder('SG\Donations:Membership')
            ->where('donation_id', $donation->donation_id)
            ->where('active', 1)
            ->fetchOne();

        if (!$membership) {
            return;
        }

        $membership->active = false;
        $membership->save(false);

        // Remove granted groups from user
        $this->removeGroupsFromUser((int)$donation->user_id, $membership->granted_group_ids);
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

    protected function sendAlert(DonationLog $donation): void
    {
        $user = $this->em()->find('XF:User', $donation->user_id);
        if (!$user) {
            return;
        }

        $campaign = $this->em()->find('SG\Donations:Campaign', $donation->campaign_id);
        $campaignTitle = $campaign ? $campaign->title : '';

        /** @var \XF\Repository\UserAlert $alertRepo */
        $alertRepo = $this->repository('XF:UserAlert');
        $alertRepo->alert(
            $user,
            \XF::visitor(),
            '',
            'sg_donation',
            $donation->donation_id,
            'donation_voided',
            ['campaign_title' => $campaignTitle]
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
            'void_reason' => $donation->void_reason,
            'voided_at'   => $donation->voided_at,
        ];
    }
}
