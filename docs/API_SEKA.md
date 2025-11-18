# 🔌 SEKA — API документация

Документация отражает реальные маршруты из `routes/api.php` и `routes/public.php`.

---

# 1. 📌 Общие принципы

- Все игровые действия проходят через API.
- Frontend — «тонкий клиент».
- Backend — единственный источник истины.
- Ответы возвращаются в JSON.

---

# 2. 📁 Публичное API

## ➤ Подсчёт очков

POST /public/seka/calculate-points


### Body:
```json
{
  "cards": ["A♥", "10♦", "6♣"]
}

Response:

{
  "success": true,
  "points": 32,
  "combination": "Joker + Ace suited"
}

3. 🎮 Game API

Все игровые эндпоинты находятся в:
/api/seka/games

➤ Создание игры
POST /api/seka/games/create
Response:
{
  "gameId": 12,
  "status": "waiting"
}

➤ Присоединение
POST /api/seka/games/{id}/join

➤ Отметить готовность
POST /api/seka/games/{id}/ready

➤ Взнос анте
POST /api/seka/games/{id}/collect-ante

➤ Получение состояния игры (основной endpoint)
GET /api/seka/games/{id}

Пример ответа:
{
  "gameId": 12,
  "status": "bidding",
  "round": 1,
  "pot": 150,
  "currentTurn": 3,
  "players": [
    {
      "id": 10,
      "name": "Alice",
      "balance": 480,
      "bet": 20,
      "status": "active",
      "cards": ["A♥", "Q♦", "10♠"],
      "isDark": false
    },
    {
      "id": 11,
      "name": "Bob",
      "balance": 510,
      "bet": 10,
      "status": "dark",
      "cards": ["?", "?", "?"],
      "isDark": true
    }
  ]
}

➤ Выполнение действия игрока
POST /api/seka/games/{id}/action

Body:
{
  "action": "raise",
  "amount": 25
}


Поддерживаемые действия:

check

call

raise

dark

fold

open

➤ Полный snapshot игры
GET /api/seka/games/{id}/full-state

➤ Открыть карты игрока
POST /api/seka/games/{id}/reveal

➤ Оставить игру
POST /api/seka/games/{id}/leave

4. ⚔️ API Свары
POST /api/seka/games/{id}/quarrel/start
POST /api/seka/games/{id}/quarrel/vote
POST /api/seka/games/{id}/quarrel/complete


Эндпоинты отражают логику:

выбор участников,

взнос,

перераздача,

старты раундов.

5. 💡 Ошибки

Все ошибки возвращаются как:

{
  "success": false,
  "error": "Invalid action",
  "code": 400
}

6. 🎯 Итог

Этот документ — фактическое API проекта SEKA.

