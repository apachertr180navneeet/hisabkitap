<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PsoRetention;

class RetentionController extends Controller
{
    public function index()
    {
        $retentions = PsoRetention::orderBy('id', 'asc')->get();
        return view('retention.index', compact('retentions'));
    }
}
