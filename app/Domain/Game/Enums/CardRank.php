<?php
// app/Domain/Game/Enums/CardRank.php
declare(strict_types=1);

namespace App\Domain\Game\Enums;

enum CardRank: string
{
    case SIX = 'six';
    case SEVEN = 'seven';
    case EIGHT = 'eight';
    case NINE = 'nine';
    case TEN = 'ten';
    case JACK = 'jack';
    case QUEEN = 'queen'; 
    case KING = 'king';
    case ACE = 'ace';
}