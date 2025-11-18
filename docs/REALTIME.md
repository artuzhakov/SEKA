---

# 📡 **docs/REALTIME.md**

```markdown
# 📡 SEKA — WebSocket / Real-time события

SEKA использует real-time обновления через Laravel WebSockets.

Frontend подписывается на канал:



private-game.{gameId}


---

# 1. 📡 События, которые отправляет Backend

## ✔ GameStateUpdated
Полный snapshot игры.

Payload:
```json
{
  "type": "game_state",
  "state": { /* game state */ }
}

✔ PlayerActionTaken

Происходит, когда игрок сделал действие.

{
  "playerId": 10,
  "action": "raise",
  "amount": 25
}

✔ CardsDistributed

Отправляется после раздачи.

{
  "players": {
    "10": ["A♥", "Q♦", "10♠"],
    "11": ["?", "?", "?"]
  }
}

✔ TurnChanged
{
  "currentTurn": 3
}

✔ RoundCompleted
{
  "round": 2,
  "pot": 150
}

✔ GameFinished
{
  "winnerId": 10,
  "points": 32,
  "hand": ["A♥", "10♥", "6♣"]
}

✔ QuarrelStarted
{
  "participants": [10, 11],
  "pot": 200
}

2. 📡 Как фронтенд слушает события
import { useWebSocket } from "../composables/useWebSocket"

const socket = useWebSocket(gameId)

socket.on("GameStateUpdated", (data) => {
    gameState.value = data.state
})

socket.on("PlayerActionTaken", (data) => {
    logAction(data)
})

3. 🎯 Итог

WebSocket — критический слой SEKA.
Он обеспечивает real-time поведение:

обновление ставок

смена хода

раздача карт

завершение раундов

свара

финал игры