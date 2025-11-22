# SEKA — Конечные автоматы (FSM)

Документ описывает основные конечные автоматы:

- FSM игры (GAME FSM)
- FSM игрока (PLAYER FSM)
- FSM раундов (ROUND FSM)
- FSM режимов (MODE FSM)
- FSM REVEAL
- FSM Свары (SVARA FSM)

## GAME FSM

См. также `GAME_STATES.md`.

Упрощённый поток:

- `WAITING_FOR_PLAYERS`
- `PREPARATION`
- `DEALING`
- `ROUND_1`
- `ROUND_2`
- `ROUND_3`
- `SHOWDOWN`
- если ничья → `SVARA_VOTING` → `SVARA_GAME` → `SHOWDOWN`
- иначе → `FINISHED` → `PAUSE_BEFORE_NEXT` → `PREPARATION`

## PLAYER FSM

См. также `PLAYER_STATES.md`.

Основной цикл:

- `TABLE_WAITING` → `TABLE_READY` → `IN_GAME`
- `IN_GAME` → `FOLDED | LEFT | WON`
- `FOLDED | WON` → `TABLE_WAITING`

## ROUND FSM

Для каждого раунда:

1. Выбор первого игрока (по очереди от дилера)
2. Для каждого активного игрока:
   - запуск таймера хода
   - ожидание действия
   - применение действия / AUTO-FOLD
3. Когда все активные игроки либо сходили, либо выбыли → раунд завершён.

## MODE FSM

См. `DARK_OPEN.md`.

## REVEAL FSM

См. `REVEAL.md`.

## SVARA FSM

См. `SVARA.md`.
