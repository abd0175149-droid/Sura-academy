<?php

declare(strict_types=1);

namespace Spike\Video;

final class CurlHttpClient implements HttpClient
{
    public function request(string $method, string $url, array $headers, ?string $body = null): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name.': '.$value;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $lines,
            // Recording downloads redirect to a CDN; curl drops Authorization on a cross-host redirect.
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 300,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $out = curl_exec($ch);
        if (! is_string($out)) {
            throw new ApiError(502, 'provider_error', ['transport' => curl_error($ch)]);
        }

        return [(int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE), $out];
    }
}
