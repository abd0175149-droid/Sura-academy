<?php

declare(strict_types=1);

namespace Spike\FlutterWeb;

use Closure;
use Spike\FlutterWeb\Http\Request;
use Spike\FlutterWeb\Http\Response;
use Throwable;

/** The app origin (port 27200). Pure request → response so the self-tests need no server. */
final class App
{
    public const DEVICES = [
        'pc-chrome', 'ipad-safari', 'chromebook-chrome', 'android-chrome', 'iphone-safari',
        'android-app', 'headless-chrome', 'other',
    ];

    private const NETWORKS = ['wifi', '4g', '5g', 'ethernet', 'unthrottled', 'throttled-slow-4g', 'throttled-fast-4g', 'unknown'];

    private const METRIC_FIELDS = [
        'variant', 'phase', 'run', 'device', 'network', 'semantics', 'marks', 'wasm', 'threads',
        'isolated', 'sw', 'ua', 'vendor', 'transfer_kb', 'encoded_kb', 'engine_files', 'screen',
    ];

    private const VARIANT = '#^((wasmx|wasm|js)/(iso|plain)|native)$#';

    public const ROOM = 'wp05-isolation';

    private const PROTECTED = ['/metrics', '/shots', '/feedback', '/livekit-token'];

    /** @param Closure(): int $clock */
    public function __construct(private readonly Config $config, private readonly Closure $clock) {}

    public function handle(Request $request): Response
    {
        $requestId = bin2hex(random_bytes(8));
        try {
            if ($request->method === 'POST' && in_array($request->path, self::PROTECTED, true)
                && ! hash_equals($this->config->runKey, $request->header('x-spike-key'))) {
                throw new ApiError(403, 'bad_run_key');
            }
            if ($request->method === 'GET' && preg_match(BuildFiles::ROUTE, $request->path, $m) === 1) {
                return (new BuildFiles($this->config, $this->clock))->serve($request, $m);
            }

            return match ([$request->method, $request->path]) {
                ['GET', '/health'] => Response::json(200, ['ok' => true]),
                ['GET', '/'], ['GET', '/start'] => $this->page(Pages::start($request)),
                ['GET', '/auto'] => $this->page(Pages::auto($request)),
                ['GET', '/auto.js'] => $this->asset('auto.js', 'text/javascript; charset=utf-8'),
                ['GET', '/pages.css'] => $this->asset('pages.css', 'text/css; charset=utf-8'),
                ['GET', '/downloads/flutter-web-spike.apk'] => $this->apk(),
                ['POST', '/metrics'] => $this->metrics($request),
                ['POST', '/shots'] => $this->shot($request),
                ['POST', '/feedback'] => $this->feedback($request),
                ['POST', '/csp-report'] => $this->cspReport($request),
                ['POST', '/livekit-token'] => $this->liveKitToken($request),
                default => throw new ApiError(404, 'not_found'),
            };
        } catch (ApiError $e) {
            return self::error($e, $requestId);
        } catch (Throwable $e) {
            error_log('[flutter-web-spike] '.$requestId.' '.$e::class.': '.$e->getMessage());

            return self::error(new ApiError(500, 'server_error'), $requestId);
        }
    }

    public static function error(ApiError $e, string $requestId): Response
    {
        return Response::json($e->status, [
            'code' => $e->errorCode,
            'message_ar' => Lang::get('ar', 'error.'.$e->errorCode),
            'message_en' => Lang::get('en', 'error.'.$e->errorCode),
            'details' => (object) $e->details,
            'request_id' => $requestId,
        ])->with(Headers::base());
    }

    private function page(string $html): Response
    {
        return Response::html($html, Headers::base() + Headers::csp($this->config));
    }

    private function asset(string $name, string $type): Response
    {
        return new Response(200, (string) file_get_contents(dirname(__DIR__).'/public/'.$name), ['Content-Type' => $type, 'Cache-Control' => 'no-cache'] + Headers::base() + Headers::csp($this->config));
    }

    private function apk(): Response
    {
        $file = $this->config->apkDir.'/app-release.apk';
        if (! is_file($file)) {
            throw new ApiError(404, 'not_found');
        }

        return new Response(200, (string) file_get_contents($file), [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="flutter-web-spike.apk"',
        ] + Headers::base());
    }

    private static function limit(Request $request, int $bytes): void
    {
        if (strlen($request->body) > $bytes) {
            throw new ApiError(413, 'too_large', ['max_bytes' => $bytes]);
        }
    }

    private static function invalid(string $field): never
    {
        throw new ApiError(422, 'validation_failed', ['field' => $field]);
    }

