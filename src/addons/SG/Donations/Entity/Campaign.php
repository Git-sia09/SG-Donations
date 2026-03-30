<?php

namespace SG\Donations\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * @property int campaign_id
 * @property string title
 * @property string|null description
 * @property string currency_label
 * @property float goal_amount
 * @property int start_date
 * @property int end_date
 * @property bool active
 * @property int display_order
 * @property int created_at
 *
 * @property-read \SG\Donations\Entity\CampaignStats|null Stats
 * @property-read \XF\Mvc\Entity\AbstractCollection Tiers
 * @property-read \XF\Mvc\Entity\AbstractCollection Donations
 */
class Campaign extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table    = 'xf_sg_donations_campaign';
        $structure->shortName = 'SG\Donations:Campaign';
        $structure->primaryKey = 'campaign_id';

        $structure->columns = [
            'campaign_id'    => ['type' => self::UINT, 'autoIncrement' => true],
            'title'          => ['type' => self::STR, 'maxLength' => 150, 'required' => 'sg_donations_campaign_title_required'],
            'description'    => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'currency_label' => ['type' => self::STR, 'maxLength' => 10, 'default' => 'USD', 'required' => 'sg_donations_currency_label_required'],
            'goal_amount'    => ['type' => self::FLOAT, 'default' => 0],
            'start_date'     => ['type' => self::UINT, 'default' => 0],
            'end_date'       => ['type' => self::UINT, 'default' => 0],
            'active'         => ['type' => self::BOOL, 'default' => true],
            'display_order'  => ['type' => self::UINT, 'default' => 10],
            'created_at'     => ['type' => self::UINT, 'default' => \XF::$time],
        ];

        $structure->relations = [
            'Stats' => [
                'entity'     => 'SG\Donations:CampaignStats',
                'type'       => self::TO_ONE,
                'conditions' => 'campaign_id',
                'primary'    => true,
            ],
            'Tiers' => [
                'entity'     => 'SG\Donations:Tier',
                'type'       => self::TO_MANY,
                'conditions' => 'campaign_id',
                'order'      => ['display_order', 'ASC'],
            ],
            'Donations' => [
                'entity'     => 'SG\Donations:DonationLog',
                'type'       => self::TO_MANY,
                'conditions' => 'campaign_id',
                'order'      => ['created_at', 'DESC'],
            ],
        ];

        return $structure;
    }

    public function isEnded(): bool
    {
        return $this->end_date > 0 && $this->end_date < \XF::$time;
    }

    protected function _preSave(): void
    {
        if ($this->isChanged('currency_label') && $this->currency_label === '') {
            $this->error(\XF::phrase('sg_donations_currency_label_required'), 'currency_label');
        }

        if ($this->isChanged('goal_amount') && $this->goal_amount < 0) {
            $this->error(\XF::phrase('sg_donations_goal_amount_invalid'), 'goal_amount');
        }
    }
}
