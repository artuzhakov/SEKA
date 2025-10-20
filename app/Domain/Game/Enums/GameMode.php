<?php
// app/Domain/Game/Enums/GameMode.php
declare(strict_types=1);

namespace App\Domain\Game\Enums;

enum GameMode: string
{
    case DARK = 'dark';
    case OPEN = 'open';
}