<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Backoffice\Seo\SeoMetaService;
use App\Services\Frontend\Analytics\AnalyticsEventService;
use App\Services\Frontend\Shop\PageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly AnalyticsEventService $analyticsEventService,
        private readonly SeoMetaService $seoMetaService,
    ) {}

    public function landing(): View
    {
        $slides = $this->pageService->getBannerSlides();
        $categories = $this->pageService->getFeaturedCategories();
        $featuredProducts = $this->pageService->getFeaturedProducts();
        $newArrivals = $this->pageService->getNewArrivals();
        $offer = $this->pageService->getActiveOffers()->first();
        $productsCount = $this->pageService->countProducts();

        return view('index', compact('slides', 'categories', 'featuredProducts', 'newArrivals', 'offer', 'productsCount'));
    }

    public function shop(): View
    {
        return view('frontend.shop.index');
    }

    public function productDetail(string $slug): View
    {
        $product = $this->pageService->getProductBySlug($slug);
        $relatedProducts = $this->pageService->getRelatedProducts($product);
        $offer = $this->pageService->getOfferFor($product);
        $categoryTrail = $this->pageService->getCategoryTrail($product->category_id);
        $analyticsEvent = $this->analyticsEventService->viewItemEvent($product);
        $seoMeta = $this->seoMetaService->forPage($product);

        return view('frontend.shop.detail', compact('product', 'relatedProducts', 'offer', 'categoryTrail', 'analyticsEvent', 'seoMeta'));
    }

    public function sitemap(): Response
    {
        abort_unless($this->seoMetaService->isIndexingEnabled(), 404);

        $products = $this->pageService->getSitemapProducts();
        $pages = ['home', 'shop', 'faq', 'about', 'contact', 'terms', 'privacy', 'refund-policy'];

        return response()->view('frontend.sitemap', compact('products', 'pages'))->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        return response()->view('frontend.robots')->header('Content-Type', 'text/plain');
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
