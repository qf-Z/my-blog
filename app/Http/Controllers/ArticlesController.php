<?php

namespace App\Http\Controllers;

use App\Article;
use App\Category;
use Illuminate\Http\Request;

class ArticlesController extends Controller
{
    /**
     * @param Request $request
     * @return  \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $sort = $request->sort; // 分类名
        $query = Article::query();
        if ($sort) {
            $category = Category::where('name', $sort)->firstOrFail('id');
            $query->where('category_id', $category->id);
        } else {
            $query->whereNotIn('category_id', [Category::HIDDEN_CATEGORY]); // 不希望显示算法相关分类的记录
        }
        $articles = $query
                    ->latest()
                    ->orderBy('sorting', 'desc')
                    ->paginate(Article::PERPAGE);
        $categories = Category::all();
        $recent_articles = Article::query()->latest()->limit(Article::PERPAGE)->get();
        return view('index', compact('articles', 'categories', 'recent_articles'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function post(Request $request)
    {
        $tags = '';
        $slug = $request->slug;
        $article = Article::query()
                ->with('category', 'tags')
                ->when($slug, function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })->firstOrFail();

        $redis = app()->get('redis');
        $redis->pconnect('127.0.0.1', '6379');
        $redis->auth(config('database.redis.default.password'));
        if ($redis->isConnected()) {
            $redis->zIncrby('view_count', 1, 'article:' . $article->id); // 将文章的浏览量记录在 redis 的 zset 中
        }
        $article->content = \Parsedown::instance()->text($article->content);

        if (count($article->tags)) {
            $tags = $article->tags->implode('name', ',');
        }

        $categories = Category::all();
        $recent_articles = Article::latest()->limit(Article::PERPAGE)->get();
        return view('post', compact('article', 'categories', 'recent_articles', 'tags'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function archive()
    {
        $articles = Article::latest()->select(['title', 'slug', 'created_at'])->limit(Article::ARCHIVE_PAGE)->get();
        $categories = Category::all();
        $recent_articles = Article::latest()->limit(Article::PERPAGE)->get();
        return view('archive', compact('articles', 'categories', 'recent_articles'));
    }

    /**
     * 关于我
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function about()
    {
        $categories = Category::all();
        $recent_articles = Article::latest()->limit(Article::PERPAGE)->get();
        return view('about', compact('categories', 'recent_articles'));
    }

    /**
     * 书单
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function booklist()
    {
        $categories = Category::all();
        $recent_articles = Article::latest()->limit(Article::PERPAGE)->get();
        return view('booklist', compact('categories', 'recent_articles'));
    }
}
