<?php

namespace App\Interfaces;

interface FeedbackInterface
{
    public function index();
    public function store();
    public function destroy($id);
}
