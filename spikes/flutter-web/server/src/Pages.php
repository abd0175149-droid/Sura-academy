<?php

declare(strict_types=1);

namespace Spike\FlutterWeb;

use Spike\FlutterWeb\Http\Request;

/** Small Arabic HTML pages: start links, auto-run, and the fake payment pages. No inline script or style. */
final class Pages
{
    /** Variants walked by the auto-run, in order. `wasmx` is a manual experiment, not in the plan. */
    public const AUTO_VARIANTS = ['wasm/iso', 'wasm/plain', 'js/iso', 'js/plain'];

    public const AUTO_COLD = 5;

    public const AUTO_WARM = 5;

    public static function start(Request $request): string
    {
        $h = self::h(...);
        $q = htmlspecialchars(self::carry($request), ENT_QUOTES);
        $links = '';
        foreach (['wasm/iso', 'wasm/plain', 'js/iso', 'js/plain', 'wasmx/iso'] as $variant) {
            $links .= "<li><a href=\"/v/{$variant}/?{$q}\">{$h('variant.'.$variant)}</a> <span class=\"ltr\">{$variant}</span></li>";
        }
        $runs = count(self::AUTO_VARIANTS) * (self::AUTO_COLD + self::AUTO_WARM);

        return self::layout($h('start.title'), <<<HTML
            <main>
              <h1>{$h('start.title')}</h1>
              <p>{$h('start.intro')}</p>
              <p><a class="button" href="/auto?start=1&amp;{$q}">{$h('start.auto')}</a></p>
              <p class="muted">{$h('start.auto_hint', ['n' => $runs])}</p>
              <h2>{$h('start.variants')}</h2>
              <ul>{$links}</ul>
            </main>
            HTML);
    }

    public static function auto(Request $request): string
    {
        $h = self::h(...);
        $plan = htmlspecialchars(json_encode([
            'variants' => self::AUTO_VARIANTS,
            'cold' => self::AUTO_COLD,
            'warm' => self::AUTO_WARM,
        ], JSON_THROW_ON_ERROR), ENT_QUOTES);
        $texts = htmlspecialchars(json_encode([
            'step' => Lang::get('ar', 'auto.step'),
            'done' => Lang::get('ar', 'auto.done'),
            'cleaning' => Lang::get('ar', 'auto.cleaning'),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        $q = htmlspecialchars(self::carry($request), ENT_QUOTES);

        return self::layout($h('auto.title'), <<<HTML
            <main id="auto" data-plan="{$plan}" data-texts="{$texts}">
              <h1>{$h('auto.title')}</h1>
              <p>{$h('auto.keep_screen_on')}</p>
              <p id="auto-state" role="status" aria-live="polite">…</p>
              <p><a href="/start?{$q}" id="auto-back" hidden>{$h('auto.back')}</a></p>
            </main>
            <script src="/auto.js"></script>
            HTML);
    }

    /** The fake hosted payment page: two links that send the shopper back with a status. */
    public static function pay(string $returnUrl, string $mode): string
    {
        $h = self::h(...);
        $link = static fn (string $status): string => htmlspecialchars('/pay/done?'.http_build_query(['status' => $status, 'mode' => $mode, 'return' => $returnUrl]), ENT_QUOTES);

        return self::layout($h('pay.title'), <<<HTML
            <main>
              <h1>{$h('pay.title')}</h1>
              <p>{$h('pay.fake')}</p>
              <p class="amount"><span class="ltr">USD 49.00</span></p>
              <p><a class="button" href="{$link('paid')}">{$h('pay.pay')}</a> <a href="{$link('cancelled')}">{$h('pay.cancel')}</a></p>
            </main>
            HTML);
    }

    /** Popup and iframe modes post the result to the app with postMessage instead of redirecting. */
    public static function message(string $origin, string $mode): string
    {
        $h = self::h(...);
        $o = htmlspecialchars($origin, ENT_QUOTES);
        $m = htmlspecialchars($mode, ENT_QUOTES);

        return self::layout($h('pay.title'), <<<HTML
            <main id="pay" data-origin="{$o}" data-mode="{$m}">
              <h1>{$h('pay.title')}</h1>
              <p>{$h('pay.fake')}</p>
              <p class="amount"><span class="ltr">USD 49.00</span></p>
              <p><button type="button" data-status="paid" class="button">{$h('pay.pay')}</button>
                 <button type="button" data-status="cancelled">{$h('pay.cancel')}</button></p>
              <p id="pay-state" role="status" aria-live="polite" data-no-opener="{$h('pay.no_opener')}" data-sent="{$h('pay.sent')}"></p>
            </main>
            <script src="/pay.js"></script>
            HTML);
    }

    private static function h(string $key, array $replace = []): string
    {
        return Lang::h('ar', $key, $replace);
    }

    /** k, d and n travel from page to page (run key, device label, network label). */
    private static function carry(Request $request): string
    {
        return http_build_query(array_intersect_key($request->query, array_flip(['k', 'd', 'n'])));
    }

    private static function layout(string $title, string $body): string
    {
        return <<<HTML
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <title>{$title}</title>
              <link rel="stylesheet" href="/pages.css">
            </head>
            <body>
            {$body}
            </body>
            </html>
            HTML;
    }
}
