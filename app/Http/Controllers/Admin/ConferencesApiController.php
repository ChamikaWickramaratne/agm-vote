<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use Illuminate\Http\Request;

class ConferencesApiController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Conference::orderByDesc('start_date')->orderByDesc('id')->paginate(15),
        ]);
    }

    public function show(Conference $conference)
    {
        return response()->json(['data' => $conference]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['nullable','date'],
            'end_date'   => ['nullable','date','after_or_equal:start_date'],
        ]);

        $c = Conference::create($data);
        return response()->json(['message'=>'Created','data'=>$c], 201);
    }

    public function update(Request $request, Conference $conference)
    {
        $data = $request->validate([
            'start_date' => ['sometimes','nullable','date'],
            'end_date'   => ['sometimes','nullable','date','after_or_equal:start_date'],
        ]);

        $conference->fill($data)->save();
        return response()->json(['message'=>'Updated','data'=>$conference]);
    }

    public function destroy(Conference $conference)
    {
        $conference->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
