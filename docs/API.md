# HotelCRM REST API

Base URL: `https://your-host/api/v1`
All responses are JSON: `{ "success": true, "data": ... }` or `{ "success": false, "error": "..." }`.

## Authentication

Create a token in **Settings → API Keys** (shown once). Send it as a bearer header:

```
Authorization: Bearer <token>
```

Requests are rate-limited per IP (default 60/min). Unauthorized requests return `401`.

---

## Rooms

### `GET /rooms`
List rooms with type + floor.

### `GET /rooms/availability?check_in=YYYY-MM-DD&check_out=YYYY-MM-DD[&room_type_id=]`
Rooms free for the date range.

```json
{ "success": true, "data": { "available": [ ... ], "count": 4 } }
```

---

## Bookings

### `GET /bookings?page=1`
Paginated reservations with guest name.

### `GET /bookings/{id}`
A reservation with its rooms.

### `POST /bookings`
```json
{
  "check_in": "2026-08-01",
  "check_out": "2026-08-03",
  "adults": 2,
  "children": 0,
  "source": "website",
  "guest": { "first_name": "Rahul", "last_name": "Sharma", "phone": "9812345678", "email": "r@x.com" },
  "rooms": [ { "room_type_id": 2, "rate": 4000 } ]
}
```
Returns `201` with the created reservation. Fires WhatsApp + email confirmation.

### `PUT /bookings/{id}`
Update `check_in`, `check_out`, `adults`, `children`, `status`, `special_requests`.

### `DELETE /bookings/{id}`
Cancel a reservation.

---

## Guests

### `GET /guests?q=&page=1`
### `POST /guests`
```json
{ "first_name": "Asha", "phone": "9800000000", "email": "asha@x.com", "city": "Pune" }
```

---

## Billing

### `GET /invoices?page=1`
### `GET /invoices/{id}` — invoice with items + payments
### `POST /payments`
```json
{ "invoice_id": 12, "amount": 5000, "method": "upi", "type": "payment" }
```

---

## Reports

### `GET /reports/{type}?from=YYYY-MM-DD&to=YYYY-MM-DD`
`type` ∈ `occupancy | revenue | adr | daily-sales | gst | housekeeping | ota | source | collection | staff`.

---

## Public Booking Engine (no token)

For the hotel's own website widget. Rate-limited.

### `GET /api/public/availability?hotel_id=1&check_in=&check_out=`
Availability + pricing per room type.

### `POST /api/public/book`
Same body shape as `POST /bookings`. Returns `{ code, total }`.

---

## OTA Webhooks

### `POST /api/public/ota/{channel}/webhook`
Inbound OTA push (new/modified/cancelled bookings). The payload is queued as a
sync job and processed asynchronously by `cli/worker.php`. Verify the OTA's
signature inside `OtaWebhookController` once the partner shares the signing key.

---

## Error Codes

| Code | Meaning |
|------|---------|
| 401 | Missing/invalid token |
| 403 | Insufficient permission |
| 404 | Not found |
| 419 | CSRF token mismatch (web only) |
| 422 | Validation error |
| 429 | Rate limit exceeded |
| 500 | Server error (logged) |
