<?php

declare(strict_types=1);

namespace Spike\Video;

/**
 * Turns the spike's raw logs into the ADR-005 numbers: join time per device (NFR: < 10 s at p95)
 * and the monthly cost of the WP-0.4 load per provider. Prices: see PRICES (checked 2026-10-12).
 */
final class Results
{
    /** Published prices used by the cost model; `source` is where each was read on `checked`. */
    public const PRICES = [
        'checked' => '2026-10-12',
        'zoom' => [
            'participant_minute' => 0.0035,
            'recording_minute' => 0.01,
            'storage_gb_month' => 0.25,
            'source' => 'https://zoom.us/pricing/developer (View credit rates)',
        ],
        'livekit' => [
            'ship' => ['base' => 50.0, 'included_minutes' => 150_000, 'minute' => 0.0005, 'included_transcode' => 600, 'transcode_minute' => 0.02, 'included_gb' => 250, 'gb' => 0.12, 'concurrent_egress' => 100],
            'scale' => ['base' => 500.0, 'included_minutes' => 1_500_000, 'minute' => 0.0004, 'included_transcode' => 8_000, 'transcode_minute' => 0.015, 'included_gb' => 3_000, 'gb' => 0.10, 'concurrent_egress' => 500],
            'source' => 'https://livekit.com/pricing',
        ],
    ];

    /** Nearest-rank percentile in seconds; a failed trial (null) ranks after every success. */
    public static function percentile(array $millis, float $p): ?float
    {
        if ($millis === []) {
            return null;
        }
        usort($millis, static fn (?int $a, ?int $b): int => ($a === null) <=> ($b === null) ?: (int) $a <=> (int) $b);
        $value = $millis[max(1, (int) ceil($p / 100 * count($millis))) - 1];

        return $value === null ? null : round($value / 1000, 2);
    }

    /** Automatic trials grouped by provider × platform × device × network. */
    public static function joinSummary(array $rows): array
    {
        $groups = [];
        foreach ($rows as $r) {
            if (($r['mode'] ?? null) !== 'auto') {
                continue;
            }
            $key = implode('|', [$r['provider'], $r['platform'], $r['device'], $r['network']]);
            $groups[$key][] = ($r['ok'] ?? false) ? (int) $r['t_first_video_ms'] : null;
        }
        ksort($groups);
        $out = [];
        foreach ($groups as $key => $times) {
            [$provider, $platform, $device, $network] = explode('|', $key);
            $p95 = self::percentile($times, 95);
            $out[] = [
                'provider' => $provider,
                'platform' => $platform,
                'device' => $device,
                'network' => $network,
                'n' => count($times),
                'ok' => count(array_filter($times, static fn (?int $t): bool => $t !== null)),
                'p50_s' => self::percentile($times, 50),
                'p95_s' => $p95,
                'max_s' => ($ok = array_filter($times, static fn (?int $t): bool => $t !== null)) === [] ? null : round(max($ok) / 1000, 2),
                'meets_nfr' => $p95 !== null && $p95 < 10.0,
            ];
        }

        return $out;
    }

    /** The WP-0.4 load, multiplied literally: classes × participants × minutes × sessions per month. */
    public static function load(int $classes, int $participants, int $minutes, int $sessionsPerMonth): array
    {
        $room = $classes * $minutes * $sessionsPerMonth;

        return ['room_minutes' => $room, 'participant_minutes' => $room * $participants];
    }

    public static function zoomCost(array $load, float $recordedShare, float $recordingGbStoredPerMonth = 0.0): array
    {
        $z = self::PRICES['zoom'];
        $out = [
            'sessions_usd' => round($load['participant_minutes'] * $z['participant_minute'], 2),
            'recording_usd' => round($load['room_minutes'] * $recordedShare * $z['recording_minute'], 2),
            'storage_usd' => round($recordingGbStoredPerMonth * $z['storage_gb_month'], 2),
        ];

        return $out + ['total_usd' => round(array_sum($out), 2)];
    }

    /** $downMbpsPerParticipant is measured from the spike's receiver stats (bytes per minute). */
    public static function liveKitCost(array $load, float $recordedShare, float $downMbpsPerParticipant, string $plan): array
    {
        $p = self::PRICES['livekit'][$plan];
        $gb = $load['participant_minutes'] * 60 * $downMbpsPerParticipant / 8 / 1000;
        $recorded = $load['room_minutes'] * $recordedShare;
        $out = [
            'base_and_minutes_usd' => round($p['base'] + max(0, $load['participant_minutes'] - $p['included_minutes']) * $p['minute'], 2),
            'recording_usd' => round(max(0, $recorded - $p['included_transcode']) * $p['transcode_minute'], 2),
            'bandwidth_usd' => round(max(0, $gb - $p['included_gb']) * $p['gb'], 2),
        ];

        return $out + ['total_usd' => round(array_sum($out), 2), 'downstream_gb' => round($gb, 1)];
    }
}
