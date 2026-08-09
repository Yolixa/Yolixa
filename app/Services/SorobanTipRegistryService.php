<?php

namespace App\Services;

use App\Models\Tip;
use Illuminate\Support\Facades\Log;

class SorobanTipRegistryService
{
    public function recordTip(Tip $tip): array
    {
        if (!config('yolixa.soroban.enabled')) {
            return ['success' => true, 'status' => 'disabled'];
        }

        $missing = [];
        foreach (['rpc_url', 'tip_registry_contract_id', 'platform_signer_public', 'platform_signer_secret'] as $key) {
            if (blank(config("yolixa.soroban.{$key}"))) {
                $missing[] = $key;
            }
        }

        if ($missing) {
            return [
                'success' => false,
                'status' => 'not_configured',
                'message' => 'Soroban registry is enabled but missing config: ' . implode(', ', $missing),
            ];
        }

        Log::channel('stellar')->warning('Soroban Tip Registry invoke is scaffolded but not implemented by PHP service.', [
            'tip_id' => $tip->id,
            'contract_id' => config('yolixa.soroban.tip_registry_contract_id'),
        ]);

        return [
            'success' => false,
            'status' => 'not_implemented',
            'message' => 'Automated Laravel-to-Soroban invoke is not implemented yet. Use the Soroban contract scaffold and CLI deployment notes.',
        ];
    }

    public function retryFailed(Tip $tip): array
    {
        return $this->recordTip($tip);
    }
}
