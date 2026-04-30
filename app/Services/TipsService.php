<?php

namespace App\Services;

use App\Interfaces\TipsInterface;
use App\Models\Tip;

class TipsService implements TipsInterface
{
    public function index()
    {
        return [
            'tips' => Tip::all()
        ];
    }

    public function store($validatedData)
    {
        Tip::updateOrCreate([
            'id' => $validatedData['id']
        ], $validatedData);
    }

    public function shouldDisable($id)
    {
        $tip = Tip::find($id, ['id', 'disabled_at']);
        $disabled_at = is_null($tip->disabled_at) ? now() : null;
        return $tip->update(['disabled_at' => $disabled_at]);
    }

    public function destroy($id)
    {
        Tip::destory($id);
    }
    public function restore($id)
    {
        Tip::withTrashed()->find($id)->restore();
    }
}
