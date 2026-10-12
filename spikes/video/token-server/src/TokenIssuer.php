<?php

declare(strict_types=1);

namespace Spike\Video;

/**
 * POST /token: one short-lived token per identity, room, role and provider (CLS-03), only with the
 * spike access code (CLS-05), only inside the join window (CLS-02), never for a locked room (CLS-04).
 */
final class TokenIssuer
{
    public const ADMIN_TICKET_TTL = 7200;

    public function __construct(private readonly Config $config, private readonly RoomLocks $locks) {}

    public function issue(array $input, int $now): array
    {
        if (! is_string($input['access_code'] ?? null) || $this->config->accessCode === ''
            || ! hash_equals($this->config->accessCode, $input['access_code'])) {
            throw new ApiError(403, 'forbidden');
        }

        $provider = $input['provider'] ?? null;
        $role = is_string($input['role'] ?? null) ? Role::tryFrom($input['role']) : null;
        $identity = is_string($input['identity'] ?? null) ? $input['identity'] : '';
        $room = is_string($input['room'] ?? null) ? $input['room'] : '';
        $invalid = array_filter([
            'provider' => ! in_array($provider, ['livekit', 'zoom'], true),
            'role' => $role === null,
            'identity' => $role !== null && ! $role->acceptsIdentity($identity),
            'room' => preg_match('/^[a-z0-9][a-z0-9-]{2,39}$/', $room) !== 1,
        ]);
        if ($invalid !== []) {
            throw new ApiError(422, 'validation_failed', ['fields' => array_keys($invalid)]);
        }

        $opens = $this->config->windowOpensAt;
        $closes = $this->config->windowClosesAt;
        if (($opens !== null && $now < $opens) || ($closes !== null && $now > $closes)) {
            throw new ApiError(409, 'outside_window', array_filter([
                'opens_at' => $opens === null ? null : gmdate('Y-m-d\TH:i:s\Z', $opens),
                'closes_at' => $closes === null ? null : gmdate('Y-m-d\TH:i:s\Z', $closes),
            ]));
        }
        if ($this->locks->isLocked($room)) {
            throw new ApiError(409, 'room_locked');
        }

        $name = Lang::get('ar', 'name.'.$role->value, ['n' => substr($identity, -1)]);
        if ($provider === 'livekit') {
            if ($this->config->liveKitApiKey === '' || $this->config->liveKitApiSecret === '' || $this->config->liveKitUrl === '') {
                throw new ApiError(503, 'provider_not_configured', ['provider' => 'livekit']);
            }
            $out = [
                'token' => LiveKitToken::build($this->config, $role, $identity, $name, $room, $now),
                'server_url' => $this->config->liveKitUrl,
                'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $now + LiveKitToken::TTL),
            ];
        } else {
            if ($this->config->zoomSdkKey === '' || $this->config->zoomSdkSecret === '') {
                throw new ApiError(503, 'provider_not_configured', ['provider' => 'zoom']);
            }
            $token = ZoomToken::build($this->config, $role, $identity, $room, $now);
            $out = [
                'token' => $token,
                'session_name' => $room,
                'expires_at' => gmdate('Y-m-d\TH:i:s\Z', Jwt::decode($token, $this->config->zoomSdkSecret, $now)['exp']),
            ];
        }

        $out += ['provider' => $provider, 'room' => $room, 'identity' => $identity, 'role' => $role->value, 'display_name' => $name];
        if ($role->isHost()) {
            $out['admin_ticket'] = Jwt::encode([
                'aud' => 'spike-admin',
                'sub' => $identity,
                'role' => $role->value,
                'room' => $room,
                'provider' => $provider,
                'iat' => $now,
                'exp' => $now + self::ADMIN_TICKET_TTL,
            ], $this->config->ticketSecret);
        }

        return $out;
    }
}
