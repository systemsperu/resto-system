<?php

namespace App\Services\Marketing;

use App\Models\Campaign;
use App\Models\CampaignDelivery;
use App\Models\Client;

class CampaignService
{
    public function simulate(Campaign $campaign): array
    {
        if (config('marketing.transport') !== 'fake' || config('marketing.external_enabled')) {
            throw new \RuntimeException('Las campañas externas no están habilitadas en esta instalación.');
        }

        $clients = $campaign->segment
            ? $campaign->segment->clients()->get()
            : Client::query()->get();
        $counts = ['simulated' => 0, 'skipped' => 0];

        foreach ($clients as $client) {
            $contact = $campaign->channel === 'email' ? $client->email : $client->phone;
            $allowed = filled($contact) && $client->hasConsent($campaign->channel);
            $status = $allowed ? 'simulated' : 'skipped';
            CampaignDelivery::firstOrCreate(
                ['idempotency_key' => hash('sha256', $campaign->id.'|'.$client->id.'|'.$campaign->channel)],
                ['campaign_id' => $campaign->id, 'client_id' => $client->id, 'status' => $status, 'delivered_at' => $allowed ? now() : null, 'failure_reason' => $allowed ? null : 'Sin consentimiento vigente o contacto']
            );
            $counts[$status]++;
        }

        $campaign->update(['status' => 'simulated', 'sent_at' => now()]);

        return $counts;
    }
}
