<?php

declare(strict_types=1);

namespace Spike\Video;

/**
 * CLS-09 feasibility: minutes present per identity from provider join/leave events. Overlapping
 * sessions of one identity (reconnects) count once; a participant still present is counted until
 * $endAt. The real attendance rules (thresholds, statuses, teacher confirmation) belong to WP-3.2.
 */
final class AttendanceProbe
{
    /** @return array<string, float> identity => minutes (1 decimal), sorted by identity */
    public static function minutes(array $events, string $room, int $endAt): array
    {
        $events = array_values(array_filter($events, static fn (array $e): bool => ($e['room'] ?? null) === $room
            && isset($e['identity']) && in_array($e['type'] ?? null, ['joined', 'left'], true)));
        usort($events, static fn (array $a, array $b): int => $a['at'] <=> $b['at']);

        $depth = [];
        $since = [];
        $seconds = [];
        foreach ($events as $e) {
            $who = $e['identity'];
            $depth[$who] ??= 0;
            $seconds[$who] ??= 0;
            if ($e['type'] === 'joined') {
                if ($depth[$who] === 0) {
                    $since[$who] = $e['at'];
                }
                $depth[$who]++;
            } elseif ($depth[$who] > 0) {
                $depth[$who]--;
                if ($depth[$who] === 0) {
                    $seconds[$who] += $e['at'] - $since[$who];
                }
            }
        }
        foreach ($depth as $who => $d) {
            if ($d > 0) {
                $seconds[$who] += max(0, $endAt - $since[$who]);
            }
        }
        ksort($seconds);

        return array_map(static fn (int $s): float => round($s / 60, 1), $seconds);
    }
}
