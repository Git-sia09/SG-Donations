<?php

declare(strict_types=1);

namespace SG\Donations\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Pub\Controller\AbstractController;

class Donations extends AbstractController
{
    public function actionIndex(): AbstractReply
    {
        /** @var \SG\Donations\Repository\Donation $repo */
        $repo = $this->repository('SG\Donations:Donation');

        $options      = $this->app->options();
        $goal         = (float) ($options->sgDonationsGoal ?? 1000);
        $currency     = (string) ($options->sgDonationsCurrency ?? 'USD');
        $title        = (string) ($options->sgDonationsTitle ?? 'Donation Goal');
        $totalDonated = $repo->getTotalDonated();
        $percent      = $goal > 0
            ? (int) min(100, round($totalDonated / $goal * 100))
            : 0;

        $recentDonors = $repo->getDonationsForList(10);

        $visitor = \XF::visitor();
        $username = $visitor->user_id ? $visitor->username : '';

        return $this->view(
            'SG\Donations:Donations\Index',
            'sg_donations_form',
            [
                'title'        => $title,
                'goal'         => $goal,
                'currency'     => $currency,
                'totalDonated' => $totalDonated,
                'percent'      => $percent,
                'recentDonors' => $recentDonors,
                'username'     => $username,
            ]
        );
    }

    public function actionDonate(): AbstractReply
    {
        $this->assertPostOnly();

        $input = $this->filter([
            'username' => 'str',
            'amount'   => 'num',
            'message'  => 'str',
        ]);

        $options  = $this->app->options();
        $currency = (string) ($options->sgDonationsCurrency ?? 'USD');
        $visitor  = \XF::visitor();

        if ((float) $input['amount'] <= 0) {
            return $this->error(\XF::phrase('please_enter_valid_amount'));
        }

        if (empty($input['username'])) {
            $input['username'] = $visitor->user_id ? $visitor->username : 'Anonymous';
        }

        /** @var \SG\Donations\Entity\Donation $donation */
        $donation = $this->em()->create('SG\Donations:Donation');
        $donation->user_id       = $visitor->user_id;
        $donation->username      = $input['username'];
        $donation->amount        = (float) $input['amount'];
        $donation->currency      = $currency;
        $donation->message       = $input['message'] ?: null;
        $donation->donation_date = \XF::$time;
        $donation->visible       = 1;
        $donation->save();

        return $this->redirect(
            $this->buildLink('donations'),
            \XF::phrase('sg_donations_thank_you')
        );
    }
}
