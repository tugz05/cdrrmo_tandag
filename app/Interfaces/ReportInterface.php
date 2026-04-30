<?php

namespace App\Interfaces;
use App\Interfaces\Xtends\RestorableInterface;

interface ReportInterface extends RestorableInterface
{
    public function index();
    public function store();
    public function updateStatus($id, $status);
    public function destroy($id);
}