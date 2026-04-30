<?php

namespace App\Interfaces;
use App\Interfaces\Xtends\RestorableInterface;

interface AdministratorInterface extends RestorableInterface
{
    public function index();
    public function store($validatedData);
    public function destroy($id);
    public function resetPassword($userId, $password);
}
