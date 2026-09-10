<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmenityController extends Controller
{
    public function index()
    {
        $amenities = DB::table('amenities')->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $amenities]);
    }
}
