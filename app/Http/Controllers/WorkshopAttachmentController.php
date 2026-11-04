<?php

namespace App\Http\Controllers;

use App\Models\DamiOrder;
use App\Models\Workshop\Production;
use App\Models\Workshop\Purchase;
use App\Models\Workshop\Task;
use Illuminate\Support\Facades\Storage;

class WorkshopAttachmentController extends Controller
{
    public function __invoke(string $type, int $id, int $index = 0)
    {
        abort_unless(auth()->user()?->canWorkshop('sale') || auth()->user()?->canWorkshop('production') || auth()->user()?->canWorkshop('purchase'), 403);
        [$record, $path] = match ($type) {
            'order' => (function () use ($id, $index) {
                $r = DamiOrder::where('workshop', true)->findOrFail($id);

                return [$r, $r->attachments[$index] ?? null];
            })(),
            'production' => (function () use ($id) {
                $r = Production::findOrFail($id);

                return [$r, $r->attachment];
            })(),
            'purchase' => (function () use ($id) {
                $r = Purchase::findOrFail($id);
                abort_unless(auth()->user()->isWorkshopAdmin() || $r->user_id === auth()->id(), 403);

                return [$r, $r->receipt];
            })(),
            'task' => (function () use ($id) {
                $r = Task::findOrFail($id);
                abort_unless(auth()->user()->isWorkshopAdmin() || $r->responsible_id === auth()->id(), 403);

                return [$r, $r->file_path];
            })(),
            default => abort(404),
        };
        abort_unless($path && str_starts_with($path, 'workshop/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, basename($path), ['X-Content-Type-Options' => 'nosniff']);
    }
}
