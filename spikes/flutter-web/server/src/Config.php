<?php

declare(strict_types=1);

namespace Spike\FlutterWeb;

/**
 * Spike settings. Origins come from tools/serve.sh (tunnel URLs or 127.0.0.1). The LiveKit Cloud
 * key is read at runtime from WP-0.4's key file (owner's answer 3b, 2026-10-13); Claude never opens
 * that file, and only its LIVEKIT_* lines are parsed.
 */
final readonly class Config
{
    /**
     * @param  array<string, string>  $builds  build name (wasm, js) => directory
     * @param  list<string>  $appOrigins  origins the fake payment page may return to or post to
     * @param  array{url: string, key: string, secret: string}  $liveKitLocal
     * @param  array{url: string, key: string, secret: string}|null  $liveKitCloud
     */
    public function __construct(
        public array $builds,
        public string $varDir,
        public string $runKey,
        public array $appOrigins,
        public string $payOrigin,
        public array $liveKitLocal,
        public ?array $liveKitCloud,
        public string $apkDir,
    ) {}

    public static function fromEnvironment(string $serverDir): self
    {
        $spike = dirname($serverDir);
        $var = $serverDir.'/var';
        $env = static fn (string $k, string $default): string => (string) (getenv($k) ?: $default);
        $appOrigins = array_values(array_unique(array_filter([
            rtrim($env('SPIKE_APP_ORIGIN', 'http://127.0.0.1:27200'), '/'),
            'http://127.0.0.1:27200',
            'http://localhost:27200',
        ])));

        return new self(
            builds: ['wasm' => $spike.'/app/build/web-wasm', 'js' => $spike.'/app/build/web-js'],
            varDir: $var,
            runKey: self::runKey($var),
            appOrigins: $appOrigins,
            payOrigin: rtrim($env('SPIKE_PAY_ORIGIN', 'http://127.0.0.1:27201'), '/'),
            // `livekit-server --dev` built-in development key: works only against a local server.
            liveKitLocal: ['url' => $env('SPIKE_LIVEKIT_LOCAL', 'ws://127.0.0.1:7880'), 'key' => 'devkey', 'secret' => 'secret'],
            liveKitCloud: self::liveKitFromKeyFile($env('LIVEKIT_KEY_FILE', dirname($spike).'/video/token-server/.env')),
            apkDir: $spike.'/app/build/app/outputs/flutter-apk',
        );
    }

    /** LIVEKIT_URL / LIVEKIT_API_KEY / LIVEKIT_API_SECRET from a KEY=VALUE file; other lines are ignored. */
    public static function liveKitFromKeyFile(string $file): ?array
    {
        if (! is_file($file)) {
            return null;
        }
        $found = [];
        foreach (preg_split('/\R/', (string) file_get_contents($file)) ?: [] as $line) {
            if (preg_match('/^\s*(LIVEKIT_URL|LIVEKIT_API_KEY|LIVEKIT_API_SECRET)\s*=\s*(.*?)\s*$/', $line, $m) === 1) {
                $found[$m[1]] = preg_replace('/^([\'"])(.*)\1$/', '$2', $m[2]);
            }
        }
        $url = $found['LIVEKIT_URL'] ?? '';
        $key = $found['LIVEKIT_API_KEY'] ?? '';
        $secret = $found['LIVEKIT_API_SECRET'] ?? '';

        return $url !== '' && $key !== '' && $secret !== '' ? ['url' => $url, 'key' => $key, 'secret' => $secret] : null;
    }

    /** A random run key kept in var/spike.json, so it survives restarts and can go into the APK build. */
    private static function runKey(string $var): string
    {
        $file = $var.'/spike.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (is_array($data) && is_string($data['run_key'] ?? null) && strlen($data['run_key']) === 32) {
            return $data['run_key'];
        }
        @mkdir($var, 0700, true);
        $key = bin2hex(random_bytes(16));
        file_put_contents($file, json_encode(['run_key' => $key]), LOCK_EX);
        chmod($file, 0600);

        return $key;
    }
}
