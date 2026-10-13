<?php

declare(strict_types=1);

namespace Spike\Video;

/**
 * Spike settings. Provider keys come from the owner's local key file next to `.env.example`
 * (git-ignored, never read by Claude). The access code and ticket secret are generated at first
 * start into var/spike.json, so testers can be given the code without touching the key file.
 */
final readonly class Config
{
    public function __construct(
        public string $liveKitUrl = '',
        public string $liveKitApiKey = '',
        public string $liveKitApiSecret = '',
        public string $zoomSdkKey = '',
        public string $zoomSdkSecret = '',
        public string $zoomWebhookSecret = '',
        public string $zoomApiKey = '',
        public string $zoomApiSecret = '',
        /** @var array{endpoint: string, bucket: string, access_key: string, secret: string, region: string}|null */
        public ?array $egressS3 = null,
        public string $accessCode = '',
        /** Required for teacher/substitute tokens, which carry the admin ticket (security review 2026-10-13). */
        public string $hostCode = '',
        public string $ticketSecret = '',
        public ?int $windowOpensAt = null,
        public ?int $windowClosesAt = null,
    ) {}

    public static function load(string $keyFile, string $varDir): self
    {
        $env = is_file($keyFile) ? self::parseKeyFile((string) file_get_contents($keyFile)) : [];
        $local = self::localSecrets($varDir);
        $time = static fn (string $key): ?int => ($env[$key] ?? '') === '' ? null : (int) strtotime($env[$key]);

        return new self(
            liveKitUrl: $env['LIVEKIT_URL'] ?? '',
            liveKitApiKey: $env['LIVEKIT_API_KEY'] ?? '',
            liveKitApiSecret: $env['LIVEKIT_API_SECRET'] ?? '',
            zoomSdkKey: $env['ZOOM_SDK_KEY'] ?? '',
            zoomSdkSecret: $env['ZOOM_SDK_SECRET'] ?? '',
            zoomWebhookSecret: $env['ZOOM_WEBHOOK_SECRET'] ?? '',
            zoomApiKey: $env['ZOOM_API_KEY'] ?? '',
            zoomApiSecret: $env['ZOOM_API_SECRET'] ?? '',
            egressS3: self::egressTarget($varDir),
            accessCode: $local['access_code'],
            hostCode: $local['host_code'],
            ticketSecret: $local['ticket_secret'],
            windowOpensAt: $time('SPIKE_WINDOW_OPENS_AT'),
            windowClosesAt: $time('SPIKE_WINDOW_CLOSES_AT'),
        );
    }

    /** KEY=VALUE lines; `#` comments; optional matching quotes around the value. */
    public static function parseKeyFile(string $text): array
    {
        $out = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            if (preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*?)\s*$/', $line, $m) !== 1) {
                continue;
            }
            $value = $m[2];
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
                $value = substr($value, 1, -1);
            }
            $out[$m[1]] = $value;
        }

        return $out;
    }

    /** Written by tools/minio-egress.sh: a write-only MinIO user and the tunnel URL in front of MinIO. */
    private static function egressTarget(string $varDir): ?array
    {
        $file = $varDir.'/egress.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($data) && isset($data['endpoint'], $data['bucket'], $data['access_key'], $data['secret'], $data['region']) ? $data : null;
    }

    /**
     * Two typeable 10-character codes (~49 bits each, no look-alike characters) and a 256-bit
     * ticket secret. tools/serve.sh deletes the file at every start, so codes rotate per session.
     *
     * @return array{access_code: string, host_code: string, ticket_secret: string}
     */
    private static function localSecrets(string $varDir): array
    {
        $file = $varDir.'/spike.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        $valid = is_array($data)
            && is_string($data['access_code'] ?? null) && strlen($data['access_code']) >= 10
            && is_string($data['host_code'] ?? null) && strlen($data['host_code']) >= 10
            && is_string($data['ticket_secret'] ?? null) && preg_match('/^[0-9a-f]{64}$/', $data['ticket_secret']) === 1;
        if (! $valid) {
            @mkdir($varDir, 0700, true);
            $data = ['access_code' => self::code(), 'host_code' => self::code(), 'ticket_secret' => bin2hex(random_bytes(32))];
            $old = umask(0077);
            $tmp = tempnam($varDir, 'spike');
            file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
            rename($tmp, $file);
            umask($old);
        }

        return $data;
    }

    private static function code(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < 10; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }
}
