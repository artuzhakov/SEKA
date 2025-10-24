export const validateGameState = (gameState) => {
  const errors = []
  
  if (!gameState.players || !Array.isArray(gameState.players)) {
    errors.push('Invalid players data')
    return errors
  }
  
  gameState.players.forEach((player, index) => {
    if (!player.id || !player.position) {
      errors.push(`Player ${index}: missing ID or position`)
    }
    
    if (player.balance < 0) {
      errors.push(`Player ${player.id}: negative balance`)
    }
    
    if (player.current_bet < 0) {
      errors.push(`Player ${player.id}: negative bet`)
    }
    
    if (player.current_bet > player.balance) {
      errors.push(`Player ${player.id}: bet exceeds balance`)
    }
  })
  
  return errors
}

export const checkDataConsistency = (gameState) => {
  const inconsistencies = []
  
  const positions = gameState.players.map(p => p.position)
  const uniquePositions = new Set(positions)
  if (positions.length !== uniquePositions.size) {
    inconsistencies.push('Duplicate player positions')
  }
  
  const totalBets = gameState.players.reduce((sum, player) => sum + (player.current_bet || 0), 0)
  if (gameState.bank !== totalBets) {
    inconsistencies.push(`Bank mismatch: bank=${gameState.bank}, totalBets=${totalBets}`)
  }
  
  return inconsistencies
}