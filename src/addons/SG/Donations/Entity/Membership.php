<?php

namespace SG\Donations\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * @property int membership_id
 * @property int user_id
 * @property int campaign_id
 * @property int tier_id
 * @property int donation_id
 * @property int start_date
 * @property int end_date
 * @property array granted_group_ids
 * @property bool active
 * @property int created_at
 *
 * @property-read \XF\Entity\User User
 * @property-read Campaign Campaign
 * @property-read Tier Tier
 */
class Membership extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table    = 'xf_sg_donations_membership';
        $structure->shortName = 'SG\Donations:Membership';
        $structure->primaryKey = 'membership_id';

        $structure->columns = [
            'membership_id'    => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id'          => ['type' => self::UINT, 'required' => true],
            'campaign_id'      => ['type' => self::UINT, 'required' => true],
            'tier_id'          => ['type' => self::UINT, 'required' => true],
            'donation_id'      => ['type' => self::UINT, 'required' => true],
            'start_date'       => ['type' => self::UINT, 'default' => 0],
            'end_date'         => ['type' => self::UINT, 'default' => 0],
            'granted_group_ids'=> ['type' => self::SERIALIZED_ARRAY, 'default' => []],
            'active'           => ['type' => self::BOOL, 'default' => true],
            'created_at'       => ['type' => self::UINT, 'default' => \XF::$time],
        ];

        $structure->relations = [
            'User' => [
                'entity'     => 'XF:User',
                'type'       => self::TO_ONE,
                'conditions' => 'user_id',
                'primary'    => true,
            ],
            'Campaign' => [
                'entity'     => 'SG\Donations:Campaign',
                'type'       => self::TO_ONE,
                'conditions' => 'campaign_id',
                'primary'    => true,
            ],
            'Tier' => [
                'entity'     => 'SG\Donations:Tier',
                'type'       => self::TO_ONE,
                'conditions' => 'tier_id',
                'primary'    => true,
            ],
        ];

        return $structure;
    }

    public function isActive(): bool
    {
        return $this->active && $this->end_date > \XF::$time;
    }
}
