<?php

declare(strict_types=1);

namespace Spike\Video;

/** Roles in a class room (CLS-04, CLS-13) plus the spike-only `bot` that feeds join-time trials. */
enum Role: string
{
    case Teacher = 'teacher';
    case Substitute = 'substitute';
    case Student = 'student';
    case Supervisor = 'supervisor';
    case Bot = 'bot';

    /** Teacher and substitute moderate the room (Zoom host, spike admin ticket). */
    public function isHost(): bool
    {
        return $this === self::Teacher || $this === self::Substitute;
    }

    /** LiveKit `canPublishSources`: students never screen-share by default; the supervisor only speaks. */
    public function liveKitSources(): array
    {
        return match ($this) {
            self::Teacher, self::Substitute => ['camera', 'microphone', 'screen_share', 'screen_share_audio'],
            self::Student, self::Bot => ['camera', 'microphone'],
            self::Supervisor => ['microphone'],
        };
    }

    /** Pseudonym identities only (`student-2`), so no real name reaches a provider or a log. */
    public function acceptsIdentity(string $identity): bool
    {
        return preg_match('/^'.$this->value.'-[1-9]$/', $identity) === 1;
    }
}
