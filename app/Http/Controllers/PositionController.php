<?php 

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    // Show all positions (with search)
    public function index(Request $request)
    {
        $search = $request->input('search');

        $positions = Position::query()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->get();

        return view('positions.index', compact('positions', 'search'));
    }

    // Show form
    public function create()
    {
        return view('positions.create');
    }

    // Store new position
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Position::create([
            'name' => $request->name,
        ]);

        return redirect()->route('positions.index')->with('success', 'Position created!');
    }
}
