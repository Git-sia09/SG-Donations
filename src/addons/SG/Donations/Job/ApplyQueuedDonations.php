<?php

namespace SG\Donations\Job;

use XF\Job\AbstractJob;

class ApplyQueuedDonations extends AbstractJob
{
    public function run($maxRunTime): \XF\Job\JobResult
    {
        $startTime = microtime(true);

        /** @var \SG\Donations\Repository\DonationLog $donationRepo */
        $donationRepo = $this->app->repository('SG\Donations:DonationLog');

        $queuedDonations = $donationRepo->findQueuedEligibleDonations()->fetch();

        $applied = 0;
        $errors  = 0;

        foreach ($queuedDonations as $donation) {
            if (microtime(true) - $startTime > $maxRunTime) {
                // Return 'more' to re-queue
                return $this->resume();
            }

            try {
                /** @var \SG\Donations\Service\DonationApplier $applier */
                $applier = $this->app->service('SG\Donations:DonationApplier');
                $applier->setDonation($donation);
                $applier->apply();
                $applied++;
            } catch (\Exception $e) {
                $errors++;
                \XF::logException($e, false, "SG Donations: error applying donation #{$donation->donation_id}: ");
            }
        }

        return $this->complete();
    }

    public function getStatusMessage(): string
    {
        return \XF::phrase('sg_donations_applying_queued_donations')->render();
    }

    public function canCancel(): bool
    {
        return false;
    }

    public function canTriggerByChoice(): bool
    {
        return true;
    }
}
