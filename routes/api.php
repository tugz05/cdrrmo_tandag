<?php

use App\Enums\PostTypeEnum;
use App\Http\Controllers\API\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\API\V1\Auth\GoogleAuthController;
use App\Http\Controllers\API\V1\Auth\LoginController;
use App\Http\Controllers\API\V1\Auth\NewPasswordController;
use App\Http\Controllers\API\V1\Auth\PasswordResetLinkController;
use App\Http\Controllers\API\V1\Auth\RegisterController;
use App\Http\Controllers\API\V1\Auth\VerificationCodeController;
use App\Http\Controllers\API\V1\Auth\VerificationController;
use App\Http\Controllers\API\V1\CallAvailabilityController;
use App\Http\Controllers\API\V1\ReportController;
use App\Http\Controllers\API\V1\SituationalIncidentReportController;
use App\Http\Controllers\API\V1\UserUploadController;
use App\Http\Controllers\API\V1\ValidImageController;
use App\Http\Controllers\CallerInfoController;
use App\Http\Controllers\StaffPresenceController;
use App\Http\Controllers\TwilioVoiceController;
use App\Http\Controllers\UserController;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [LoginController::class, 'store'])->name('api.signin')->middleware('throttle:8,1');
        Route::post('google', [GoogleAuthController::class, 'store'])->name('api.auth.google')->middleware('throttle:8,1');
        Route::post('register', [RegisterController::class, 'store'])->name('api.signup');
        // Route::post('verification-code/send', [VerificationController::class, 'store'])->name('api.verification-code.store');
        // Route::post('verification-code/verify', [VerificationController::class, 'verify'])->name('api.verify-code')->middleware('throttle:6,1');
        // Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1');
        Route::post('code/send', [VerificationCodeController::class, 'send'])->name('api.send-code')->middleware('throttle:6,1');
        Route::post('code/verify', [VerificationCodeController::class, 'verify'])->name('api.verify-code')->middleware('throttle:6,1');
        Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:6,1');
        Route::post('reset-password', [NewPasswordController::class, 'store']);

        // Route::middleware('auth:sanctum')->get('/twilio/token', [TwilioController::class, 'generateToken']);
    });

    Route::post('valid-images/{user}', [ValidImageController::class, 'store'])->name('api.valid-images');

    Route::post('/upload', [UserUploadController::class, 'upload']);

    $publishedPostsWithImage = fn (string $type) => Post::query()
        ->whereType($type)
        ->whereIsPublished(true)
        ->whereNotNull('bg_image')
        ->where('bg_image', '!=', '')
        ->latest();

    Route::get('/news', fn () => PostResource::collection($publishedPostsWithImage(PostTypeEnum::NEWS)->get()));

    Route::get('/safety-tips', fn () => PostResource::collection($publishedPostsWithImage(PostTypeEnum::SAFETY_TIPS)->get()));

    Route::get('/emergency-preparedness', fn () => PostResource::collection($publishedPostsWithImage(PostTypeEnum::EMERGENCY_PREPAREDNESS)->get()));

    Route::get('/valid_ids/{path}', function ($path) {
        return Storage::disk('public')->response('img_valid_ids/'.$path);
    });

    Route::post('/caller-details/set-location', [CallerInfoController::class, 'setLocation'])
        ->name('set-caller-location.store');

    Route::get('/call/availability', [CallAvailabilityController::class, 'show'])
        ->middleware('throttle:120,1');

    Route::post('/call/started', [CallerInfoController::class, 'callStarted']);
    Route::post('/call/ended', [CallerInfoController::class, 'callEnded']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'updatePassword']);
        Route::get('report-history/all', [ReportController::class, 'historyAll']);
        Route::get('report-history/{userId}', [ReportController::class, 'history'])->whereNumber('userId');
        Route::get('reports/{report}', [ReportController::class, 'show']);
        Route::post('/report', [ReportController::class, 'store']);

        Route::get('situational-incident-reports/history/{userId}', [SituationalIncidentReportController::class, 'history'])
            ->whereNumber('userId');
        Route::post('situational-incident-reports', [SituationalIncidentReportController::class, 'store']);
        Route::get('situational-incident-reports/{situational_incident_report}', [SituationalIncidentReportController::class, 'show']);
        Route::put('situational-incident-reports/{situational_incident_report}', [SituationalIncidentReportController::class, 'update']);
        Route::delete('situational-incident-reports/{situational_incident_report}', [SituationalIncidentReportController::class, 'destroy']);

        Route::get('/voice/token', [TwilioVoiceController::class, 'tokenForMobile'])
            ->middleware('throttle:60,1');

        /** Same presence as POST /admin/staff/heartbeat — use from Flutter dispatch so availability API can return 200 without the web dashboard. */
        Route::post('/staff/heartbeat', [StaffPresenceController::class, 'heartbeat'])
            ->middleware('throttle:120,1');
    });

    // Route::get('/twilio/token', [TwilioVoiceController::class, 'token']);
    // Route::post('/twilio/voice', [TwilioVoiceController::class, 'handleVoice']);
});
