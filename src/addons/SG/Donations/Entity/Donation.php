<?php

declare(strict_types=1);

namespace SG\Donations\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * @property int $donation_id
 * @property int $user_id
 * @property string $username
 * @property float $amount
 * @property string $currency
 * @property string|null $message
 * @property int $donation_date
 * @property int $visible
 *
 * @property-read \XF\Entity\User|null $User
 */
class Donation extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_sg_donation';
        $structure->shortName = 'SG\Donations:Donation';
        $structure->primaryKey = 'donation_id';
        $structure->columns = [
            'donation_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'user_id'     => ['type' => self::UINT, 'default' => 0],
            'username'    => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'amount'      => ['type' => self::FLOAT, 'default' => 0.0],
            'currency'    => ['type' => self::STR, 'maxLength' => 10, 'default' => 'USD'],
            'message'     => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'donation_date' => ['type' => self::UINT, 'default' => 0],
            'visible'     => ['type' => self::UINT, 'default' => 1],
        ];
        $structure->relations = [
            'User' => [
                'entity'     => 'XF:User',
                'type'       => self::TO_ONE,
                'conditions' => 'user_id',
                'primary'    => true,
            ],
        ];

        return $structure;
    }
}
