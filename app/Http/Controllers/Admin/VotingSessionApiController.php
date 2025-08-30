<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use App\Models\VotingSession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VotingSessionsApiController extends Controller
{
    // GET /system/api/conferences/{conference}/sessions
    public function index(Conference $conference)
    {
        $list = $conference->sessions()
            ->with('position')
            ->orderByDesc('start_time')->orderByDesc('id')
            ->paginate(15);

        return response()->json(['data' => $list]);
    }

    // GET /system/api/conferences/{conference}/sessions/{session}
    public function show(Conference $conference, VotingSession $session)
    {
        $this->assertBelongs($conference, $session);
        return response()->json(['data' => $session->load('position')]);
    }

    // POST /system/api/conferences/{conference}/sessions
    public function store(Request $request, Conference $conference)
    {
        if ($conference->end_date) {
            return response()->json(['message' => 'Conference already ended'], 422);
        }

        $data = $request->validate([
            'position_id'     => ['required','integer','exists:positions,id'],
            'start_time'      => ['nullable','date'],
            'status'          => ['sometimes','required', Rule::in(['Pending','Open','Closed'])],
            'close_condition' => ['sometimes','required', Rule::in(['Manual','Timer','AllVotesCast'])],
            'voting_rules'    => ['nullable','array'], // client can POST JSON; we’ll cast to array
        ]);

        // defaults
        $data['status'] = $data['status'] ?? 'Pending';
        $data['close_condition'] = $data['close_condition'] ?? 'Manual';
        $data['conference_id'] = $conference->id;

        $session = VotingSession::create($data);

        return response()->json(['message' => 'Created', 'data' => $session->load('position')], 201);
    }

    // PATCH /system/api/conferences/{conference}/sessions/{session}
    public function update(Request $request, Conference $conference, VotingSession $session)
    {
        $this->assertBelongs($conference, $session);

        $data = $request->validate([
            'position_id'     => ['sometimes','integer','exists:positions,id'],
            'start_time'      => ['sometimes','nullable','date'],
            'end_time'        => ['sometimes','nullable','date','after_or_equal:start_time'],
            'status'          => ['sometimes', Rule::in(['Pending','Open','Closed'])],
            'close_condition' => ['sometimes', Rule::in(['Manual','Timer','AllVotesCast'])],
            'voting_rules'    => ['sometimes','nullable','array'],
        ]);

        $session->fill($data)->save();

        return response()->json(['message' => 'Updated', 'data' => $session->load('position')]);
    }

    // DELETE /system/api/conferences/{conference}/sessions/{session}
    public function destroy(Conference $conference, VotingSession $session)
    {
        $this->assertBelongs($conference, $session);
        $session->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // PATCH /system/api/conferences/{conference}/sessions/{session}/end
    public function end(Conference $conference, VotingSession $session)
    {
        $this->assertBelongs($conference, $session);

        if ($session->end_time) {
            return response()->json(['message' => 'Session already ended'], 422);
        }

        $session->update(['end_time' => now(), 'status' => 'Closed']);
        return response()->json(['message' => 'Ended', 'data' => $session->load('position')]);
    }

    private function assertBelongs(Conference $conference, VotingSession $session): void
    {
        abort_unless($session->conference_id === $conference->id, 404);
    }
}
