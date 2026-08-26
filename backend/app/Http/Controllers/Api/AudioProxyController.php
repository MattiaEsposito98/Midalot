<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AudioProxyController extends Controller
{
    /**
     * Fa da proxy per gli estratti audio di iTunes: Apple restituisce
     * questi file con Content-Type "audio/x-m4p" (storicamente usato per
     * audio protetto da DRM), che alcuni browser rifiutano di riprodurre
     * anche se il contenuto è un normale M4A/AAC non protetto. Qui
     * scarichiamo il file e lo re-inviamo con un Content-Type corretto.
     */
    public function stream(Request $request)
    {
        $url = (string) $request->query('url', '');

        if (! $this->isAllowedUrl($url)) {
            abort(403, 'URL non consentita');
        }

        $headers = [];
        if ($range = $request->header('Range')) {
            $headers['Range'] = $range;
        }

        $response = Http::withHeaders($headers)->timeout(15)->get($url);

        if (! $response->successful()) {
            abort(502, 'Impossibile recuperare il file audio');
        }

        $responseHeaders = [
            'Content-Type' => 'audio/mp4',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=86400',
        ];

        foreach (['Content-Length', 'Content-Range'] as $header) {
            if ($response->hasHeader($header)) {
                $responseHeaders[$header] = $response->header($header);
            }
        }

        return response($response->body(), $response->status(), $responseHeaders);
    }

    private function isAllowedUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        return $host === 'itunes.apple.com'
            || str_ends_with($host, '.itunes.apple.com')
            || str_ends_with($host, '.mzstatic.com');
    }
}
