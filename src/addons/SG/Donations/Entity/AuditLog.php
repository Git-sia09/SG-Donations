<?php

namespace SG\Donations\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * @property int audit_id
 * @property string content_type
 * @property int content_id
 * @property string action
 * @property int admin_user_id
 * @property string|null ip_address
 * @property int event_date
 * @property string|null before_json
 * @property string|null after_json
 *
 * @property-read \XF\Entity\User|null Admin
 */
class AuditLog extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table    = 'xf_sg_donations_audit_log';
        $structure->shortName = 'SG\Donations:AuditLog';
        $structure->primaryKey = 'audit_id';

        $structure->columns = [
            'audit_id'      => ['type' => self::UINT, 'autoIncrement' => true],
            'content_type'  => ['type' => self::STR, 'maxLength' => 50],
            'content_id'    => ['type' => self::UINT, 'default' => 0],
            'action'        => ['type' => self::STR, 'maxLength' => 50],
            'admin_user_id' => ['type' => self::UINT, 'default' => 0],
            'ip_address'    => ['type' => self::BINARY, 'maxLength' => 16, 'nullable' => true, 'default' => null],
            'event_date'    => ['type' => self::UINT, 'default' => \XF::$time],
            'before_json'   => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'after_json'    => ['type' => self::STR, 'nullable' => true, 'default' => null],
        ];

        $structure->relations = [
            'Admin' => [
                'entity'     => 'XF:User',
                'type'       => self::TO_ONE,
                'conditions' => [['admin_user_id', '=', '$user_id']],
                'primary'    => true,
            ],
        ];

        return $structure;
    }
}
