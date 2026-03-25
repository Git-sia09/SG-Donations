<?php

declare(strict_types=1);

namespace SG\Donations\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;

class Donations extends AbstractController
{
    public function actionIndex(): AbstractReply
    {
        /** @var \SG\Donations\Repository\Donation $repo */
        $repo = $this->repository('SG\Donations:Donation');

        $page    = $this->filterPage();
        $perPage = 20;

        $finder = $repo->findDonations()
            ->with('User')
            ->order('donation_date', 'DESC');

        $total     = $finder->total();
        $donations = $finder->limitByPage($page, $perPage)->fetch();

        $this->assertValidPage($page, $perPage, $total, 'admin:donations');

        return $this->view(
            'SG\Donations:Donations\Index',
            'sg_donations_list',
            [
                'donations' => $donations,
                'page'      => $page,
                'perPage'   => $perPage,
                'total'     => $total,
            ]
        );
    }

    public function actionDelete(): AbstractReply
    {
        $donationId = $this->filter('donation_id', 'uint');

        /** @var \SG\Donations\Entity\Donation|null $donation */
        $donation = $this->assertRecordExists('SG\Donations:Donation', $donationId);

        if ($this->isPost()) {
            $donation->delete();
            return $this->redirect($this->buildLink('donations'));
        }

        return $this->view(
            'SG\Donations:Donations\Delete',
            'sg_donations_delete_confirm',
            ['donation' => $donation]
        );
    }

    public function actionToggleVisible(): AbstractReply
    {
        $this->assertPostOnly();

        $donationId = $this->filter('donation_id', 'uint');

        /** @var \SG\Donations\Entity\Donation $donation */
        $donation = $this->assertRecordExists('SG\Donations:Donation', $donationId);

        $donation->visible = $donation->visible ? 0 : 1;
        $donation->save();

        return $this->redirect($this->buildLink('donations'));
    }
}
