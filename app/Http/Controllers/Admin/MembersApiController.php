<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MembersApiController extends Controller
{
    public function index(Request $request)
    {
        $q = Member::query()
            ->when($request->filled('search'), function($qq) use ($request) {
                $s = '%'.$request->search.'%';
                $qq->where(function($w) use ($s) {
                    $w->where('name', 'like', $s)
                      ->orWhere('email', 'like', $s);
                });
            })
            ->orderByDesc('id');

        return response()->json([
            'data' => $q->paginate(15),
        ]);
    }

    public function show(Member $member)
    {
        return response()->json(['data' => $member]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required','string','max:255'],
            'email' => ['nullable','email','max:255', Rule::unique('members','email')],
        ]);

        $member = Member::create($data);

        return response()->json(['message' => 'Created', 'data' => $member], 201);
    }

    public function update(Request $request, Member $member)
    {
        $data = $request->validate([
            'name'  => ['sometimes','required','string','max:255'],
            'email' => ['sometimes','nullable','email','max:255', Rule::unique('members','email')->ignore($member->id)],
        ]);

        $member->fill($data)->save();

        return response()->json(['message' => 'Updated', 'data' => $member]);
    }

    public function destroy(Member $member)
    {
        $member->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
