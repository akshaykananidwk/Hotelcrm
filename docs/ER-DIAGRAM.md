# Entity-Relationship Overview

Multi-tenant: most tables carry `hotel_id`. Text diagram (see `database/schema.sql` for full DDL).

```
roles ──< role_permissions >── permissions
  │
  └──< users >── hotels ──< floors
                   │   ├──< amenities
                   │   ├──< room_types ──< rooms ──┐
                   │   │        │                  │
                   │   │        └──< room_inventory│
                   │   ├──< guests ──< guest_feedback
                   │   │        │
                   │   │        └──< reservations ──< reservation_rooms >── rooms
                   │   │                  │
                   │   │                  ├──< invoices ──< invoice_items
                   │   │                  │       │
                   │   │                  │       └──< payments
                   │   │                  └──< wifi_vouchers
                   │   ├──< housekeeping_tasks >── rooms
                   │   ├──< lost_and_found
                   │   ├──< ota_channels
                   │   ├──< sync_jobs
                   │   ├──< promo_codes
                   │   ├──< message_templates
                   │   └──< settings
                   │
users ──< api_tokens
        ──< login_logs
audit_logs (user_id, hotel_id)
notifications (hotel_id, user_id)
```

## Key relationships

- **hotels 1—N room_types 1—N rooms** — physical inventory.
- **room_inventory** — per-date availability/rate/restrictions used by the Channel Manager & booking engine.
- **reservations 1—N reservation_rooms** — a booking can span multiple rooms; each line references a room_type and (once assigned) a room.
- **invoices 1—N invoice_items** and **invoices 1—N payments** — billing; invoice totals recomputed from items + successful payments.
- **ota_channels / sync_jobs** — channel credentials and the async sync queue (with attempts/backoff).
- **settings** — grouped key/value store for all integration credentials, per hotel.
- **audit_logs / login_logs** — full activity & security trail.

## Reference integrity

Foreign keys use `ON DELETE CASCADE` for tenant-owned data and `ON DELETE SET NULL`
where a soft reference should survive (e.g. a room removed from a past reservation line).
