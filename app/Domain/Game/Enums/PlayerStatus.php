<?php
// app/Domain/Game/Enums/PlayerStatus.php
declare(strict_types=1);

namespace App\Domain\Game\Enums;

enum PlayerStatus: string
{
    case WAITING = 'waiting';
    case READY = 'ready';
    case ACTIVE = 'active';
    case PASSED = 'passed';
    case REVEALED = 'revealed';
    case FOLDED = 'folded';
    case DARK = 'dark';
}