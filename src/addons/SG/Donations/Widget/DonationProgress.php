<?php

declare(strict_types=1);

namespace SG\Donations\Widget;

use XF\Widget\AbstractWidget;
use XF\Widget\WidgetRenderer;

class DonationProgress extends AbstractWidget
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        \XF\App $app,
        array $widgetConfig,
        array $options = []
    ) {
        parent::__construct($app, $widgetConfig, $options);
    }

    public function render(): string|WidgetRenderer
    {
        $options = $this->app->options();

        $goal     = (float) ($options->sgDonationsGoal ?? 1000);
        $currency = (string) ($options->sgDonationsCurrency ?? 'USD');
        $title    = (string) ($options->sgDonationsTitle ?? 'Donation Goal');

        /** @var \SG\Donations\Repository\Donation $repo */
        $repo = $this->app->repository('SG\Donations:Donation');
        $totalDonated = $repo->getTotalDonated();

        $percent = $goal > 0
            ? (int) min(100, round($totalDonated / $goal * 100))
            : 0;

        return $this->renderer('sg_donations_widget_progress', [
            'title'        => $title,
            'totalDonated' => $totalDonated,
            'goal'         => $goal,
            'currency'     => $currency,
            'percent'      => $percent,
        ]);
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function verifyOptions(
        \XF\Http\Request $request,
        array &$options,
        ?string &$error = null
    ): bool {
        return true;
    }
}
