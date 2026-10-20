<?php

namespace App\Console\Commands;

use App\Services\Chat\Routing\RoutingInput;
use App\Services\Chat\Routing\VeniceRequestRouter;
use Illuminate\Console\Command;

/**
 * Насколько точно диспетчер чата понимает просьбы.
 *
 * Прогоняет размеченную таблицу (tests/Fixtures/chat-routing.json)
 * через настоящую модель и печатает процент попаданий и ошибки.
 * Запросы платные: прогон таблицы стоит около цента.
 */
class EvaluateChatRouting extends Command
{
    protected $signature = 'chat:evaluate-routing
        {--model= : Модель-диспетчер, по умолчанию из config/ai.php}
        {--sample=0 : Сколько сообщений взять для быстрого прогона, 0 — все}';

    protected $description = 'Проверяет точность диспетчера чата на размеченной таблице';

    public function handle(): int
    {
        $apiKey = config('studio.api_key');

        if (blank($apiKey)) {
            $this->error('Нет ключа провайдера (VENICE_API_KEY).');

            return self::FAILURE;
        }

        $model = $this->option('model') ?: config('ai.router.model');
        $router = new VeniceRequestRouter(config('studio.base_url'), $apiKey, $model, 20);
        $cases = json_decode((string) file_get_contents(base_path('tests/Fixtures/chat-routing.json')), true);

        // Быстрый прогон: равномерно по таблице, чтобы в выборку попал
        // каждый раздел, — таблица сгруппирована по разделам.
        $sample = (int) $this->option('sample');

        if ($sample > 0 && $sample < count($cases)) {
            $step = count($cases) / $sample;
            $cases = array_map(fn (int $i) => $cases[(int) floor($i * $step)], range(0, $sample - 1));
        }

        $this->info("Модель: {$model}, сообщений: ".count($cases));

        $results = [];
        $started = microtime(true);

        foreach ($cases as $case) {
            $results[] = [$case, $router->route(new RoutingInput(
                message: $case['message'],
                attachedImages: $case['attached_images'] ?? 0,
                previousReplyWasImage: $case['previous_reply_was_image'] ?? false,
                recent: $case['recent'] ?? [],
            ))];
        }

        $wrong = array_filter($results, fn ($result) => ($result[1]?->intent ?? 'нет ответа') !== $result[0]['expect']);
        $accuracy = (count($results) - count($wrong)) / max(1, count($results)) * 100;

        $this->table(['Сообщение', 'Ожидали', 'Получили', 'Уверенность'], array_map(fn ($result) => [
            mb_substr($result[0]['message'], 0, 60),
            $result[0]['expect'],
            $result[1]?->intent ?? 'нет ответа',
            $result[1] ? number_format($result[1]->confidence, 2) : '—',
        ], $wrong));

        $this->info(sprintf(
            'Точность: %.1f%% (%d из %d), %.1f с на сообщение в среднем',
            $accuracy,
            count($results) - count($wrong),
            count($results),
            (microtime(true) - $started) / max(1, count($results)),
        ));

        return self::SUCCESS;
    }
}
