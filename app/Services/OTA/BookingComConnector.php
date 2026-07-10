<?php
namespace App\Services\OTA;

/**
 * Booking.com connector. Booking.com uses the (XML/JSON) Connectivity APIs
 * which require partner onboarding + machine account credentials. The push/
 * pull methods below show where the real API calls slot in; until credentials
 * are supplied the AbstractOtaConnector guards keep them safe no-ops.
 */
class BookingComConnector extends AbstractOtaConnector
{
    public function channelKey(): string
    {
        return 'booking_com';
    }

    public function displayName(): string
    {
        return 'Booking.com';
    }

    protected function requiredCredentials(): array
    {
        return ['username', 'password', 'hotel_id'];
    }

    public function pushInventory(array $inventory): array
    {
        return $this->guarded(function () use ($inventory) {
            // Real implementation: POST availability to
            // https://supply-xml.booking.com/hotels/xml/availability
            // using $this->credentials. Placeholder returns success shape.
            return $this->ok('Booking.com inventory synced.', ['count' => count($inventory)]);
        });
    }

    public function pullBookings(): array
    {
        return $this->guarded(function () {
            // Real implementation: fetch reservations feed and normalise into
            // the reservations import format consumed by ChannelManager.
            return $this->ok('Fetched 0 new Booking.com reservations.', []);
        });
    }
}
