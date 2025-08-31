<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VotingSession;
use App\Models\VoterId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VoterIdsApiController extends Controller
{
    public function assign(Request $req, VotingSession $session)
    {
        $data = $req->validate(['member_id' => ['required','integer','exists:members,id']]);

        $exists = VoterId::where('voting_session_id',$session->id)
            ->where('member_id',$data['member_id'])->first();

        if ($exists) return response()->json(['message'=>'Already assigned'], 200);

        $code = $this->makeCode(6);
        $row = VoterId::create([
            'voting_session_id' => $session->id,
            'member_id'         => $data['member_id'],
            'voter_code_hash'   => Hash::make($code),
            'issued_by'         => auth()->id(),
            'issued_at'         => now(),
        ]);

        return response()->json(['message'=>'Created','code'=>$code,'data'=>$row],201);
    }

    public function unassign(Request $req, VotingSession $session)
    {
        $data = $req->validate(['member_id' => ['required','integer','exists:members,id']]);

        VoterId::where('voting_session_id',$session->id)
            ->where('member_id',$data['member_id'])
            ->delete();

        return response()->json(['message'=>'Deleted']);
    }

    protected function generateCode(int $len = 6): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // skip ambiguous 0/O/1/I
        $out = '';
        for ($i=0; $i<$len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet)-1)];
        }
        return $out;
    }
}
