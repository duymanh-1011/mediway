<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home() { return view('public.home', ['news' => config('portal.news')]); }
    public function about() { return view('public.about'); }
    public function careProcess() { return view('public.care-process'); }
    public function support() { return view('public.support'); }
    public function news(Request $request)
    {
        $allNews = collect(config('portal.news'));
        $categories = $allNews->pluck('category')->unique()->values();
        $category = $request->query('danh-muc');
        $category = is_string($category) && $categories->contains($category) ? $category : null;
        $news = $category ? $allNews->where('category', $category)->values() : $allNews;

        return view('public.news.index', compact('news', 'categories', 'category'));
    }
    public function article(string $slug)
    {
        $article = collect(config('portal.news'))->firstWhere('slug', $slug);
        abort_unless($article, 404);
        return view('public.news.show', compact('article'));
    }
}
