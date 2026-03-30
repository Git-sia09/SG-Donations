<?php

namespace SG\Donations\Repository;

use XF\Mvc\Entity\Repository;

class AuditLog extends Repository
{
    public function writeLog(
        string $contentType,
        int    $contentId,
        string $action,
        ?array $before = null,
        ?array $after  = null,
        ?int   $adminUserId = null,
        ?string $ipAddress = null
    ): void {
        if ($adminUserId === null) {
            $visitor = \XF::visitor();
            $adminUserId = $visitor ? (int)$visitor->user_id : 0;
        }

        $this->db()->insert('xf_sg_donations_audit_log', [
            'content_type'  => $contentType,
            'content_id'    => $contentId,
            'action'        => $action,
            'admin_user_id' => $adminUserId,
            'ip_address'    => $ipAddress,
            'event_date'    => \XF::$time,
            'before_json'   => $before !== null ? json_encode($before) : null,
            'after_json'    => $after !== null ? json_encode($after) : null,
        ]);
    }

    public function findLogsForContent(string $contentType, int $contentId): \XF\Mvc\Entity\Finder
    {
        return $this->finder('SG\Donations:AuditLog')
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->order('event_date', 'DESC');
    }

    public function findAllLogs(): \XF\Mvc\Entity\Finder
    {
        return $this->finder('SG\Donations:AuditLog')
            ->order('event_date', 'DESC');
    }
}
