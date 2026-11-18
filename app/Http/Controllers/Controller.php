<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * The seller whose workspace the current user works in.
     * For a seller that is the user; for a collaborator it is their seller.
     */
    protected function owner(Request $request): User
    {
        $owner = $request->user()->workspaceOwner();

        abort_unless($owner, 403, 'You are not part of a seller workspace.');

        return $owner;
    }

    /** A workspace record (brand kit, post, ...) must belong to the current workspace. */
    protected function ensureWorkspace(Request $request, Model $model): void
    {
        abort_unless((int) $model->seller_id === $this->owner($request)->id, 404);
    }

    /** In demo mode, OTP codes are shown on screen because email only goes to the log file. */
    protected function demo(?string $code): ?string
    {
        return config('brandbondhu.show_demo_codes') ? $code : null;
    }
}
