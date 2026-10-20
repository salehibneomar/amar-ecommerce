<?php

namespace App\Livewire\Frontend\AiAssistant;

use App\Enums\AiAssistant\AiAssistantRoleEnum;
use App\Models\Product;
use App\Services\Frontend\AiAssistant\AiAssistantSearchService;
use App\Services\Frontend\AiAssistant\AiAssistantService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection<int, Product> $products
 */
class Index extends Component
{
    public string $message = '';

    /** @var list<array{role: string, text: string, suggestions: list<array{id: int, reason: string}>}> */
    public array $messages = [];

    protected AiAssistantService $assistantService;

    protected AiAssistantSearchService $searchService;

    public function boot(AiAssistantService $assistantService, AiAssistantSearchService $searchService): void
    {
        $this->assistantService = $assistantService;
        $this->searchService = $searchService;
    }

    public function send(): void
    {
        if (! isAiAssistantEnabled()) {
            return;
        }

        $this->validate(['message' => ['required', 'string', 'max:'.aiAssistantMessageMaxLength()]]);

        $message = trim($this->message);
        $result = $this->assistantService->reply($this->messages, $message, Auth::id() ? 'user:'.Auth::id() : 'ip:'.request()->ip());

        $this->messages[] = ['role' => AiAssistantRoleEnum::User->value, 'text' => $message, 'suggestions' => []];
        $this->messages[] = ['role' => AiAssistantRoleEnum::Assistant->value, 'text' => $result['reply'], 'suggestions' => $result['suggestions']];
        $this->reset('message');
        unset($this->products);
    }

    public function clear(): void
    {
        $this->reset('message', 'messages');
        $this->resetValidation();
        unset($this->products);
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        $ids = array_merge(...array_map(fn (array $item): array => array_column($item['suggestions'], 'id'), $this->messages));

        return $ids === [] ? new Collection : $this->searchService->products($ids);
    }

    public function render(): View
    {
        return view('components.frontend.ai-assistant.index', [
            'userRole' => AiAssistantRoleEnum::User->value,
            'examples' => aiAssistantExamplePrompts(),
        ]);
    }
}
