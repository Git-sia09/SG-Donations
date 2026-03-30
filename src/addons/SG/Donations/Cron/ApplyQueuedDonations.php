<?php

namespace SG\Donations\Cron;

class ApplyQueuedDonations
{
    public static function run(): void
    {
        \XF::app()->jobManager()->enqueueUnique(
            'sgDonationsApplyQueued',
            'SG\Donations:ApplyQueuedDonations',
            [],
            false
        );
    }
}
