<?php

namespace SG\Donations\Widget;

use XF\Widget\AbstractWidget;

class DonationsSummary extends AbstractWidget
{
    public function render(): string|\XF\Widget\WidgetRendererInterface
    {
        /** @var \SG\Donations\Repository\WidgetSettings $settingsRepo */
        $settingsRepo = $this->app->repository('SG\Donations:WidgetSettings');
        $settings     = $settingsRepo->getSettings();

        /** @var \SG\Donations\Repository\Campaign $campaignRepo */
        $campaignRepo = $this->app->repository('SG\Donations:Campaign');

        $campaignIds = $settings->campaign_ids ?: [];

        if (empty($campaignIds)) {
            $campaigns = $campaignRepo->getActiveCampaignsWithStats();
        } else {
            $campaigns = $this->app->finder('SG\Donations:Campaign')
                ->with('Stats')
                ->where('campaign_id', $campaignIds)
                ->where('active', 1)
                ->order('display_order')
                ->fetch()
                ->toArray();
        }

        // Optionally hide ended campaigns
        if ($settings->hide_ended_campaign_tabs) {
            $now = \XF::$time;
            $campaigns = array_filter($campaigns, function ($c) use ($now) {
                return $c->end_date === 0 || $c->end_date > $now;
            });
        }

        // Build per-campaign donors list, repeating if < 10 for smooth loop
        $campaignData = [];
        foreach ($campaigns as $campaign) {
            $stats  = $campaign->Stats;
            $donors = $stats ? $stats->getLatestDonors() : [];

            // Repeat list if < 10 donors for smooth ticker loop
            if (count($donors) > 0 && count($donors) < 10) {
                $repeated = [];
                while (count($repeated) < 10) {
                    $repeated = array_merge($repeated, $donors);
                }
                $donors = array_slice($repeated, 0, 10);
            }

            $campaignData[] = [
                'campaign' => $campaign,
                'stats'    => $stats,
                'donors'   => $donors,
            ];
        }

        return $this->renderer('sg_donations_widget_summary', [
            'campaignData' => $campaignData,
            'settings'     => $settings,
        ]);
    }

    public static function getDefaultOptions(): array
    {
        return [];
    }

    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null): bool
    {
        return true;
    }
}
