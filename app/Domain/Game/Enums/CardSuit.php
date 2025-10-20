<?php
// app/Domain/Game/Enums/CardSuit.php
declare(strict_types=1);

namespace App\Domain\Game\Enums;

enum CardSuit: string
{
    case HEARTS = 'hearts';
    case DIAMONDS = 'diamonds'; 
    case CLUBS = 'clubs';
    case SPADES = 'spades';
}