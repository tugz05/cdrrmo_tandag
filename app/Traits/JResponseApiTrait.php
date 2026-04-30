<?php
namespace App\Traits;

use App\Enums\JStatusCode;
use Symfony\Component\HttpFoundation\JsonResponse;

trait JResponseApiTrait 
{
    protected function responseOK(
        $data, 
        $message = 'Data loaded successfully.',
        $code = JsonResponse::HTTP_OK,
    ): JsonResponse
    {
    	$response = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        return response()->json($response);
    }

    protected function responseError(
        $message = 'An error has occured. Please try again.', 
        $errorMessages = [], 
        $code = JStatusCode::NOT_FOUND
    ): JsonResponse
    {
    	$response = [
            'success' => false,
            'message' => $message,
        ];

        if(!empty($errorMessages))
            $response['errors'] = $errorMessages;

        return response()->json($response, $code);
    }
}