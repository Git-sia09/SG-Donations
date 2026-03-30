<?php

namespace SG\Donations\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * @property int campaign_id
 * @property float raised_total
 * @property string|null latest_donors_json
 * @property int last_rebuild_at
 *
 * @property-read Campaign Campaign
 */
class CampaignStats extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table    = 'xf_sg_donations_campaign_stats';
        $structure->shortName = 'SG\Donations:CampaignStats';
        $structure->primaryKey = 'campaign_id';

        $structure->columns = [
            'campaign_id'        => ['type' => self::UINT],
            'raised_total'       => ['type' => self::FLOAT, 'default' => 0],
            'latest_donors_json' => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'last_rebuild_at'    => ['type' => self::UINT, 'default' => 0],
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

    public function getLatestDonors(): array
    {
        if ($this->latest_donors_json === null) {
            return [];
        }
        return json_decode($this->latest_donors_json, true) ?: [];
    }
}