    private function metrics(Request $request): Response
    {
        self::limit($request, 16 * 1024);
        $data = $request->json();
        foreach (array_keys($data) as $key) {
            in_array($key, self::METRIC_FIELDS, true) || self::invalid((string) $key);
        }
        in_array($data['device'] ?? null, self::DEVICES, true) || self::invalid('device');
        in_array($data['network'] ?? null, self::NETWORKS, true) || self::invalid('network');
        in_array($data['phase'] ?? null, ['cold', 'warm', 'manual'], true) || self::invalid('phase');
        preg_match(self::VARIANT, (string) ($data['variant'] ?? '')) === 1 || self::invalid('variant');
        preg_match('/^(r-[0-9a-f]{4,32})?$/', (string) ($data['run'] ?? '')) === 1 || self::invalid('run');
        is_array($data['marks'] ?? null) || self::invalid('marks');
        foreach ($data['marks'] as $name => $value) {
            (is_string($name) && preg_match('/^[a-z-]{2,20}$/', $name) === 1 && is_numeric($value)) || self::invalid('marks');
        }
        Store::append($this->config->varDir.'/metrics.jsonl', $data + ['received_at' => ($this->clock)()]);

        return new Response(204);
    }

    private function shot(Request $request): Response
    {
        self::limit($request, 8 * 1024 * 1024);
        if (! str_starts_with($request->body, "\x89PNG\r\n\x1a\n")) {
            throw new ApiError(415, 'not_png');
        }
        $device = $request->q('device');
        $variant = $request->q('variant') === '' ? 'native' : $request->q('variant');
        in_array($device, self::DEVICES, true) || self::invalid('device');
        preg_match(self::VARIANT, $variant) === 1 || self::invalid('variant');
        in_array($request->q('diacritics'), ['on', 'off'], true) || self::invalid('diacritics');
        in_array($request->q('scale'), ['1.0', '1.3', '2.0'], true) || self::invalid('scale');
        $name = sprintf('%s-%s-%s-%s-%s-%s.png', gmdate('Ymd\THis\Z', ($this->clock)()), $device, str_replace('/', '-', $variant), $request->q('diacritics'), $request->q('scale'), bin2hex(random_bytes(3)));
        Store::put($this->config->varDir.'/shots', $name, $request->body);

        return new Response(204);
    }

    private function feedback(Request $request): Response
    {
        self::limit($request, 4 * 1024);
        $data = $request->json();
        array_diff(array_keys($data), ['device', 'variant', 'answers']) === [] || self::invalid('body');
        in_array($data['device'] ?? null, self::DEVICES, true) || self::invalid('device');
        preg_match(self::VARIANT, (string) ($data['variant'] ?? 'native') ?: 'native') === 1 || self::invalid('variant');
        $answers = $data['answers'] ?? null;
        (is_array($answers) && $answers !== []) || self::invalid('answers');
        foreach ($answers as $q => $a) {
            (preg_match('/^q[1-9]$/', (string) $q) === 1 && in_array($a, ['yes', 'no'], true)) || self::invalid('answers');
        }
        Store::append($this->config->varDir.'/feedback.jsonl', $data + ['received_at' => ($this->clock)()]);

        return new Response(204);
    }

    /** Browser CSP reports (enforced and report-only policies). Only the fields we read are kept. */
    private function cspReport(Request $request): Response
    {
        self::limit($request, 16 * 1024);
        $report = json_decode($request->body, true)['csp-report'] ?? null;
        if (is_array($report)) {
            Store::append($this->config->varDir.'/csp.jsonl', [
                'at' => ($this->clock)(),
                'document' => (string) parse_url((string) ($report['document-uri'] ?? ''), PHP_URL_PATH),
                'directive' => (string) ($report['effective-directive'] ?? $report['violated-directive'] ?? ''),
                'blocked' => (string) ($report['blocked-uri'] ?? ''),
                'disposition' => (string) ($report['disposition'] ?? ''),
                'sample' => mb_substr((string) ($report['script-sample'] ?? ''), 0, 80),
            ]);
        }

        return new Response(204);
    }

    /** Join token for the isolation probe: one test room, camera and microphone only, 10 minutes. */
    private function liveKitToken(Request $request): Response
    {
        $data = $request->json();
        $target = $data['target'] ?? null;
        in_array($target, ['local', 'cloud'], true) || self::invalid('target');
        ($data['room'] ?? null) === self::ROOM || self::invalid('room');
        $identity = (string) ($data['identity'] ?? '');
        preg_match('/^[a-z0-9-]{3,40}$/', $identity) === 1 || self::invalid('identity');
        $server = $target === 'cloud' ? $this->config->liveKitCloud : $this->config->liveKitLocal;
        if ($server === null) {
            throw new ApiError(503, 'cloud_key_missing');
        }
        $now = ($this->clock)();
        $token = Jwt::encode([
            'iss' => $server['key'],
            'sub' => $identity,
            'name' => $identity,
            'nbf' => $now,
            'exp' => $now + 600,
            'video' => [
                'room' => self::ROOM,
                'roomJoin' => true,
                'canSubscribe' => true,
                'canPublish' => true,
                'canPublishData' => false,
                'canPublishSources' => ['camera', 'microphone'],
            ],
        ], $server['secret']);

        return Response::json(200, ['token' => $token, 'url' => $server['url']]);
    }
}
