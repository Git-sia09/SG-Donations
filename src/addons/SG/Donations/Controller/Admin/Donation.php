<?php

namespace SG\Donations\Controller\Admin;

use XF\Mvc\ParameterBag;
use XF\Admin\Controller\AbstractController;

class Donation extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('sgDonations_viewDonations');
    }

    // -------------------------------------------------------------------------
    // Donation list with filters
    // -------------------------------------------------------------------------

    public function actionIndex(): \XF\Mvc\Reply\AbstractReply
    {
        $filters = $this->getFilterInput();

        /** @var \SG\Donations\Repository\DonationLog $repo */
        $repo    = $this->repository('SG\Donations:DonationLog');
        $finder  = $repo->findDonationsForList($filters);

        $page    = $this->filterPage();
        $perPage = 20;
        $total   = $finder->total();

        $donations = $finder->limitByPage($page, $perPage)->fetch();

        /** @var \SG\Donations\Repository\Campaign $campaignRepo */
        $campaignRepo = $this->repository('SG\Donations:Campaign');
        $campaigns    = $campaignRepo->findCampaignsForList()->fetch();

        return $this->view(
            'SG\Donations:Donation\Listing',
            'sg_donations_donation_list',
            [
                'donations' => $donations,
                'campaigns' => $campaigns,
                'filters'   => $filters,
                'total'     => $total,
                'page'      => $page,
                'perPage'   => $perPage,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Add donation
    // -------------------------------------------------------------------------

    public function actionAdd(): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertAdminPermission('sgDonations_addDonation');

        // Prefill from duplicate action
        $prefill = $this->filter([
            'user_id'     => 'uint',
            'campaign_id' => 'uint',
            'tier_id'     => 'uint',
        ]);

        /** @var \SG\Donations\Repository\Campaign $campaignRepo */
        $campaignRepo = $this->repository('SG\Donations:Campaign');
        $campaigns    = $campaignRepo->findCampaignsForList(true)->with('Tiers')->fetch();

        $prefilledUser = null;
        if ($prefill['user_id']) {
            $prefilledUser = $this->em()->find('XF:User', $prefill['user_id']);
        }

        return $this->view(
            'SG\Donations:Donation\Add',
            'sg_donations_donation_add',
            [
                'campaigns'    => $campaigns,
                'prefill'      => $prefill,
                'prefilledUser'=> $prefilledUser,
            ]
        );
    }

    public function actionSave(): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertPostOnly();
        $this->assertAdminPermission('sgDonations_addDonation');

        $input = $this->filter([
            'user_id'      => 'uint',
            'tier_id'      => 'uint',
            'payment_ref'  => 'str',
            'queued_until' => 'str',
        ]);

        /** @var \XF\Entity\User|null $user */
        $user = $this->em()->find('XF:User', $input['user_id']);
        if (!$user) {
            return $this->error(\XF::phrase('sg_donations_invalid_user'));
        }

        /** @var \SG\Donations\Entity\Tier|null $tier */
        $tier = $this->em()->find('SG\Donations:Tier', $input['tier_id']);
        if (!$tier) {
            return $this->error(\XF::phrase('sg_donations_invalid_tier'));
        }

        $queuedUntil = $input['queued_until'] ? (int)strtotime($input['queued_until']) : \XF::$time;

        /** @var \SG\Donations\Service\DonationCreator $creator */
        $creator = $this->service('SG\Donations:DonationCreator');
        $creator->setup($user, $tier, $input['payment_ref'], $this->request->getIp(), $queuedUntil);

        $errors = [];
        if (!$creator->validate($errors)) {
            return $this->error($errors);
        }

        $donation = $creator->save();

        return $this->redirect($this->buildLink('sg-donations/donations/view', $donation));
    }

    // -------------------------------------------------------------------------
    // View donation
    // -------------------------------------------------------------------------

    public function actionView(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $donation = $this->assertDonationExists($params['donation_id']);

        /** @var \SG\Donations\Repository\AuditLog $auditRepo */
        $auditRepo = $this->repository('SG\Donations:AuditLog');
        $auditLogs = $auditRepo->findLogsForContent('donation', $donation->donation_id)->fetch();

        return $this->view(
            'SG\Donations:Donation\View',
            'sg_donations_donation_view',
            [
                'donation'  => $donation,
                'auditLogs' => $auditLogs,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Void donation
    // -------------------------------------------------------------------------

    public function actionVoid(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertAdminPermission('sgDonations_voidDonation');

        $donation = $this->assertDonationExists($params['donation_id']);

        if ($donation->isVoided()) {
            return $this->error(\XF::phrase('sg_donations_already_voided'));
        }

        if ($this->isPost()) {
            $voidReason = $this->filter('void_reason', 'str');

            /** @var \SG\Donations\Service\DonationVoider $voider */
            $voider = $this->service('SG\Donations:DonationVoider');
            $voider->setDonation($donation);
            $voider->setVoidReason($voidReason);
            $voider->setIpAddress($this->request->getIp());

            $errors = [];
            if (!$voider->validate($errors)) {
                return $this->error($errors);
            }

            $voider->void();

            return $this->redirect($this->buildLink('sg-donations/donations'));
        }

        return $this->view(
            'SG\Donations:Donation\Void',
            'sg_donations_donation_void',
            ['donation' => $donation]
        );
    }

    // -------------------------------------------------------------------------
    // Duplicate donation
    // -------------------------------------------------------------------------

    public function actionDuplicate(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertAdminPermission('sgDonations_addDonation');

        $donation = $this->assertDonationExists($params['donation_id']);

        return $this->redirect($this->buildLink('sg-donations/donations/add', null, [
            'user_id'     => $donation->user_id,
            'campaign_id' => $donation->campaign_id,
            'tier_id'     => $donation->tier_id,
        ]));
    }

    // -------------------------------------------------------------------------
    // AJAX: user preview
    // -------------------------------------------------------------------------

    public function actionUserPreview(): \XF\Mvc\Reply\AbstractReply
    {
        $userId = $this->filter('user_id', 'uint');

        /** @var \XF\Entity\User|null $user */
        $user = $this->em()->find('XF:User', $userId);
        if (!$user) {
            return $this->error(\XF::phrase('sg_donations_invalid_user'));
        }

        /** @var \SG\Donations\Repository\Membership $membershipRepo */
        $membershipRepo = $this->repository('SG\Donations:Membership');
        $memberships    = $membershipRepo->getMembershipsForUser($userId);

        /** @var \SG\Donations\Repository\DonationLog $donationRepo */
        $donationRepo = $this->repository('SG\Donations:DonationLog');
        $queued       = $donationRepo->findDonationsForList(['user_id' => $userId, 'status' => 'queued'])->fetch();

        return $this->view(
            'SG\Donations:Donation\UserPreview',
            'sg_donations_donation_user_preview',
            [
                'user'        => $user,
                'memberships' => $memberships,
                'queued'      => $queued,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // CSV export: filtered
    // -------------------------------------------------------------------------

    public function actionExportCsv(): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertAdminPermission('sgDonations_exportCsv');

        $filters = $this->getFilterInput();

        /** @var \SG\Donations\Repository\DonationLog $repo */
        $repo   = $this->repository('SG\Donations:DonationLog');
        $finder = $repo->findDonationsForList($filters);

        return $this->streamCsvResponse($finder, 'sg_donations_filtered_' . date('Ymd') . '.csv');
    }

    // -------------------------------------------------------------------------
    // CSV export: all donations
    // -------------------------------------------------------------------------

    public function actionExportCsvAll(): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertAdminPermission('sgDonations_exportCsv');

        /** @var \SG\Donations\Repository\DonationLog $repo */
        $repo   = $this->repository('SG\Donations:DonationLog');
        $finder = $repo->findDonationsForList([]);

        return $this->streamCsvResponse($finder, 'sg_donations_all_' . date('Ymd') . '.csv');
    }

    // -------------------------------------------------------------------------
    // AJAX: tiers for campaign (for Add donation form)
    // -------------------------------------------------------------------------

    public function actionTiersForCampaign(): \XF\Mvc\Reply\AbstractReply
    {
        $campaignId = $this->filter('campaign_id', 'uint');

        /** @var \SG\Donations\Repository\Tier $tierRepo */
        $tierRepo = $this->repository('SG\Donations:Tier');
        $tiers    = $tierRepo->getTiersForCampaignSelect($campaignId);

        return $this->view(
            'SG\Donations:Donation\TierSelect',
            'sg_donations_donation_tier_select',
            ['tiers' => $tiers]
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function getFilterInput(): array
    {
        return $this->filter([
            'campaign_id' => 'uint',
            'user_id'     => 'uint',
            'status'      => 'str',
        ]);
    }

    protected function assertDonationExists(int $id): \SG\Donations\Entity\DonationLog
    {
        $donation = $this->em()->find('SG\Donations:DonationLog', $id, ['User', 'Campaign', 'Tier']);
        if (!$donation) {
            throw $this->exception($this->notFound(\XF::phrase('sg_donations_donation_not_found')));
        }
        return $donation;
    }

    /**
     * Stream CSV output using chunked DB reads to avoid memory issues.
     */
    protected function streamCsvResponse(\XF\Mvc\Entity\Finder $finder, string $filename): \XF\Mvc\Reply\AbstractReply
    {
        $response = $this->app->response();
        $response->header('Content-Type', 'text/csv; charset=utf-8');
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $chunkSize  = 500;
        $offset     = 0;
        $csvContent = '';

        // Header row
        $headers = [
            'donation_id', 'user_id', 'username', 'campaign', 'tier',
            'amount', 'payment_ref', 'status', 'void_reason',
            'created_at', 'queued_until', 'applied_at', 'voided_at',
        ];
        $csvContent .= $this->csvRow($headers);

        do {
            $rows = $finder->limit($chunkSize, $offset)->fetch();
            $count = count($rows);
            $offset += $count;

            foreach ($rows as $donation) {
                $row = [
                    $donation->donation_id,
                    $donation->user_id,
                    $donation->User ? $donation->User->username : '',
                    $donation->Campaign ? $donation->Campaign->title : '',
                    $donation->Tier ? $donation->Tier->title : '',
                    number_format($donation->amount, 2, '.', ''),
                    $donation->payment_ref ?? '',
                    $donation->status,
                    $donation->void_reason ?? '',
                    $donation->created_at ? date('Y-m-d H:i:s', $donation->created_at) : '',
                    $donation->queued_until ? date('Y-m-d H:i:s', $donation->queued_until) : '',
                    $donation->applied_at ? date('Y-m-d H:i:s', $donation->applied_at) : '',
                    $donation->voided_at ? date('Y-m-d H:i:s', $donation->voided_at) : '',
                ];
                $csvContent .= $this->csvRow($row);
            }
        } while ($count >= $chunkSize);

        // Return a basic view that serves raw CSV
        return $this->view(
            'SG\Donations:Donation\CsvExport',
            '',
            ['csvContent' => $csvContent, 'filename' => $filename]
        );
    }

    protected function csvRow(array $fields): string
    {
        $escaped = array_map(function ($value) {
            $value = str_replace('"', '""', (string)$value);
            return '"' . $value . '"';
        }, $fields);
        return implode(',', $escaped) . "\r\n";
    }
}
