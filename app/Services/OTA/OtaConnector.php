<?php
namespace App\Services\OTA;

/**
 * Contract every OTA connector implements. Concrete connectors (Booking.com,
 * Agoda, Expedia…) extend AbstractOtaConnector and are registered in
 * ChannelManager::CONNECTORS. Adding a new OTA never requires touching the
 * core — this is the plugin seam the master spec calls for.
 *
 * All methods return a normalised result array:
 *   ['success' => bool, 'message' => string, 'data' => mixed]
 */
interface OtaConnector
{
    public function channelKey(): string;
    public function displayName(): string;

    /** Verify stored credentials / connectivity. */
    public function testConnection(): array;

    /** Push availability + inventory for a date range. */
    public function pushInventory(array $inventory): array;

    /** Push rates / prices. */
    public function pushRates(array $rates): array;

    /** Push restrictions (stop-sell, min/max stay). */
    public function pushRestrictions(array $restrictions): array;

    /** Pull new/modified/cancelled bookings from the OTA. */
    public function pullBookings(): array;
}
