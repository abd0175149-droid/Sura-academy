<?php

declare(strict_types=1);

namespace Spike\Video;

/**
 * Server-side moderation and recording on LiveKit Cloud (Twirp JSON API,
 * https://docs.livekit.io/reference/other/roomservice-api/). Only this server holds room-admin power.
 */
final class LiveKitAdmin
{
    public function __construct(private readonly Config $config, private readonly HttpClient $http, private readonly int $now) {}

    public function participants(string $room): array
    {
        return $this->call('RoomService', 'ListParticipants', ['room' => $room], $room)['participants'] ?? [];
    }

    /** Mutes every unmuted microphone except the hosts'; returns the identities muted. */
    public function muteAll(string $room): array
    {
        $muted = [];
        foreach ($this->participants($room) as $p) {
            $identity = (string) ($p['identity'] ?? '');
            if (preg_match('/^(teacher|substitute)-/', $identity) === 1) {
                continue;
            }
            foreach ($p['tracks'] ?? [] as $track) {
                if (($track['source'] ?? '') === 'MICROPHONE' && ! ($track['muted'] ?? false)) {
                    $this->call('RoomService', 'MutePublishedTrack', ['room' => $room, 'identity' => $identity, 'track_sid' => $track['sid'], 'muted' => true], $room);
                    $muted[] = $identity;
                }
            }
        }

        return array_values(array_unique($muted));
    }

    /** On LiveKit Cloud this also revokes the participant's token. */
    public function remove(string $room, string $identity): void
    {
        $this->call('RoomService', 'RemoveParticipant', ['room' => $room, 'identity' => $identity], $room);
    }

    /** Room-composite recording uploaded straight to our S3-compatible bucket (LiveKit keeps no copy). */
    public function startRecording(string $room): string
    {
        $s3 = $this->config->egressS3;
        if ($s3 === null) {
            throw new ApiError(503, 'provider_not_configured', ['storage' => 'egress bucket']);
        }
        $out = $this->call('Egress', 'StartRoomCompositeEgress', [
            'room_name' => $room,
            'layout' => 'grid',
            'file_outputs' => [[
                'file_type' => 'MP4',
                'filepath' => $room.'/'.$room.'-'.gmdate('Ymd-His', $this->now).'.mp4',
                's3' => [
                    'access_key' => $s3['access_key'],
                    'secret' => $s3['secret'],
                    'region' => $s3['region'],
                    'endpoint' => $s3['endpoint'],
                    'bucket' => $s3['bucket'],
                    'force_path_style' => true,
                ],
            ]],
        ], $room);

        return (string) ($out['egressId'] ?? $out['egress_id'] ?? '');
    }

    /** Recent recordings of the room with LiveKit's status and error text (why a recording failed). */
    public function recordings(string $room): array
    {
        $items = $this->call('Egress', 'ListEgress', ['room_name' => $room], $room)['items'] ?? [];

        return array_map(static fn (array $e): array => [
            'egress_id' => $e['egressId'] ?? $e['egress_id'] ?? null,
            'status' => $e['status'] ?? null,
            'error' => $e['error'] ?? null,
            'started_at' => $e['startedAt'] ?? $e['started_at'] ?? null,
            'files' => array_map(static fn (array $f): array => array_intersect_key($f, array_flip(['filename', 'location', 'size', 'duration'])), $e['fileResults'] ?? $e['file_results'] ?? []),
        ], $items);
    }

    public function stopRecording(string $room, string $egressId): void
    {
        $this->call('Egress', 'StopEgress', ['egress_id' => $egressId], $room);
    }

    private function call(string $service, string $method, array $body, string $room): array
    {
        $base = preg_replace('#^ws(s?)://#', 'http$1://', rtrim($this->config->liveKitUrl, '/'));
        [$status, $response] = $this->http->request('POST', "{$base}/twirp/livekit.{$service}/{$method}", [
            'Authorization' => 'Bearer '.LiveKitToken::admin($this->config, $room, $this->now),
            'Content-Type' => 'application/json',
        ], json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        if ($status < 200 || $status >= 300) {
            throw new ApiError(502, 'provider_error', ['provider' => 'livekit', 'method' => $method, 'status' => $status, 'body' => mb_substr($response, 0, 300)]);
        }

        return json_decode($response, true) ?: [];
    }
}
