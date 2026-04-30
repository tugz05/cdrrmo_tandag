<?php

namespace App\Interfaces;

interface UserLogInterface
{
    public function index();
    public function store();
    public function destroy($id);
}
