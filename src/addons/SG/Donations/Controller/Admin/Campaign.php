<?php

namespace SG\Donations\Controller\Admin;

use XF\Mvc\ParameterBag;
use XF\Admin\Controller\AbstractController;

class Campaign extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('sgDonations_manageCampaigns');
    }

    // -------------------------------------------------------------------------
    // Campaign list
    // -------------------------------------------------------------------------

    public function actionIndex(): \XF\Mvc\Reply\AbstractReply
    {
        /** @var \SG\Donations\Repository\Campaign $repo */
        $repo     = $this->repository('SG\Donations:Campaign');
        $campaigns = $repo->findCampaignsForList()->fetch();

        return $this->view(
            'SG\Donations:Campaign\Listing',
            'sg_donations_campaign_list',
            ['campaigns' => $campaigns]
        );
    }

    // -------------------------------------------------------------------------
    // Add / edit campaign
    // -------------------------------------------------------------------------

    protected function campaignAddEdit(\SG\Donations\Entity\Campaign $campaign): \XF\Mvc\Reply\AbstractReply
    {
        return $this->view(
            'SG\Donations:Campaign\Edit',
            'sg_donations_campaign_edit',
            ['campaign' => $campaign]
        );
    }

    public function actionAdd(): \XF\Mvc\Reply\AbstractReply
    {
        /** @var \SG\Donations\Entity\Campaign $campaign */
        $campaign = $this->em()->create('SG\Donations:Campaign');
        return $this->campaignAddEdit($campaign);
    }

    public function actionEdit(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $campaign = $this->assertCampaignExists($params['campaign_id']);
        return $this->campaignAddEdit($campaign);
    }

    public function actionSave(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertPostOnly();

        if ($params['campaign_id']) {
            $campaign = $this->assertCampaignExists($params['campaign_id']);
        } else {
            /** @var \SG\Donations\Entity\Campaign $campaign */
            $campaign = $this->em()->create('SG\Donations:Campaign');
        }

        $input = $this->filter([
            'title'          => 'str',
            'description'    => 'str',
            'currency_label' => 'str',
            'goal_amount'    => 'unum',
            'start_date'     => 'str',
            'end_date'       => 'str',
            'active'         => 'bool',
            'display_order'  => 'uint',
        ]);

        $campaign->bulkSet($input);
        $campaign->start_date = $input['start_date'] ? strtotime($input['start_date']) : 0;
        $campaign->end_date   = $input['end_date']   ? strtotime($input['end_date'])   : 0;
        $campaign->created_at = $campaign->isInsert() ? \XF::$time : $campaign->created_at;

        if (!$campaign->save()) {
            return $this->error($campaign->getErrors());
        }

        // Audit log
        /** @var \SG\Donations\Repository\AuditLog $auditRepo */
        $auditRepo = $this->repository('SG\Donations:AuditLog');
        $auditRepo->writeLog(
            'campaign',
            $campaign->campaign_id,
            $params['campaign_id'] ? 'edit' : 'create',
            null,
            $campaign->toArray()
        );

        return $this->redirect($this->buildLink('sg-donations/campaigns'));
    }

    // -------------------------------------------------------------------------
    // Toggle active
    // -------------------------------------------------------------------------

    public function actionToggle(): \XF\Mvc\Reply\AbstractReply
    {
        /** @var \XF\ControllerPlugin\Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        return $plugin->actionToggle('SG\Donations:Campaign', 'active');
    }

    // -------------------------------------------------------------------------
    // Delete campaign
    // -------------------------------------------------------------------------

    public function actionDelete(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $campaign = $this->assertCampaignExists($params['campaign_id']);

        if ($this->isPost()) {
            $campaign->delete();
            return $this->redirect($this->buildLink('sg-donations/campaigns'));
        }

        return $this->view(
            'SG\Donations:Campaign\Delete',
            'sg_donations_campaign_delete',
            ['campaign' => $campaign]
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function assertCampaignExists(int $id): \SG\Donations\Entity\Campaign
    {
        /** @var \SG\Donations\Entity\Campaign|null $campaign */
        $campaign = $this->em()->find('SG\Donations:Campaign', $id);
        if (!$campaign) {
            throw $this->exception($this->notFound(\XF::phrase('sg_donations_campaign_not_found')));
        }
        return $campaign;
    }
}
