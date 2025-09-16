<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index()
    {
        $user = Auth::user(); if (! $user) abort(401);
        $services = $user->client ? $user->client->services()->latest()->paginate(15) : collect();
        return view('services.index', compact('services'));
    }
}

