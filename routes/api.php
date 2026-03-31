<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageLogController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\ScheduledMessageController;
use App\Http\Controllers\Api\SlaController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// ── Webhook (validated manually by bearer secret) ─────────────────────────────
Route::post('webhook/form', [WebhookController::class, 'handleForm'])->middleware('throttle:10,1');

// ── Sanctum-protected routes ──────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Students — order matters: specific paths before {id}
    Route::get('students/match',        [StudentController::class, 'match']);
    Route::get('students/search',       [StudentController::class, 'search']);
    Route::get('students/pipeline',     [StudentController::class, 'pipeline']);
    Route::post('students/{student}/link-phone', [StudentController::class, 'linkPhone']);
    Route::patch('students/{student}/stage',         [StudentController::class, 'updateStage']);
    Route::patch('students/{student}/exam',          [StudentController::class, 'updateExam']);
    Route::patch('students/{student}/payment',       [StudentController::class, 'updatePayment']);
    Route::patch('students/{student}/visa',          [StudentController::class, 'updateVisa']);
    Route::patch('students/{student}/priority',      [StudentController::class, 'updatePriority']);
    Route::patch('students/{student}/gift-received', [StudentController::class, 'markGiftReceived']);

    // Notes
    Route::post('notes',              [NoteController::class, 'store']);
    Route::get('notes/{student_id}',  [NoteController::class, 'index']);

    // Templates
    Route::get('templates', [TemplateController::class, 'index']);

    // Message logs
    Route::post('message-logs', [MessageLogController::class, 'store']);

    // Notifications
    Route::get('notifications',                       [NotificationController::class, 'index']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);

    // SLA settings
    Route::get('sla-settings', [SlaController::class, 'index']);

    // Scheduled messages
    Route::get('scheduled-messages/pending',                      [ScheduledMessageController::class, 'pending']);
    Route::patch('scheduled-messages/{scheduledMessage}/sent',    [ScheduledMessageController::class, 'markSent']);
    Route::get('students/{student_id}/scheduled-messages',        [ScheduledMessageController::class, 'forStudent']);
});
