<?php

namespace App\Http\Controllers;

use App\Models\CloudBackup;
use App\Models\SyncConflict;
use App\Models\SyncInbox;
use App\Models\SyncOutbox;
use App\Services\Sync\CloudBackupService;
use App\Services\Sync\InstallationManager;
use App\Services\Sync\SyncPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SyncStatusController extends Controller
{
    public function index(InstallationManager $installations): View
    {
        return view('sync.index', ['installation' => $installations->current(), 'pending' => SyncOutbox::where('status', 'pending')->count(), 'failed' => SyncOutbox::where('status', 'failed')->count(), 'received' => SyncInbox::count(), 'conflicts' => SyncConflict::where('status', 'open')->count(), 'backups' => CloudBackup::latest()->limit(10)->get()]);
    }

    public function push(SyncPushService $sync): RedirectResponse
    {
        try {
            $count = $sync->push();

            return back()->with('success', "$count eventos confirmados.");
        } catch (Throwable) {
            return back()->with('error', 'No fue posible sincronizar. Los eventos se conservaron para reintento.');
        }
    }

    public function backup(Request $request, CloudBackupService $backups): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:RESPALDAR NUBE']]);
        try {
            $backups->create();

            return back()->with('success', 'Respaldo cifrado creado y verificado.');
        } catch (Throwable) {
            return back()->with('error', 'No se pudo completar el respaldo cifrado.');
        }
    }
}
