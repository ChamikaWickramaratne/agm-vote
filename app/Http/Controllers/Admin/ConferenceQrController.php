<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConferenceQrController extends Controller
{
    public function download(Conference $conference, Request $request)
    {
        // Gate here if needed; your route already has auth middleware
        // abort_unless(auth()->check(), 403);

        $format   = strtolower($request->query('format', 'svg'));   // svg|png
        $size     = (int) $request->query('size', 500);             // pixels
        $margin   = (int) $request->query('margin', 1);             // modules margin
        $filename = $request->query('filename');                    // optional

        // Sensible guards
        $size   = max(64, min($size, 4096));
        $margin = max(0, min($margin, 10));

        $targetUrl = route('public.conference', $conference->public_token);

        // Default filename
        if (!$filename) {
            $filename = 'conference-'.$conference->id.'-qr.'.$format;
        } else {
            // ensure the provided filename has correct extension
            $ext = $format === 'png' ? 'png' : 'svg';
            if (!Str::endsWith(strtolower($filename), '.'.$ext)) {
                $filename .= '.'.$ext;
            }
        }

        // Build QR
        $qr = \QrCode::format($format)->size($size)->margin($margin);

        // If you ever want to brand/colour it, you can chain ->color(r,g,b) etc.

        $binary = $qr->generate($targetUrl);

        if ($format === 'png') {
            $contentType = 'image/png';
        } elseif ($format === 'svg') {
            $contentType = 'image/svg+xml';
        } else {
            abort(400, 'Unsupported format: '.$format);
        }

        // Send as file download
        return response($binary, 200, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
