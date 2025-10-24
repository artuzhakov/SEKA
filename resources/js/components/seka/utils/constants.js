// Game constants
export const GAME_STATUS = {
  WAITING: 'waiting',
  ACTIVE: 'active',
  BIDDING: 'bidding',
  FINISHED: 'finished'
}

export const PLAYER_STATUS = {
  ACTIVE: 'active',
  FOLDED: 'folded',
  REVEALED: 'revealed',
  DARK: 'dark',
  READY: 'ready',
  WAITING: 'waiting'
}

export const ACTIONS = {
  CHECK: 'check',
  CALL: 'call',
  RAISE: 'raise',
  FOLD: 'fold',
  DARK: 'dark',
  REVEAL: 'reveal',
  OPEN: 'open'
}

export const ROUNDS = {
  1: 'first',
  2: 'second', 
  3: 'third'
}

// API endpoints
export const API_ENDPOINTS = {
  GAME_INFO: (id) => `/api/seka/${id}/game-info`,
  STATUS: (id) => `/api/seka/${id}/status`,
  ACTION: (id) => `/api/seka/${id}/action`,
  CARDS: (id) => `/api/seka/${id}/cards`,
  READY: (id) => `/api/seka/${id}/ready`,
  DISTRIBUTE: (id) => `/api/seka/${id}/distribute`,
  START_BIDDING: (id) => `/api/seka/${id}/start-bidding`,
  CLEAR: (id) => `/api/seka/${id}/clear`
}