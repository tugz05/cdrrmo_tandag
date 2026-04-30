<?php 
use App\Http\Controllers\Guest\EmergencyPreparednessController;
use App\Http\Controllers\Guest\NewsController;
use App\Http\Controllers\Guest\SafetyTipsController;




Route::get('news/{id}', [NewsController::class, 'index'])->name('guest.news.index');
Route::get('safety-tips/{id}', [SafetyTipsController::class, 'index'])->name('guest.safety-tips.index');
Route::get('emergency-preparedness/{id}', [EmergencyPreparednessController::class, 'index'])->name('guest.emergency-preparedness.index');
