<?php

namespace SG\Donations\Service;

use SG\Donations\Entity\DonationLog;
use SG\Donations\Entity\Tier;
use XF\Service\AbstractService;

class DonationCreator extends AbstractService
{
    protected DonationLog $donation;

    protected Tier $tier;

    protected ?\XF\Entity\User $targetUser = null;

    protected ?string $ipAddress = null;

    protected int $queuedUntil = 0;

    public function setTier(Tier $tier): void
    {
        $this->tier = $tier;
    }

    public function setTargetUser(\XF\Entity\User $user): void
    {
        $this->targetUser = $user;
    }

    public function setIpAddress(?string $ip): void
    {
        $this->ipAddress = $ip;
    }

    public function setQueuedUntil(int $timestamp): void
    {
        $this->queuedUntil = $timestamp;
    }

    public function setup(
        \XF\Entity\User $user,
        Tier $tier,
        string $paymentRef = '',
        ?string $ip = null,
        int $queuedUntil = 0
    ): void {
        $this->targetUser = $user;
        $this->tier       = $tier;
        $this->ipAddress  = $ip;
        $this->queuedUntil = $queuedUntil;

        /** @var DonationLog $donation */
        $donation = $this->em()->create('SG\Donations:DonationLog');
        $donation->user_id     = $user->user_id;
        $donation->campaign_id = $tier->campaign_id;
        $donation->tier_id     = $tier->tier_id;
        $donation->amount      = round($tier->amount, 2);
        $donation->payment_ref = $paymentRef !== '' ? $paymentRef : null;
        $donation->status      = 'queued';
        $donation->queued_until = $queuedUntil;
        $donation->created_at  = \XF::$time;
        $donation->created_by  = \XF::visitor() ? \XF::visitor()->user_id : 0;
        $donation->created_ip  = $ip ? inet_pton($ip) : null;

        $this->donation = $donation;
    }

    public function validate(array &$errors = []): bool
    {
        $donation = $this->donation;
        $tier     = $this->tier;

        if (!$this->targetUser || !$this->targetUser->user_id) {
            $errors[] = \XF::phrase('sg_donations_invalid_user');
            return false;
        }

        if (!$tier->tier_id) {
            $errors[] = \XF::phrase('sg_donations_invalid_tier');
            return false;
        }

        if ($tier->campaign_id !== $donation->campaign_id) {
            $errors[] = \XF::phrase('sg_donations_tier_campaign_mismatch');
            return false;
        }

        if ($donation->amount <= 0) {
            $errors[] = \XF::phrase('sg_donations_amount_must_be_positive');
            return false;
        }

        $donation->preSave();
        if ($donation->hasErrors()) {
            $errors = array_merge($errors, array_values($donation->getErrors()));
            return false;
        }

        return true;
    }

    public function save(): DonationLog
    {
        $donation = $this->donation;
        $tier     = $this->tier;

        $db = $this->db();
        $db->beginTransaction();

        try {
            $donation->save(false);

            // Update campaign stats cache immediately (queued count = 1)
            /** @var \SG\Donations\Repository\CampaignStats $statsRepo */
            $statsRepo = $this->repository('SG\Donations:CampaignStats');
            $statsRepo->rebuildForCampaign($donation->campaign_id);

            // Audit log
            /** @var \SG\Donations\Repository\AuditLog $auditRepo */
            $auditRepo = $this->repository('SG\Donations:AuditLog');
            $auditRepo->writeLog(
                'donation',
                $donation->donation_id,
                'create',
                null,
                $this->donationToArray($donation),
                $donation->created_by,
                $this->ipAddress
            );

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }

        // Send alert (outside transaction)
        $this->sendAlert($donation);

        return $donation;
    }

    protected function sendAlert(DonationLog $donation): void
    {
        if (!$this->targetUser || !$this->targetUser->user_id) {
            return;
        }

        $campaign = $this->em()->find('SG\Donations:Campaign', $donation->campaign_id);
        $campaignTitle = $campaign ? $campaign->title : '';

        /** @var \XF\Repository\UserAlert $alertRepo */
        $alertRepo = $this->repository('XF:UserAlert');
        $alertRepo->alert(
            $this->targetUser,
            \XF::visitor(),
            '',
            'sg_donation',
            $donation->donation_id,
            'donation_added',
            ['campaign_title' => $campaignTitle, 'amount' => $donation->amount]
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
            'payment_ref' => $donation->payment_ref,
            'created_at'  => $donation->created_at,
        ];
    }
}
