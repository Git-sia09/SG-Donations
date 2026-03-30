<?php

namespace SG\Donations\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * @property int tier_id
 * @property int campaign_id
 * @property string title
 * @property string|null description
 * @property float amount
 * @property int duration_months
 * @property array user_group_ids
 * @property int display_order
 * @property bool active
 *
 * @property-read Campaign Campaign
 */
class Tier extends Entity
{
    /**
     * XenForo default system groups that must not be granted by tiers.
     * 1 = Unregistered/Unconfirmed, 2 = Registered, 3 = Administrative
     */
    public const RESTRICTED_GROUP_IDS = [1, 2, 3];

    public static function getStructure(Structure $structure): Structure
    {
        $structure->table    = 'xf_sg_donations_tier';
        $structure->shortName = 'SG\Donations:Tier';
        $structure->primaryKey = 'tier_id';

        $structure->columns = [
            'tier_id'        => ['type' => self::UINT, 'autoIncrement' => true],
            'campaign_id'    => ['type' => self::UINT, 'required' => true],
            'title'          => ['type' => self::STR, 'maxLength' => 150, 'required' => 'sg_donations_tier_title_required'],
            'description'    => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'amount'         => ['type' => self::FLOAT, 'required' => true],
            'duration_months'=> ['type' => self::UINT, 'default' => 1, 'min' => 1],
            'user_group_ids' => ['type' => self::SERIALIZED_ARRAY, 'default' => []],
            'display_order'  => ['type' => self::UINT, 'default' => 10],
            'active'         => ['type' => self::BOOL, 'default' => true],
        ];

        $structure->relations = [
            'Campaign' => [
                'entity'     => 'SG\Donations:Campaign',
                'type'       => self::TO_ONE,
                'conditions' => 'campaign_id',
                'primary'    => true,
            ],
        ];

        return $structure;
    }

    protected function _preSave(): void
    {
        if ($this->isChanged('amount')) {
            $amount = round((float)$this->amount, 2);
            if ($amount <= 0) {
                $this->error(\XF::phrase('sg_donations_amount_must_be_positive'), 'amount');
            }
            $this->set('amount', $amount);
        }

        if ($this->isChanged('user_group_ids')) {
            $restricted = self::RESTRICTED_GROUP_IDS;
            foreach ($this->user_group_ids as $groupId) {
                if (in_array((int)$groupId, $restricted, true)) {
                    $this->error(\XF::phrase('sg_donations_restricted_group_not_allowed'), 'user_group_ids');
                    break;
                }
            }
        }
    }

    protected function _postDelete(): void
    {
        // Check for existing donation logs — enforced in service, but double-guard here
    }
}
