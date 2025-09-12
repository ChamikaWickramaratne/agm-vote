<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\View\View;

class MemberController extends Controller
{
    /**
     * Show all members.
     */
    public function index(): View
    {
        // Fetch members from DB
        $members = Member::all();

        // Pass $members into the blade
        return view('livewire.admin.members-page', compact('members'));
    }

    /**
     * Show the form for creating a new member.
     */
    public function create(): View
    {
        return view('livewire.admin.members-create');
    }
}
