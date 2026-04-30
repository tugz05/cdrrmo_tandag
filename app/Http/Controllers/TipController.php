<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipStoreRequest;
use App\Interfaces\TipsInterface;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function __construct(private TipsInterface $tip)
    {}

    public function index()
    {
        return inertia('', $this->tip->index());
    }

    public function store(TipStoreRequest $request)
    {
        $this->tip->store($request->validated());
    }

    public function shouldDisable($id)
    {
        $this->tip->shouldDisable($id);
    }

    public function destory($id)
    {
        $this->tip->destroy($id);   
    }

    public function restore($id)
    {
        $this->tip->restore($id);   
    } 
}
