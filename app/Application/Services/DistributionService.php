<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\Entities\Card;
use App\Domain\Game\Enums\CardSuit;
use App\Domain\Game\Enums\CardRank;

class DistributionService
{
    /**
     * 🎯 Раздать карты всем игрокам (упрощенная колода)
     */
    public function distributeCards(Game $game): void
    {
        $players = $game->getActivePlayers();
        $deck = $this->createSimplifiedDeck();
        
        // 🎯 Раздаем по 3 карты каждому игроку
        foreach ($players as $player) {
            $playerCards = [];
            for ($i = 0; $i < 3; $i++) {
                if (!empty($deck)) {
                    $playerCards[] = array_shift($deck);
                }
            }
            $player->receiveCards($playerCards);
        }
    }

    /**
     * 🎯 Создать упрощенную колоду (21 карта)
     */
    private function createSimplifiedDeck(): array
    {
        $deck = [];
        
        // 🎯 4 десятки
        foreach (CardSuit::cases() as $suit) {
            $deck[] = new Card($suit, CardRank::TEN);
        }
        
        // 🎯 4 вальта
        foreach (CardSuit::cases() as $suit) {
            $deck[] = new Card($suit, CardRank::JACK);
        }
        
        // 🎯 4 дамы
        foreach (CardSuit::cases() as $suit) {
            $deck[] = new Card($suit, CardRank::QUEEN);
        }
        
        // 🎯 4 короля
        foreach (CardSuit::cases() as $suit) {
            $deck[] = new Card($suit, CardRank::KING);
        }
        
        // 🎯 4 туза
        foreach (CardSuit::cases() as $suit) {
            $deck[] = new Card($suit, CardRank::ACE);
        }
        
        // 🎯 1 джокер (6 крестей)
        $deck[] = new Card(CardSuit::CLUBS, CardRank::SIX); // Джокер
        
        // 🎯 Перемешиваем колоду
        shuffle($deck);
        
        return $deck;
    }

    /**
     * 🎯 Перераздать карты (для свары)
     */
    public function redistributeForQuarrel(array $players): void
    {
        $deck = $this->createSimplifiedDeck();
        
        foreach ($players as $player) {
            $player->receiveCards([]); // 🎯 Очищаем старые карты
            
            $newCards = [];
            for ($i = 0; $i < 3; $i++) {
                if (!empty($deck)) {
                    $newCards[] = array_shift($deck);
                }
            }
            $player->receiveCards($newCards);
        }
    }

    /**
     * 🎯 Получить информацию о колоде (для тестов)
     */
    public function getDeckInfo(): array
    {
        $deck = $this->createSimplifiedDeck();
        
        return [
            'total_cards' => count($deck),
            'cards_per_suit' => $this->countCardsPerSuit($deck),
            'has_joker' => $this->hasJoker($deck)
        ];
    }

    private function countCardsPerSuit(array $deck): array
    {
        $counts = [];
        foreach (CardSuit::cases() as $suit) {
            $counts[$suit->value] = 0;
        }
        
        foreach ($deck as $card) {
            if (!$card->isJoker() && $card->getSuit()) {
                $counts[$card->getSuit()->value]++;
            }
        }
        
        return $counts;
    }

    private function hasJoker(array $deck): bool
    {
        foreach ($deck as $card) {
            if ($card->isJoker()) {
                return true;
            }
        }
        return false;
    }
}