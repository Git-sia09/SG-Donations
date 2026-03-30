<?php

namespace SG\Donations\Repository;

use SG\Donations\Entity\WidgetSettings as WidgetSettingsEntity;
use XF\Mvc\Entity\Repository;

class WidgetSettings extends Repository
{
    public function getSettings(): WidgetSettingsEntity
    {
        /** @var WidgetSettingsEntity|null $settings */
        $settings = $this->finder('SG\Donations:WidgetSettings')
            ->order('setting_id')
            ->fetchOne();

        if (!$settings) {
            // Create default settings row
            /** @var WidgetSettingsEntity $settings */
            $settings = $this->em->create('SG\Donations:WidgetSettings');
            $settings->save();
        }

        return $settings;
    }
}
