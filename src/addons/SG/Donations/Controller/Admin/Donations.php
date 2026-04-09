<?php

namespace SG\Donations\Controller\Admin;

use XF\Admin\Controller\AbstractController;

class Donations extends AbstractController
{
    public function actionIndex()
    {
        // Default landing page
        return $this->rerouteController(__CLASS__, 'campaigns');
    }

    public function actionDonations()
    {
        return $this->rerouteController('SG\Donations:Admin\Donation', 'index');
    }

    public function actionCampaigns()
    {
        return $this->rerouteController('SG\Donations:Admin\Campaign', 'index');
    }

    public function actionTiers()
    {
        return $this->rerouteController('SG\Donations:Admin\Tier', 'index');
    }

    public function actionStats()
    {
        return $this->rerouteController('SG\Donations:Admin\Stats', 'index');
    }

    // Only keep this if your Stats controller has actionWidgetSettings()
    public function actionWidgetSettings()
    {
        return $this->rerouteController('SG\Donations:Admin\Stats', 'widgetSettings');
    }
}
