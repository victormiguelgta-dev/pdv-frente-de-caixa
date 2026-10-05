<?php

use Illuminate\Support\Facades\Schedule;

/*
| Tarefas agendadas (rodam com "php artisan schedule:work" ou pelo cron do
| servidor em produção).
|
| Uma vez por dia, apaga do banco os tokens de login vencidos há mais de 24h.
| Eles já não funcionam (o Sanctum recusa token expirado), mas não precisam
| ficar guardados para sempre.
*/
Schedule::command('sanctum:prune-expired --hours=24')->daily();
