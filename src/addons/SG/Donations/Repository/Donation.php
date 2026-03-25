<?php

declare(strict_types=1);

namespace SG\Donations\Repository;

use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Donation extends Repository
{
    public function getTotalDonated(): float
    {
        return (float) $this->db()->fetchOne(
            'SELECT COALESCE(SUM(amount), 0) FROM xf_sg_donation WHERE visible = 1'
        );
    }

    public function getDonationsForList(int $limit = 20): AbstractCollection
    {
        return $this->findDonations()
            ->with('User')
            ->order('donation_date', 'DESC')
            ->limit($limit)
            ->fetch();
    }

    public function findDonations(): Finder
    {
        return $this->finder('SG\Donations:Donation');
    }
}
