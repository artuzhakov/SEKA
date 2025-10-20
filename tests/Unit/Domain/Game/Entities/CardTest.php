<?php
// tests/Unit/Domain/Game/Entities/CardTest.php
namespace Tests\Unit\Domain\Game\Entities;

use Tests\TestCase;
use App\Domain\Game\Entities\Card;
use App\Domain\Game\Enums\CardSuit;
use App\Domain\Game\Enums\CardRank;

class CardTest extends TestCase
{
    /** @test */
    public function it_identifies_joker_correctly()
    {
        $joker = new Card(CardSuit::CLUBS, CardRank::SIX);
        $notJoker1 = new Card(CardSuit::HEARTS, CardRank::SIX);
        $notJoker2 = new Card(CardSuit::CLUBS, CardRank::SEVEN);
        
        $this->assertTrue($joker->isJoker(), '6 крестей должен быть джокером');
        $this->assertFalse($notJoker1->isJoker(), '6 не крестей не должен быть джокером');
        $this->assertFalse($notJoker2->isJoker(), '7 крестей не должен быть джокером');
    }
    
    /** @test */
    public function it_identifies_ace_correctly()
    {
        $ace = new Card(CardSuit::HEARTS, CardRank::ACE);
        $notAce = new Card(CardSuit::HEARTS, CardRank::KING);
        
        $this->assertTrue($ace->isAce(), 'Туз должен определяться как туз');
        $this->assertFalse($notAce->isAce(), 'Король не должен определяться как туз');
    }
    
    /** @test */
    public function it_compares_cards_correctly()
    {
        $card1 = new Card(CardSuit::HEARTS, CardRank::ACE);
        $card2 = new Card(CardSuit::HEARTS, CardRank::ACE);
        $card3 = new Card(CardSuit::DIAMONDS, CardRank::ACE);
        
        $this->assertTrue($card1->equals($card2), 'Одинаковые карты должны быть равны');
        $this->assertFalse($card1->equals($card3), 'Разные карты не должны быть равны');
    }
}