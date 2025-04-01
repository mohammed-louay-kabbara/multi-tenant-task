<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Notecontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
        $user = auth()->user();
        if ($user && $user->tenant) {
            $tenantDbName = $user->tenant->database;
            config(['database.connections.tenant.database' => $tenantDbName]);
            DB::purge('tenant');
            DB::reconnect('tenant');
            $notes = DB::connection('tenant')->table('notes')->get();
            return response()->json($notes);
        }
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'حدث خطأ أثناء جلب البيانات',
            'error' => $e->getMessage()
        ], 500);
    }

    }
    

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
