<?php

namespace App\Application\DTO;

use Illuminate\Http\Request;

class StartGameDTO
{
    public function __construct(
        public readonly int $roomId,
        public readonly array $playerIds
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            roomId: (int)$request->input('room_id'),
            playerIds: $request->input('players', [])
        );
    }

    public static function fromValues(int $roomId, array $playerIds): self
    {
        return new self($roomId, $playerIds);
    }
}