<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ConferenceQrController extends Controller
{
    public function download(Request $request, Conference $conference)
    {
        // Optional: simple role gate like your <x-role> component
        $role = optional(Auth::user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) {
            abort(403);
        }

        $format = strtolower($request->query('format', 'png')); // png|svg
        $size   = (int) $request->query('size', 1000);          // large, crisp image
        $margin = (int) $request->query('margin', 1);

        $url = route('public.conference', $conference->public_token);

        if ($format === 'svg') {
            $svg = QrCode::format('svg')->size($size)->margin($margin)->generate($url);

            return response($svg, 200, [
                'Content-Type'        => 'image/svg+xml',
                'Content-Disposition' => 'attachment; filename="conference-'.$conference->id.'-qr.svg"',
            ]);
        }

        // default to PNG
        $png = QrCode::format('png')->size($size)->margin($margin)->generate($url);

        return response($png, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'attachment; filename="conference-'.$conference->id.'-qr.png"',
        ]);
    }
}
