<?php

declare(strict_types=1);

namespace Spike\FlutterWeb;

use Spike\FlutterWeb\Http\Request;
use Spike\FlutterWeb\Http\Response;
use Throwable;

/**
 * The fake payment origin (port 27201, a different origin from the app). Stands in for a hosted
 * payment page: full-page redirect and return, popup with postMessage, and iframe with
 * postMessage — served without, or with, the headers a gateway would need under COEP. No money,
 * no card fields, nothing stored.
 */
final class PayApp
{
    public function __construct(private readonly Config $config) {}

    public function handle(Request $request): Response
    {
        $requestId = bin2hex(random_bytes(8));
        try {
            $mode = $request->q('mode');

            return match ([$request->method, $request->path]) {
                ['GET', '/pay'] => $this->page(Pages::pay($this->returnUrl($request->q('return')), 'redirect')),
                ['GET', '/pay/done'] => $this->done($request),
                ['GET', '/popup'] => $this->page(Pages::message($this->origin($request->q('origin')), 'popup')),
                ['GET', '/frame'] => $this->frame($request, $mode),
                ['GET', '/pay.js'] => $this->asset('pay.js', 'text/javascript; charset=utf-8'),
                ['GET', '/pages.css'] => $this->asset('pages.css', 'text/css; charset=utf-8'),
                default => throw new ApiError(404, 'not_found'),
            };
        } catch (ApiError $e) {
            return App::error($e, $requestId);
        } catch (Throwable $e) {
            error_log('[flutter-web-spike pay] '.$requestId.' '.$e::class.': '.$e->getMessage());

            return App::error(new ApiError(500, 'server_error'), $requestId);
        }
    }

    private function done(Request $request): Response
    {
        $return = $this->returnUrl($request->q('return'));
        in_array($request->q('status'), ['paid', 'cancelled'], true) || throw new ApiError(422, 'validation_failed', ['field' => 'status']);
        $query = http_build_query(['status' => $request->q('status'), 'mode' => 'redirect', 'ref' => bin2hex(random_bytes(4))]);

        return new Response(303, '', ['Location' => $return.(str_contains($return, '?') ? '&' : '?').$query, 'Cache-Control' => 'no-store']);
    }

    private function frame(Request $request, string $mode): Response
    {
        in_array($mode, ['frame', 'frame-corp'], true) || throw new ApiError(422, 'validation_failed', ['field' => 'mode']);
        $origin = $this->origin($request->q('origin'));
        $headers = [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store',
            'Content-Security-Policy' => "default-src 'self'; frame-ancestors ".implode(' ', $this->config->appOrigins),
        ];
        if ($mode === 'frame-corp') {
            // What a real gateway's framed page would have to send to be embeddable under COEP.
            $headers += ['Cross-Origin-Resource-Policy' => 'cross-origin', 'Cross-Origin-Embedder-Policy' => 'require-corp'];
        }

        return new Response(200, Pages::message($origin, $mode), $headers);
    }

    private function page(string $html): Response
    {
        return Response::html($html, [
            'Content-Security-Policy' => "default-src 'self'; frame-ancestors 'none'",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function asset(string $name, string $type): Response
    {
        // Cross-origin readable so the `frame-corp` page's own subresources load under its COEP.
        return new Response(200, (string) file_get_contents(dirname(__DIR__).'/public/'.$name), [
            'Content-Type' => $type, 'Cache-Control' => 'no-cache', 'Cross-Origin-Resource-Policy' => 'cross-origin',
        ]);
    }

    /** The return URL must be on one of the app origins (no open redirect). */
    private function returnUrl(string $url): string
    {
        $p = parse_url($url);
        if (! is_array($p) || ! isset($p['scheme'], $p['host'])) {
            throw new ApiError(400, 'bad_return_url');
        }
        $origin = $p['scheme'].'://'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '');
        $this->origin($origin);

        return $url;
    }

    private function origin(string $origin): string
    {
        in_array($origin, $this->config->appOrigins, true) || throw new ApiError(400, 'bad_return_url');

        return $origin;
    }
}
