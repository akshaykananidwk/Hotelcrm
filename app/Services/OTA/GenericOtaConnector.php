<?php
namespace App\Services\OTA;

/**
 * Generic connector used for OTAs that share a conventional REST partner API
 * (MakeMyTrip, Goibibo, Agoda, Expedia, Hotels.com, Trip.com, Hostelworld,
 * Airbnb…). Each is instantiated with its channel key + display name so a new
 * OTA can be onboarded purely through configuration.
 */
class GenericOtaConnector extends AbstractOtaConnector
{
    private string $channel;
    private string $name;

    public function __construct(int $hotelId, array $credentials, string $channel = 'generic', string $name = 'OTA')
    {
        parent::__construct($hotelId, $credentials);
        $this->channel = $channel;
        $this->name = $name;
    }

    public function channelKey(): string
    {
        return $this->channel;
    }

    public function displayName(): string
    {
        return $this->name;
    }

    protected function requiredCredentials(): array
    {
        return ['api_key'];
    }
}
