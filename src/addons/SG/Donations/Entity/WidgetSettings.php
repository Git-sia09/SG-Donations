<?php

namespace SG\Donations\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * @property int setting_id
 * @property array campaign_ids
 * @property bool show_ticker
 * @property bool show_progress_bar
 * @property bool show_goal
 * @property bool hide_ended_campaign_tabs
 * @property int ticker_speed
 * @property int updated_at
 */
class WidgetSettings extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table    = 'xf_sg_donations_widget_settings';
        $structure->shortName = 'SG\Donations:WidgetSettings';
        $structure->primaryKey = 'setting_id';

        $structure->columns = [
            'setting_id'               => ['type' => self::UINT, 'autoIncrement' => true],
            'campaign_ids'             => ['type' => self::SERIALIZED_ARRAY, 'default' => []],
            'show_ticker'              => ['type' => self::BOOL, 'default' => true],
            'show_progress_bar'        => ['type' => self::BOOL, 'default' => true],
            'show_goal'                => ['type' => self::BOOL, 'default' => true],
            'hide_ended_campaign_tabs' => ['type' => self::BOOL, 'default' => false],
            'ticker_speed'             => ['type' => self::UINT, 'default' => 30],
            'updated_at'               => ['type' => self::UINT, 'default' => \XF::$time],
        ];

        return $structure;
    }
}
