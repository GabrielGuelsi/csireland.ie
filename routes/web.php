<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AlertRuleController;
use App\Http\Controllers\Admin\Applications\ApplicationPipelineController;
use App\Http\Controllers\Admin\Applications\ApplicationStudentController;
use App\Http\Controllers\Admin\Applications\DispatchController;
use App\Http\Controllers\Admin\Applications\StudentChatController;
use App\Http\Controllers\Admin\AssignmentRuleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DuplicateController;
use App\Http\Controllers\Admin\MessageSequenceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SalesConsultantController;
use App\Http\Controllers\Admin\SlaSettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    // Applications subdomain → land on dispatch inbox; otherwise dashboard
    if ($request->getHost() === 'app.ciireland.ie' && Auth::check()) {
        return redirect()->route('admin.applications.dispatch.index');
    }
    return redirect()->route('admin.dashboard');
});
Route::get('/privacy', fn() => view('privacy'))->name('privacy');

Route::middleware(['auth', 'admin_or_application'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Duplicates
    Route::get('duplicates',        [DuplicateController::class, 'index'])->name('duplicates.index');
    Route::post('duplicates/merge', [DuplicateController::class, 'merge'])->name('duplicates.merge');

    // Students
    Route::get('students',                               [StudentController::class, 'index'])->name('students.index');
    Route::get('students/create',                        [StudentController::class, 'create'])->name('students.create');
    Route::post('students',                              [StudentController::class, 'store'])->name('students.store');
    Route::get('students/{student}',                     [StudentController::class, 'show'])->name('students.show');
    Route::get('students/{student}/edit',                [StudentController::class, 'edit'])->name('students.edit');
    Route::match(['PUT','PATCH'], 'students/{student}',  [StudentController::class, 'update'])->name('students.update');
    Route::patch('students/{student}/reassign',          [StudentController::class, 'reassign'])->name('students.reassign');
    Route::post('students/bulk-reassign',                [StudentController::class, 'bulkReassign'])->name('students.bulkReassign');
    Route::patch('students/{student}/gift-received',     [StudentController::class, 'markGiftReceived'])->name('students.markGiftReceived');

    // CS Agents
    Route::resource('agents', AgentController::class)->names('agents')->except(['show']);

    // Sales Consultants
    Route::get('sales-consultants',                           [SalesConsultantController::class, 'index'])->name('sales-consultants.index');
    Route::post('sales-consultants',                          [SalesConsultantController::class, 'store'])->name('sales-consultants.store');
    Route::patch('sales-consultants/{salesConsultant}',       [SalesConsultantController::class, 'update'])->name('sales-consultants.update');
    Route::delete('sales-consultants/{salesConsultant}',      [SalesConsultantController::class, 'destroy'])->name('sales-consultants.destroy');

    // Assignment rules
    Route::resource('assignment-rules', AssignmentRuleController::class)
         ->names('assignment-rules')
         ->except(['show', 'create', 'edit']);

    // SLA settings
    Route::get('sla-settings', [SlaSettingController::class, 'index'])->name('sla-settings.index');
    Route::put('sla-settings', [SlaSettingController::class, 'update'])->name('sla-settings.update');

    // Templates
    Route::resource('templates', TemplateController::class)->names('templates');
    Route::patch('templates/{template}/toggle', [TemplateController::class, 'toggle'])->name('templates.toggle');

    // Message Sequences
    Route::resource('message-sequences', MessageSequenceController::class)->names('message-sequences')->except(['show']);

    // Alert Rules
    Route::resource('alert-rules', AlertRuleController::class)->names('alert-rules');
    Route::patch('alert-rules/{alertRule}/toggle', [AlertRuleController::class, 'toggle'])->name('alert-rules.toggle');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    // Applications team
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('dispatch',                 [DispatchController::class, 'index'])->name('dispatch.index');
        Route::post('dispatch/{student}/accept', [DispatchController::class, 'accept'])->name('dispatch.accept');
        Route::get('pipeline',                 [ApplicationPipelineController::class, 'index'])->name('pipeline.index');
        Route::get('students/{student}',       [ApplicationStudentController::class, 'show'])->name('students.show');
        Route::match(['PUT','PATCH'], 'students/{student}', [ApplicationStudentController::class, 'update'])->name('students.update');
    });

    // Student chat (shared between CS admins and Applications team)
    Route::get('students/{student}/chat',  [StudentChatController::class, 'index'])->name('students.chat.index');
    Route::post('students/{student}/chat', [StudentChatController::class, 'store'])->name('students.chat.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
