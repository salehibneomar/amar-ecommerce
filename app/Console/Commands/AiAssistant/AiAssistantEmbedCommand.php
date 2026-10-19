<?php

namespace App\Console\Commands\AiAssistant;

use App\Interfaces\AiAssistant\AiAssistantInterface;
use App\Traits\AiAssistant\AiAssistantEmbeddingTrait;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class AiAssistantEmbedCommand extends Command
{
    use AiAssistantEmbeddingTrait;

    /**
     * @var string
     */
    protected $signature = 'ai-assistant:embed {--sleep=1 : Seconds to wait after each embedding call, to respect the provider rate limit}';

    /**
     * @var string
     */
    protected $description = 'Embed new or changed records of every AI assistant model (config ai-assistant.models)';

    public function handle(): int
    {
        if (! aiAssistantEmbeddingModel()) {
            $this->components->warn('No embedding model is configured. The assistant will use keyword search.');

            return self::SUCCESS;
        }

        $embedded = 0;
        $failed = 0;

        foreach (aiAssistantModels() as $class) {
            if (! is_subclass_of($class, AiAssistantInterface::class)) {
                continue;
            }

            foreach ($class::aiAssistantEmbeddingQuery()->lazyById() as $model) {
                try {
                    if ($this->syncAiAssistantEmbedding($model)) {
                        $embedded++;
                        sleep(max(0, (int) $this->option('sleep')));
                    }
                } catch (ConnectionException|RequestException $exception) {
                    report($exception);
                    $failed++;
                }
            }
        }

        $this->components->info("Embedded {$embedded} records. {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
