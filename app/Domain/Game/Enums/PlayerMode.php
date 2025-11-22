<?php

namespace App\Domain\Game\Enums;

enum PlayerMode: string
{
    case NONE = 'none';
    case DARK = 'dark';
    case OPEN = 'open';
}
