<?php

namespace App\Livewire\Frontend\Reader;

use App\Enums\Catalog\BookTypeEnum;
use App\Services\Frontend\Account\BookReaderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    public ?int $bookId = null;

    public string $bookTitle = '';

    public string $documentId = '';

    public string $viewerKey = '';

    public bool $isPreview = false;

    public bool $canDownload = false;

    public bool $showReader = false;

    public bool $showDownloadWarning = false;

    protected BookReaderService $bookReaderService;

    public function boot(BookReaderService $bookReaderService): void
    {
        $this->bookReaderService = $bookReaderService;
    }

    #[On('open-book')]
    public function openBook(int $bookId): void
    {
        try {
            $book = $this->bookReaderService->open(Auth::user(), $bookId);
        } catch (ValidationException $exception) {
            $this->dispatch('toast-show', slots: ['text' => $exception->validator->errors()->first('book')], dataset: ['variant' => 'danger']);

            return;
        }

        $this->bookId = $bookId;
        $this->bookTitle = $book['title'];
        $this->documentId = $book['documentId'];
        $this->isPreview = $book['isPreview'];
        $this->canDownload = $book['canDownload'];
        $this->viewerKey = $bookId.'-'.Str::random(12);
        $this->showReader = true;
        $this->dispatch('pdf-viewer-open');
    }

    public function updatedShowReader(bool $showReader): void
    {
        if (! $showReader) {
            $this->reset();
        }
    }

    #[Computed]
    public function isPhysicalOnly(): bool
    {
        return BookTypeEnum::current()->isPhysicalOnly();
    }

    #[Computed]
    public function downloadUrl(): ?string
    {
        return $this->bookId && $this->canDownload ? route('user.books.download', $this->bookId) : null;
    }

    public function render(): View
    {
        return view('components.frontend.reader.index');
    }
}
