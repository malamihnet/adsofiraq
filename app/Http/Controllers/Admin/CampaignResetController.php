<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CampaignResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignResetController extends Controller
{
    public function __construct(
        protected CampaignResetService $resetService,
    ) {}

    public function index(): View
    {
        return view('admin.maintenance.reset-all-campaigns', [
            'counts' => $this->resetService->gatherCounts(),
            'confirmationPhrase' => CampaignResetService::CONFIRMATION_PHRASE,
        ]);
    }

    public function dryRun(Request $request): RedirectResponse
    {
        $state = $this->resetService->startSession((int) $request->user()->id, dryRun: true);

        return redirect()
            ->route('admin.maintenance.reset-all-campaigns.progress', $state['id'])
            ->with('success', 'Dry run complete. Review counts below — nothing was deleted.');
    }

    public function start(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'string'],
        ]);

        if (trim($request->input('confirmation')) !== CampaignResetService::CONFIRMATION_PHRASE) {
            return back()
                ->withInput()
                ->withErrors(['confirmation' => 'You must type exactly: '.CampaignResetService::CONFIRMATION_PHRASE]);
        }

        $state = $this->resetService->startSession((int) $request->user()->id, dryRun: false);

        return redirect()
            ->route('admin.maintenance.reset-all-campaigns.progress', $state['id'])
            ->with('warning', 'Destructive reset started. Keep this page open until complete.');
    }

    public function progress(string $session): View|RedirectResponse
    {
        $state = $this->resetService->getSession($session);

        if ($state === null) {
            return redirect()
                ->route('admin.maintenance.reset-all-campaigns')
                ->with('error', 'Reset session expired. Start again.');
        }

        return view('admin.maintenance.reset-all-campaigns-progress', [
            'sessionId' => $session,
            'progress' => $this->resetService->progressArray($state),
        ]);
    }

    public function status(string $session): JsonResponse
    {
        $state = $this->resetService->getSession($session);

        if ($state === null) {
            return response()->json(['ok' => false, 'error' => 'Session not found.'], 404);
        }

        return response()->json([
            'ok' => true,
            'progress' => $this->resetService->progressArray($state),
        ]);
    }

    public function tick(string $session): JsonResponse
    {
        $result = $this->resetService->tick($session);

        if (! $result['ok']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    public function pause(string $session): JsonResponse
    {
        $progress = $this->resetService->pause($session);

        if ($progress === null) {
            return response()->json(['ok' => false, 'error' => 'Session not found.'], 404);
        }

        return response()->json(['ok' => true, 'progress' => $progress]);
    }

    public function resume(string $session): JsonResponse
    {
        $progress = $this->resetService->resume($session);

        if ($progress === null) {
            return response()->json(['ok' => false, 'error' => 'Session not found.'], 404);
        }

        return response()->json(['ok' => true, 'progress' => $progress]);
    }
}
