<?php

use App\Helpers\JHelper;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\CallerInfoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SafetyTipsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffPresenceController;
use App\Http\Controllers\TwilioVoiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class);

require __DIR__.'/web_routes/web_guest.php';

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->middleware(['auth', 'role:admin|super_admin'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::put('users/verification-status/{id}', [UserController::class, 'verify'])->name('users.verify');
    // Route::get('users/show/{user}', [UserController::class, 'show'])->name('users.show');
    Route::resource('users', UserController::class)->only(['index', 'show', 'destroy']);
    Route::put('reports/update-status', [ReportController::class, 'updateStatus'])->name('reports.update-status');
    Route::get('/reports/{type}/{status?}', [ReportController::class, 'index'])->name('reports.index');
    Route::resource('reports', ReportController::class)->only(['store', 'destroy']);
    // Route::resource('reports', ReportController::class)->only(['index']);
    Route::get('posts/{type?}', [PostController::class, 'index'])->name('posts.index');
    Route::put('posts/publish/{id}', [PostController::class, 'publish'])->name('posts.publish');
    Route::put('posts/update-title', [PostController::class, 'updateTitle'])->name('posts.update-title');
    Route::put('posts/update-type', [PostController::class, 'updateType'])->name('posts.update-type');
    Route::resource('posts', PostController::class)->only(['create', 'edit', 'store', 'destroy']);
    // Route::resource('safety-tips', SafetyTipsController::class)->only(['index','store','destroy']);
    Route::resource('settings', SettingsController::class)->only(['index']);

    Route::get('report-images/{reportId}', function ($reportId) {
        return response()->json(JHelper::getReportImages($reportId));
    })->name('report.images');

    Route::post('staff/heartbeat', [StaffPresenceController::class, 'heartbeat'])->name('admin.staff.heartbeat');
    Route::post('staff/call-answered', [StaffPresenceController::class, 'callAnswered'])->name('admin.staff.call-answered');
    Route::post('staff/call-finished', [StaffPresenceController::class, 'callFinished'])->name('admin.staff.call-finished');

    // Route::get('call', function () {
    //     return view('admin_call');
    // });
    // Route::post('/twilio/voice', function (Request $request) {
    //     $response = new \Twilio\TwiML\VoiceResponse();
    //     $dial = $response->dial();
    //     $dial->client(env('ADMIN_IDENTITY')); // Admin will receive the call
    //     return response($response)->header('Content-Type', 'text/xml');
    // });

});

Route::prefix('admin')->middleware(['auth', 'role:super_admin'])->group(function () {
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::resource('administrators', AdministratorController::class)->only(['index', 'store', 'update', 'destroy']);
});

Route::get('/caller-details/{callerId}', [CallerInfoController::class, 'index'])
    ->name('caller-info.index');

Route::get('/caller', function () {
    return view('caller');
});

Route::get('/receiver', function () {
    return view('receiver');
});

Route::get('/twilio/token', [TwilioVoiceController::class, 'generateToken']);
Route::get('/twilio/health', [TwilioVoiceController::class, 'health']);
Route::match(['get', 'post'], '/twilio/voice', [TwilioVoiceController::class, 'handleVoice'])
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::match(['get', 'post'], '/twilio/voice/dial-status', [TwilioVoiceController::class, 'dialStatus'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

if (app()->environment('local')) {
    Route::get('/twilio/token-debug', [TwilioVoiceController::class, 'tokenDebug']);
}

require __DIR__.'/auth.php';
