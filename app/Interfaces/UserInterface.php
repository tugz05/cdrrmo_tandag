<?php

namespace App\Interfaces;
use App\Interfaces\Xtends\RestorableInterface;

interface UserInterface extends RestorableInterface
{
    public function index();
    public function confirm($id);
    public function destroy($id);
}
