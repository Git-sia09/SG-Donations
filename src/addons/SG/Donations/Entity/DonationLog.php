<?php

namespace SG\Donations\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * @property int donation_id
 * @property int user_id
 * @property int campaign_id
 * @property int tier_id
 * @property float amount
 * @property string|null payment_ref
 * @property string status  queued|applied|voided
 * @property int queued_until
 * @property int applied_at
 * @property int applied_by
 * @property int voided_at
 * @property int voided_by
 * @property string|null void_reason
 * @property int created_at
 * @property int created_by
 * @property string|null created_ip
 * @property string|null voided_ip
 * @property string|null applied_ip
 *
 * @property-read \XF\Entity\User|null User
 * @property-read Campaign Campaign
 * @property-read Tier Tier
 */
class DonationLog extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table    = 'xf_sg_donations_log';
        $structure->shortName = 'SG\Donations:DonationLog';
        $structure->primaryKey = 'donation_id';

        $structure->columns = [
            'donation_id'  => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id'      => ['type' => self::UINT, 'required' => true],
            'campaign_id'  => ['type' => self::UINT, 'required' => true],
            'tier_id'      => ['type' => self::UINT, 'required' => true],
            'amount'       => ['type' => self::FLOAT, 'required' => true],
            'payment_ref'  => ['type' => self::STR, 'maxLength' => 100, 'nullable' => true, 'default' => null],
            'status'       => ['type' => self::STR, 'default' => 'queued',
                               'allowedValues' => ['queued', 'applied', 'voided']],
            'queued_until' => ['type' => self::UINT, 'default' => 0],
            'applied_at'   => ['type' => self::UINT, 'default' => 0],
            'applied_by'   => ['type' => self::UINT, 'default' => 0],
            'voided_at'    => ['type' => self::UINT, 'default' => 0],
            'voided_by'    => ['type' => self::UINT, 'default' => 0],
            'void_reason'  => ['type' => self::STR, 'maxLength' => 500, 'nullable' => true, 'default' => null],
            'created_at'   => ['type' => self::UINT, 'default' => \XF::$time],
            'created_by'   => ['type' => self::UINT, 'default' => 0],
            'created_ip'   => ['type' => self::BINARY, 'maxLength' => 16, 'nullable' => true, 'default' => null],
            'voided_ip'    => ['type' => self::BINARY, 'maxLength' => 16, 'nullable' => true, 'default' => null],
            'applied_ip'   => ['type' => self::BINARY, 'maxLength' => 16, 'nullable' => true, 'default' => null],
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

    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }
}
