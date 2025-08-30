<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserApiController extends Controller
{
    // GET /system/api/users
    public function index(Request $request)
    {
        $q = User::query()
            ->when($request->filled('role'), fn($qq) => $qq->where('role', $request->role))
            ->orderByDesc('id');

        return response()->json([
            'data' => $q->select('id','name','email','role','created_at')->paginate(15),
        ]);
    }

    // POST /system/api/users
    // Create ONLY a VotingManager (admin/superadmin shouldn’t create other admins via this endpoint)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')],
            'password' => ['required','string','min:8'],
            // ignore any role incoming; we will force VotingManager below
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'VotingManager',
        ]);

        return response()->json(['message' => 'Voting Manager created', 'user' => [
            'id' => $user->id, 'name'=>$user->name, 'email'=>$user->email, 'role'=>$user->role
        ]], 201);
    }

    // PATCH /system/api/users/{user}
    // Optional: allow changing only name/password for VotingManagers
    public function update(Request $request, User $user)
    {
        // Authorization: only edit VotingManagers via this endpoint
        if ($user->role !== 'VotingManager') {
            return response()->json(['message' => 'Only VotingManagers can be edited here'], 403);
        }

        $data = $request->validate([
            'name'     => ['sometimes','string','max:255'],
            'password' => ['sometimes','string','min:8'],
        ]);

        if (isset($data['name']))     $user->name = $data['name'];
        if (isset($data['password'])) $user->password = Hash::make($data['password']);
        $user->save();

        return response()->json(['message'=>'Updated','user'=>[
            'id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'role'=>$user->role
        ]]);
    }

    // DELETE /system/api/users/{user}
    public function destroy(User $user)
    {
        if ($user->role !== 'VotingManager') {
            return response()->json(['message' => 'Only VotingManagers can be deleted here'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
