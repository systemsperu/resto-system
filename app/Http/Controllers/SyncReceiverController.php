<?php

namespace App\Http\Controllers;

use App\Services\Sync\SyncInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncReceiverController extends Controller
{
    public function __invoke(Request $request, SyncInboxService $inbox): JsonResponse
    {
        abort_unless(config('sync.enabled') && filled(config('sync.token')) && hash_equals((string) config('sync.token'), (string) $request->bearerToken()), 401);
        $data = $request->validate(['events' => ['required', 'array', 'max:100'], 'events.*' => ['required', 'array']]);
        $acked = [];
        foreach ($data['events'] as $event) {
            $acked[] = $inbox->receive($event)->event_uuid;
        }

        return response()->json(['acked' => $acked]);
    }
}
