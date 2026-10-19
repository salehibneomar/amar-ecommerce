<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\Backoffice\Catalog\BookService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    public function __construct(private readonly BookService $bookService) {}

    public function index(): View
    {
        return view('backoffice.books.index');
    }

    public function file(Request $request, Book $book): Response
    {
        return $this->bookService->fileResponse($book, $request->boolean('download'));
    }
}
