<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purpose;

class PurposeController extends Controller
{
    // Read-only: no CRUD needed, admin manages via phpMyAdmin
    public function index()
    {
        return response()->json(Purpose::orderBy('name')->get());
    }
}
