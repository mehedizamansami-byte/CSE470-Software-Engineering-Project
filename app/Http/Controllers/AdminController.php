<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function users(): View
    {
        return view('admin.users', ['users' => User::orderBy('id')->get()]);
    }
}
