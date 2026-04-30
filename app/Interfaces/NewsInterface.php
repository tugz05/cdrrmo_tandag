<?php

namespace App\Interfaces;

use App\Interfaces\Xtends\RestorableInterface;
use Illuminate\Http\Request;

interface NewsInterface extends RestorableInterface
{
    public function index();

    public function store(Request $request);

    public function updateStatus($id);

    public function destroy($id);
}
