<?php 

namespace App\Helpers;

use App\Models\ReportImage;
use App\Models\User;
use App\Models\UserValidId;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

abstract class JHelper {
    public static function generateCode(
        $prefix = '', 
        $characters = '0123456789', 
        $length = 6
    ) {
        $generatedCharacters = '';
    
        for ($i = 0; $i < $length; $i++) {
            $generatedCharacters .= $characters[rand(0, strlen($characters) - 1)];
        }

        $code = $prefix . $generatedCharacters;
    
        return $code;
    }


    public static function storeValidIds(Request $request, User $user)
    {
        $validIds = [];
        
        if ($request->hasFile('img_valid_id')) 
            $validIds[] = $request->file('img_valid_id')->store('img_valid_ids', 'public');
        if ($request->hasFile('img_selfie')) 
            $validIds[] = $request->file('img_selfie')->store('img_valid_ids', 'public');

        foreach($validIds as $id) {
            UserValidId::create([
                'user_id' => $user->id,
                'filename' => $id
            ]);
        }
    }

    public static function getValidImages($userId) 
    {
        $images = [];
        foreach (UserValidId::whereUserId($userId)->get() as $image)
            $images[] = Storage::disk('public')->url($image->filename);

        return $images;    
    }

    public static function getReportImages($reportId) 
    {
        $images = [];
        foreach (ReportImage::whereReportId($reportId)->get() as $image)
            $images[] = Storage::disk('public')->url($image->filename);

        return $images;    
    }
}