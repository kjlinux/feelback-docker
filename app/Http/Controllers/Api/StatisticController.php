<?php

namespace App\Http\Controllers\Api;

use App\Models\Statistic;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StatisticController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(Statistic $statistic) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Statistic $statistic) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Statistic $statistic) {}
}
