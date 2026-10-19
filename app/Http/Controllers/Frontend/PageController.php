<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\Catalog\BookTypeEnum;
use App\Http\Controllers\Controller;
use App\Services\Frontend\Account\BookLibraryService;
use App\Services\Frontend\Shop\PageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly BookLibraryService $bookLibraryService,
    ) {}

    public function landing(Request $request): View
    {
        $slides = $this->pageService->getBannerSlides();
        $categories = $this->pageService->getFeaturedCategories();
        $featuredProducts = $this->pageService->getFeaturedProducts();
        $newArrivals = $this->pageService->getNewArrivals();
        $offer = $this->pageService->getActiveOffers()->first();
        $booksCount = $this->pageService->countProducts();
        $ownedIds = $this->bookLibraryService->ownedProductIds($request->user());
        $isPhysicalOnly = BookTypeEnum::current()->isPhysicalOnly();

        return view('frontend.landing.index', compact('slides', 'categories', 'featuredProducts', 'newArrivals', 'offer', 'booksCount', 'ownedIds', 'isPhysicalOnly'));
    }

    public function shop(): View
    {
        $isPhysicalOnly = BookTypeEnum::current()->isPhysicalOnly();

        return view('frontend.shop.index', compact('isPhysicalOnly'));
    }

    public function productDetail(Request $request, string $slug): View
    {
        $product = $this->pageService->getProductBySlug($slug);
        $relatedProducts = $this->pageService->getRelatedProducts($product);
        $ownsProduct = $this->bookLibraryService->ownsProduct($request->user(), $product->id);
        $offer = $this->pageService->getOfferFor($product);
        $categoryTrail = $this->pageService->getCategoryTrail($product->category_id);

        return view('frontend.shop.detail', compact('product', 'relatedProducts', 'ownsProduct', 'offer', 'categoryTrail'));
    }

    public function sitemap(): Response
    {
        $products = $this->pageService->getSitemapProducts();
        $pages = ['home', 'shop', 'faq', 'about', 'contact', 'terms', 'privacy', 'refund-policy'];

        return response()->view('frontend.sitemap', compact('products', 'pages'))->header('Content-Type', 'application/xml');
    }

    public function faq(): View
    {
        $faqs = $this->pageService->getFaqs();

        return view('frontend.faq.index', compact('faqs'));
    }

    public function about(): View
    {
        return view('frontend.about.index');
    }

    public function terms(): View
    {
        return view('frontend.legal.terms');
    }

    public function privacy(): View
    {
        return view('frontend.legal.privacy');
    }

    public function refundPolicy(): View
    {
        return view('frontend.legal.refund-policy');
    }

    public function contact(): View
    {
        $channels = $this->pageService->getContactChannels();

        return view('frontend.contact.index', compact('channels'));
    }

    public function assistant(): View
    {
        abort_unless(isAiAssistantEnabled(), 404);

        return view('frontend.ai-assistant.index');
    }
}
