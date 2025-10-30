// utils/scoringService.js
export class ScoringService {
    static JOKER = '6♣'
    
    static calculateHandValue(cards) {
        if (!cards || cards.length === 0) return 0
        
        if (cards.length === 3) {
            return this.calculateThreeCardHand(cards)
        } else if (cards.length === 2) {
            return this.calculateTwoCardHand(cards)
        }
        
        return 10 // Минимальные очки по умолчанию
    }
    
    static calculateThreeCardHand(cards) {
        const hasJoker = this.hasJoker(cards)
        const suits = this.getSuits(cards)
        const ranks = this.getRanks(cards)
        
        // Проверяем СЕКА комбинации
        const sekaCombo = this.checkSekaCombinations(ranks, hasJoker)
        if (sekaCombo > 0) return sekaCombo
        
        // Проверяем комбинации с мастями
        const suitCombo = this.checkSuitCombinations(suits, hasJoker, ranks)
        if (suitCombo > 0) return suitCombo
        
        // Базовая комбинация
        return this.getBaseCombination(suits, hasJoker, ranks)
    }
    
    static calculateTwoCardHand(cards) {
        const hasJoker = this.hasJoker(cards)
        const suits = this.getSuits(cards)
        const ranks = this.getRanks(cards)
        
        return this.getTwoCardCombination(suits, hasJoker, ranks)
    }
    
    static checkSekaCombinations(ranks, hasJoker) {
        const rankCounts = this.countRanks(ranks)
        
        // Убираем джокер из подсчета
        if (hasJoker) {
            delete rankCounts['6']
        }
        
        // СЕКА ТУЗОВ (37)
        if ((rankCounts['A'] || 0) === 3) return 37
        if (hasJoker && (rankCounts['A'] || 0) === 2) return 37
        
        // СЕКА КОРОЛЕЙ (36)
        if ((rankCounts['K'] || 0) === 3) return 36
        if (hasJoker && (rankCounts['K'] || 0) === 2) return 36
        
        // СЕКА ДАМ (35)
        if ((rankCounts['Q'] || 0) === 3) return 35
        if (hasJoker && (rankCounts['Q'] || 0) === 2) return 35
        
        // СЕКА ВАЛЬТОВ (34)
        if ((rankCounts['J'] || 0) === 3) return 34
        if (hasJoker && (rankCounts['J'] || 0) === 2) return 34
        
        // СЕКА ДЕСЯТОК (33)
        if ((rankCounts['10'] || 0) === 3) return 33
        if (hasJoker && (rankCounts['10'] || 0) === 2) return 33
        
        return 0
    }
    
    static checkSuitCombinations(suits, hasJoker, ranks) {
        const suitCounts = this.countSuits(suits)
        const maxSameSuit = Math.max(...Object.values(suitCounts))
        const hasAce = ranks.includes('A')
        
        // 32: Три одной масти + Туз + Джокер
        if (maxSameSuit === 3 && hasAce && hasJoker) {
            return 32
        }
        
        // 31: Три одной масти + (Туз ИЛИ Джокер)
        if (maxSameSuit === 3 && (hasAce || hasJoker)) {
            return 31
        }
        
        // 30: Три одной масти
        if (maxSameSuit === 3) {
            return 30
        }
        
        return 0
    }
    
    static getBaseCombination(suits, hasJoker, ranks) {
        const uniqueSuits = new Set(suits).size
        const hasAce = ranks.includes('A')
        
        // 11: Есть туз
        if (hasAce) {
            return 11
        }
        
        // 10: Базовые
        return 10
    }
    
    static getTwoCardCombination(suits, hasJoker, ranks) {
        const suitCounts = this.countSuits(suits)
        const maxSameSuit = Math.max(...Object.values(suitCounts))
        const aceCount = ranks.filter(r => r === 'A').length
        
        // 22: Два туза ИЛИ Туз + Джокер
        if (aceCount === 2 || (aceCount === 1 && hasJoker)) {
            return 22
        }
        
        // 21: Две одной масти + Туз ИЛИ Джокер + карта
        if ((maxSameSuit === 2 && aceCount === 1) || hasJoker) {
            return 21
        }
        
        // 20: Две одной масти
        if (maxSameSuit === 2) {
            return 20
        }
        
        return 20
    }
    
    // Вспомогательные методы
    static hasJoker(cards) {
        return cards.some(card => 
            card === this.JOKER || 
            card.value === '6' && card.suit === '♣' ||
            card.isJoker
        )
    }
    
    static getSuits(cards) {
        return cards.map(card => {
            if (typeof card === 'string') return card.slice(-1) // Последний символ - масть
            return card.suit || card.mast
        })
    }
    
    static getRanks(cards) {
        return cards.map(card => {
            if (typeof card === 'string') {
                const rank = card.slice(0, -1) // Все кроме последнего символа
                return this.normalizeRank(rank)
            }
            return this.normalizeRank(card.value || card.rank)
        })
    }
    
    static normalizeRank(rank) {
        const map = {
            '6': '6', '7': '7', '8': '8', '9': '9', '10': '10',
            'В': 'J', 'J': 'J', 'Д': 'Q', 'Q': 'Q', 'К': 'K', 'K': 'K', 'Т': 'A', 'A': 'A'
        }
        return map[rank] || rank
    }
    
    static countRanks(ranks) {
        return ranks.reduce((counts, rank) => {
            counts[rank] = (counts[rank] || 0) + 1
            return counts
        }, {})
    }
    
    static countSuits(suits) {
        return suits.reduce((counts, suit) => {
            counts[suit] = (counts[suit] || 0) + 1
            return counts
        }, {})
    }
}