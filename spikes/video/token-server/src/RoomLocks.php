<?php

declare(strict_types=1);

namespace Spike\Video;

/** "Lock room" (CLS-04): neither provider has a room lock, so the server stops issuing tokens. */
final class RoomLocks
{
    public function __construct(private readonly string $file) {}

    public function isLocked(string $room): bool
    {
        return in_array($room, $this->read(), true);
    }

    public function lock(string $room): void
    {
        $this->write(array_values(array_unique([...$this->read(), $room])));
    }

    public function unlock(string $room): void
    {
        $this->write(array_values(array_diff($this->read(), [$room])));
    }

    private function read(): array
    {
        $data = is_file($this->file) ? json_decode((string) file_get_contents($this->file), true) : [];

        return is_array($data) ? $data : [];
    }

    private function write(array $rooms): void
    {
        file_put_contents($this->file, json_encode($rooms), LOCK_EX);
    }
}
