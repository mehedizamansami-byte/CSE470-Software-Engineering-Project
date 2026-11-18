<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown when a seller does not have enough AI credits for an action (Feature 5).
 * Laravel calls render() automatically, so controllers do not need try/catch.
 */
class InsufficientCreditsException extends RuntimeException
{
    public function __construct(public readonly int $needed, public readonly int $balance)
    {
        parent::__construct("This action needs {$needed} AI credits, but only {$balance} are left.");
    }

    /** Not an application error, so do not write it to the log. */
    public function report(): void {}

    public function render(Request $request)
    {
        $message = $this->getMessage().' Upgrade your plan or buy a credit pack.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'credits' => $this->balance], 402);
        }

        return back()->withInput()->with('error', $message);
    }
}
