<?php

namespace App\Interfaces;
use App\Interfaces\Xtends\RestorableInterface;

interface TipsInterface extends RestorableInterface
{
    public function index();
    public function store($validatedData);
    public function shouldDisable($id);
    public function destroy($id);
}
