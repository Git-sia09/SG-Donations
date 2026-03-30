<?php

namespace SG\Donations\Controller\Admin;

use XF\Mvc\ParameterBag;
use XF\Admin\Controller\AbstractController;

class Tier extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('sgDonations_manageCampaigns');
    }

    // -------------------------------------------------------------------------
    // List tiers for a campaign
    // -------------------------------------------------------------------------

    public function actionIndex(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $campaign = $this->assertCampaignExists($params['campaign_id']);

        /** @var \SG\Donations\Repository\Tier $repo */
        $repo  = $this->repository('SG\Donations:Tier');
        $tiers = $repo->findTiersByCampaign($campaign->campaign_id)->fetch();

        return $this->view(
            'SG\Donations:Tier\Listing',
            'sg_donations_tier_list',
            ['campaign' => $campaign, 'tiers' => $tiers]
        );
    }

    // -------------------------------------------------------------------------
    // Add / edit tier
    // -------------------------------------------------------------------------

    protected function tierAddEdit(
        \SG\Donations\Entity\Tier $tier,
        \SG\Donations\Entity\Campaign $campaign
    ): \XF\Mvc\Reply\AbstractReply {
        $groups = $this->app->finder('XF:UserGroup')
            ->where('user_group_id', '!=', \SG\Donations\Entity\Tier::RESTRICTED_GROUP_IDS)
            ->order('title')
            ->fetch();

        return $this->view(
            'SG\Donations:Tier\Edit',
            'sg_donations_tier_edit',
            [
                'tier'     => $tier,
                'campaign' => $campaign,
                'groups'   => $groups,
            ]
        );
    }

    public function actionAdd(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $campaign = $this->assertCampaignExists($params['campaign_id']);
        /** @var \SG\Donations\Entity\Tier $tier */
        $tier = $this->em()->create('SG\Donations:Tier');
        $tier->campaign_id = $campaign->campaign_id;
        return $this->tierAddEdit($tier, $campaign);
    }

    public function actionEdit(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $tier     = $this->assertTierExists($params['tier_id']);
        $campaign = $this->assertCampaignExists($tier->campaign_id);
        return $this->tierAddEdit($tier, $campaign);
    }

    public function actionSave(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertPostOnly();

        if ($params['tier_id']) {
            $tier     = $this->assertTierExists($params['tier_id']);
            $campaign = $this->assertCampaignExists($tier->campaign_id);
        } else {
            $campaign = $this->assertCampaignExists($this->filter('campaign_id', 'uint'));
            /** @var \SG\Donations\Entity\Tier $tier */
            $tier = $this->em()->create('SG\Donations:Tier');
            $tier->campaign_id = $campaign->campaign_id;
        }

        $input = $this->filter([
            'title'          => 'str',
            'description'    => 'str',
            'amount'         => 'unum',
            'duration_months'=> 'uint',
            'user_group_ids' => 'array-uint',
            'display_order'  => 'uint',
            'active'         => 'bool',
        ]);

        $tier->bulkSet($input);

        if (!$tier->save()) {
            return $this->error($tier->getErrors());
        }

        // Audit log
        /** @var \SG\Donations\Repository\AuditLog $auditRepo */
        $auditRepo = $this->repository('SG\Donations:AuditLog');
        $auditRepo->writeLog(
            'tier',
            $tier->tier_id,
            $params['tier_id'] ? 'edit' : 'create',
            null,
            $tier->toArray()
        );

        return $this->redirect($this->buildLink('sg-donations/campaigns/tiers', $campaign));
    }

    // -------------------------------------------------------------------------
    // Toggle active
    // -------------------------------------------------------------------------

    public function actionToggle(): \XF\Mvc\Reply\AbstractReply
    {
        /** @var \XF\ControllerPlugin\Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        return $plugin->actionToggle('SG\Donations:Tier', 'active');
    }

    // -------------------------------------------------------------------------
    // Delete tier (guard: disallow if donation logs exist)
    // -------------------------------------------------------------------------

    public function actionDelete(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $tier     = $this->assertTierExists($params['tier_id']);
        $campaign = $this->assertCampaignExists($tier->campaign_id);

        /** @var \SG\Donations\Repository\Tier $tierRepo */
        $tierRepo = $this->repository('SG\Donations:Tier');

        try {
            $tierRepo->assertTierDeletable($tier);
        } catch (\LogicException $e) {
            return $this->error($e->getMessage());
        }

        if ($this->isPost()) {
            $tier->delete();
            return $this->redirect($this->buildLink('sg-donations/campaigns/tiers', $campaign));
        }

        return $this->view(
            'SG\Donations:Tier\Delete',
            'sg_donations_tier_delete',
            ['tier' => $tier, 'campaign' => $campaign]
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function assertCampaignExists(int $id): \SG\Donations\Entity\Campaign
    {
        $campaign = $this->em()->find('SG\Donations:Campaign', $id);
        if (!$campaign) {
            throw $this->exception($this->notFound(\XF::phrase('sg_donations_campaign_not_found')));
        }
        return $campaign;
    }

    protected function assertTierExists(int $id): \SG\Donations\Entity\Tier
    {
        $tier = $this->em()->find('SG\Donations:Tier', $id);
        if (!$tier) {
            throw $this->exception($this->notFound(\XF::phrase('sg_donations_tier_not_found')));
        }
        return $tier;
    }
}
