# SEKA — API Reference (упрощённый)

Набросок структуры API (для уточнения по фактическому коду).

## Общие принципы

- Все критические операции — только на бэкенде (Laravel).
- Фронтенд вызывает JSON API + WebSocket события.

## Примеры REST-эндпоинтов

### Игры

- `GET /api/seka/games` — список игр / столов
- `POST /api/seka/games` — создать игру / стол
- `GET /api/seka/games/{id}` — состояние игры
- `POST /api/seka/games/{id}/join` — присоединиться к игре
- `POST /api/seka/games/{id}/ready` — отметить готовность

### Действия игрока

- `POST /api/seka/games/{id}/action`

Пример тела:

```json
{
  "action": "call | raise | fold | check | reveal | dark | open",
  "amount": 100   // для raise / dark / других ставок, если нужно
}
```

### Подсчёт очков

- `POST /api/public/seka/calculate-points`

```json
{
  "cards": ["10♥", "J♦", "Q♣"],
  "card_count": 3
}
```

## WebSocket события (пример)

- `game_state_updated` — обновлённое состояние игры
- `player_action` — действие игрока
- `round_started` — новый раунд
- `round_finished` — раунд завершён
- `game_finished` — игра завершена
- `svara_started` — началась Свара
