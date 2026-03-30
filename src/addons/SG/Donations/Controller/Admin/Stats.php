<?php

namespace SG\Donations\Controller\Admin;

use XF\Mvc\ParameterBag;
use XF\Admin\Controller\AbstractController;

class Stats extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('sgDonations_rebuildStats');
    }

    // -------------------------------------------------------------------------
    // Stats / rebuild tool
    // -------------------------------------------------------------------------

    public function actionIndex(): \XF\Mvc\Reply\AbstractReply
    {
        /** @var \SG\Donations\Repository\CampaignStats $statsRepo */
        $statsRepo = $this->repository('SG\Donations:CampaignStats');

        $stats = $this->app->finder('SG\Donations:CampaignStats')
            ->with('Campaign')
            ->order('campaign_id')
            ->fetch();

        return $this->view(
            'SG\Donations:Stats\Index',
            'sg_donations_stats_index',
            ['stats' => $stats]
        );
    }

    public function actionRebuild(): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertPostOnly();

        /** @var \SG\Donations\Repository\CampaignStats $statsRepo */
        $statsRepo = $this->repository('SG\Donations:CampaignStats');
        $count     = $statsRepo->rebuildAll();

        // Audit log
        /** @var \SG\Donations\Repository\AuditLog $auditRepo */
        $auditRepo = $this->repository('SG\Donations:AuditLog');
        $auditRepo->writeLog(
            'stats',
            0,
            'rebuild',
            null,
            ['campaigns_rebuilt' => $count]
        );

        return $this->redirect(
            $this->buildLink('sg-donations/stats'),
            \XF::phrase('sg_donations_stats_rebuilt_x_campaigns', ['count' => $count])
        );
    }

    // -------------------------------------------------------------------------
    // Widget settings
    // -------------------------------------------------------------------------

    public function actionWidgetSettings(): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertAdminPermission('sgDonations_manageWidget');

        /** @var \SG\Donations\Repository\WidgetSettings $settingsRepo */
        $settingsRepo = $this->repository('SG\Donations:WidgetSettings');
        $settings     = $settingsRepo->getSettings();

        /** @var \SG\Donations\Repository\Campaign $campaignRepo */
        $campaignRepo = $this->repository('SG\Donations:Campaign');
        $campaigns    = $campaignRepo->findCampaignsForList(true)->fetch();

        return $this->view(
            'SG\Donations:Stats\WidgetSettings',
            'sg_donations_widget_settings',
            [
                'settings'  => $settings,
                'campaigns' => $campaigns,
            ]
        );
    }

    public function actionWidgetSettingsSave(): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertPostOnly();
        $this->assertAdminPermission('sgDonations_manageWidget');

        /** @var \SG\Donations\Repository\WidgetSettings $settingsRepo */
        $settingsRepo = $this->repository('SG\Donations:WidgetSettings');
        $settings     = $settingsRepo->getSettings();

        $before = $settings->toArray();

        $input = $this->filter([
            'campaign_ids'              => 'array-uint',
            'show_ticker'               => 'bool',
            'show_progress_bar'         => 'bool',
            'show_goal'                 => 'bool',
            'hide_ended_campaign_tabs'  => 'bool',
            'ticker_speed'              => 'uint',
        ]);

        $settings->bulkSet($input);
        $settings->updated_at = \XF::$time;
        $settings->save();

        // Audit log
        /** @var \SG\Donations\Repository\AuditLog $auditRepo */
        $auditRepo = $this->repository('SG\Donations:AuditLog');
        $auditRepo->writeLog(
            'widget_settings',
            $settings->setting_id,
            'settings',
            $before,
            $settings->toArray()
        );

        return $this->redirect(
            $this->buildLink('sg-donations/stats/widget-settings'),
            \XF::phrase('sg_donations_widget_settings_saved')
        );
    }
}
