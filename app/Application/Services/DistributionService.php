<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\Entities\Card;
use App\Domain\Game\Enums\CardSuit;
use App\Domain\Game\Enums\CardRank;
use App\Domain\Game\Enums\GameStatus;

class DistributionService
{

    public function __construct(
        private BiddingService $biddingService
    ) {}
    
    /**
     * 🎯 Раздать карты всем игрокам
     */
    public function distributeCards(Game $game): array
    {
        \Log::info("🎴 Starting card distribution for game: " . $game->getId()->toInt());
        
        $players = $game->getActivePlayers();
        $deck = $this->createSimplifiedDeck();
        
        \Log::info("🎴 Created deck with " . count($deck) . " cards");
        
        // 🎯 Раздаем по 3 карты каждому игроку
        $playerCards = [];
        foreach ($players as $player) {
            $playerHand = [];
            for ($i = 0; $i < 3; $i++) {
                if (!empty($deck)) {
                    $card = array_shift($deck);
                    $playerHand[] = $this->cardToArray($card);
                }
            }
            $playerCards[$player->getUserId()] = $playerHand;
            
            \Log::info("🎴 Player {$player->getUserId()} received " . count($playerHand) . " cards");
        }
        
        // 🎯 Запускаем систему торгов через BiddingService
        $this->biddingService->startBiddingRound($game);
        
        // 🎯 Обновить статус игры на BIDDING
        $this->updateGameStatus($game, GameStatus::BIDDING);
        
        // 🎯 Сохраняем игру после раздачи
        $this->saveGame($game);
        
        \Log::info("🎴 Distribution complete. Status: " . $game->getStatus()->value);
        
        return [
            'player_cards' => $playerCards,
            'community_cards' => [],
            'round' => 'preflop',
            'dealer_position' => $game->getCurrentPlayerPosition()
        ];
    }

    // 🎯 ДОБАВЬТЕ ЭТОТ МЕТОД В BiddingService ИЛИ DistributionService
    private function startBiddingRound(Game $game): void
    {
        \Log::info("🎯 Starting bidding round for game: " . $game->getId()->toInt());
        
        // Устанавливаем начальные значения для торгов
        $game->setCurrentBiddingRound(1);
        $game->setCurrentMaxBet(0);
        $game->setBank(0);
        
        // Сбрасываем ставки игроков для нового раунда
        foreach ($game->getActivePlayers() as $player) {
            $player->setCurrentBet(0);
            $player->setHasFolded(false);
            // Другие сбросы состояний если нужно
        }
        
        \Log::info("🎯 Bidding round initialized. First player position: " . $game->getCurrentPlayerPosition());
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
        $deck[] = new Card(CardSuit::CLUBS, CardRank::SIX);
        
        // 🎯 Перемешиваем колоду
        shuffle($deck);
        
        return $deck;
    }

    /**
     * 🎯 Преобразовать Card в массив для фронтенда
     */
    private function cardToArray(Card $card): array
    {
        // 🎯 Адаптируем под методы существующего Card класса
        return [
            'suit' => $card->getSuit()->value ?? $card->getSuit(),
            'rank' => $card->getRank()->value ?? $card->getRank(),
            'is_face_up' => false, // 🎯 Карты раздаются рубашкой вверх
            'is_joker' => $card->isJoker() ?? ($card->getRank() === CardRank::SIX && $card->getSuit() === CardSuit::CLUBS),
        ];
    }

    /**
     * 🎯 Выбрать случайного дилера
     */
    private function selectRandomDealer(Game $game): int
    {
        $players = $game->getPlayers();
        $randomPlayer = $players[array_rand($players)];
        return $randomPlayer->getPosition();
    }
    
    /**
     * 🎯 Обновить статус игры
     */
    private function updateGameStatus(Game $game, GameStatus $status): void
    {
        $reflection = new \ReflectionClass($game);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($game, $status);
    }
    
    /**
     * 🎯 Сохранить игру в репозитории
     */
    private function saveGame(Game $game): void
    {
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $repository->save($game);
        \Log::info("💾 Game saved to repository after card distribution");
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