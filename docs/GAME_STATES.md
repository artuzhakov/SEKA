# SEKA — Состояния игры (GAME STATES)

Этот документ описывает высокоуровневые состояния игровой сессии.

## Перечень состояний

- `WAITING_FOR_PLAYERS` — ожидание игроков за столом
- `PREPARATION` — стадия готовности перед раздачей
- `DEALING` — раздача карт
- `ROUND_1` — первый раунд торгов
- `ROUND_2` — второй раунд торгов
- `ROUND_3` — третий раунд торгов
- `SHOWDOWN` — финальное вскрытие и подсчёт очков
- `SVARA_VOTING` — стадия голосований за Свару
- `SVARA_GAME` — игра в рамках Свары
- `FINISHED` — игра завершена, ожидание следующей
- `PAUSE_BEFORE_NEXT` — пауза 10 секунд перед новой игрой

## Диаграмма переходов (упрощённо)

- `WAITING_FOR_PLAYERS` → `PREPARATION`
- `PREPARATION` → `DEALING` (если ≥2 готовых игрока)
- `DEALING` → `ROUND_1`
- `ROUND_1` → `ROUND_2`
- `ROUND_2` → `ROUND_3`
- `ROUND_3` → `SHOWDOWN`
- `SHOWDOWN`:
  - если один победитель → `FINISHED`
  - если несколько победителей → `SVARA_VOTING`
- `SVARA_VOTING` → `SVARA_GAME` (если Свара одобрена)
- `SVARA_GAME` → `SHOWDOWN` (в контексте Свары)
- `FINISHED` → `PAUSE_BEFORE_NEXT` → `PREPARATION`
