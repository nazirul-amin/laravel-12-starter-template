<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BaseResourceController extends Controller
{
    protected function attemptTransaction(callable $transaction, ?callable $afterTransaction, string $successRoute, string $successMessage, string $failureMessage, string $errorLogMessage, array $logContext = []): RedirectResponse
    {
        try {
            $result = DB::transaction(function () use ($transaction) {
                return $transaction();
            });

            if ($afterTransaction) {
                $afterTransaction($result);
            }

            return redirect()->route($successRoute)->with('success', $successMessage);
        } catch (\Throwable $th) {
            Log::error($errorLogMessage, array_merge($logContext, [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]));

            return redirect()->back()->with('error', $failureMessage);
        }
    }
}
