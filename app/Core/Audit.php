<?php
namespace App\Core;

/** Writes audit & activity trail entries. */
class Audit
{
    /**
     * Record an action against an entity.
     *
     * @param string $action e.g. 'reservation.create'
     * @param string $entity e.g. 'reservation'
     */
    public static function log(string $action, string $entity, $entityId = null, array $meta = []): void
    {
        try {
            App::db()->insert('audit_logs', [
                'user_id'    => Auth::id(),
                'hotel_id'   => Auth::hotelId(),
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'meta'       => $meta ? json_encode($meta) : null,
                'ip_address' => Request::ip(),
                'user_agent' => substr(Request::userAgent(), 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Logger::warn('Audit write failed: ' . $e->getMessage());
        }
    }
}
