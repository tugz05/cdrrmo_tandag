<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsStoreRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private PostService $postService) {}

    public function index($type = null)
    {
        return inertia('Posts/Index', $this->postService->index($type));
    }

    public function create()
    {
        return inertia(
            'Posts/Create',
            $this->postService->create()
        );
    }

    public function edit(Post $post)
    {
        return inertia(
            'Posts/Edit',
            $this->postService->edit($post)
        );
    }

    public function store(NewsStoreRequest $request)
    {
        return $this->postService->store($request);
    }

    public function updateTitle(Request $request)
    {
        $this->postService->updateTitle($request->validate([
            'id' => 'required',
            'title' => 'required|string|min:2|max:200',
        ]));

        return redirect()->back();
    }

    public function updateType(Request $request)
    {
        $this->postService->updateType($request->validate([
            'id' => 'required',
            'type' => 'required|string',
        ]));
    }

    public function updateStatus($id)
    {
        $this->postService->updateStatus($id);
    }

    public function destroy($id)
    {
        $this->postService->destroy($id);
    }

    public function restore($id)
    {
        $this->postService->restore($id);
    }

    public function publish($id)
    {
        $this->postService->publish($id);

        return redirect()->back();
    }
}
