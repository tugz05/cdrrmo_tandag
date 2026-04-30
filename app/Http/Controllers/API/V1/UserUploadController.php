<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\UserUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserUploadController extends Controller
{
    public function upload(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'id_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'face_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Handle the uploaded files
        $idImage = $request->file('id_image');
        $faceImage = $request->file('face_image');

        $idImageName = time() . '_id.' . $idImage->getClientOriginalExtension();
        $faceImageName = time() . '_face.' . $faceImage->getClientOriginalExtension();

        // Store images in the public storage
        $idImagePath = $idImage->storeAs('ids', $idImageName, 'public');
        $faceImagePath = $faceImage->storeAs('faces', $faceImageName, 'public');

        // Store the file paths in the database
        $userUpload = UserUpload::create([
            'user_id' => $request->user_id,
            'id_image' => 'storage/' . $idImagePath, // Updated path
            'face_image' => 'storage/' . $faceImagePath, // Updated path
        ]);

        return response()->json(['success' => 'Images uploaded successfully', 'data' => $userUpload], 200);
    }

    public function retrieve($id)
    {
        $upload = UserUpload::find($id);
        if (!$upload) {
            return response()->json(['error' => 'Upload not found'], 404);
        }

        return response()->json($upload, 200);
    }
}
