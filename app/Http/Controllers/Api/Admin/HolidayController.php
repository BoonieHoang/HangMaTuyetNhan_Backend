<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;

class HolidayController extends Controller
{
    // Read-only: no CRUD needed, admin manages via phpMyAdmin
    public function index()
    {
        return response()->json(Holiday::orderBy('name')->get());
    }
}
