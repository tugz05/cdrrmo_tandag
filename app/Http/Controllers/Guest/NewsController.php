<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function index($id) 
    {
        return Inertia::render('Guest/News/Index', [
            'post' => Post::findOrFail($id)
        ]);
    }
}
