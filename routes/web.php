<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AssignmentRuleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageSequenceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SalesConsultantController;
use App\Http\Controllers\Admin\SlaSettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('admin.dashboard'));

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Students
    Route::get('students',                               [StudentController::class, 'index'])->name('students.index');
    Route::get('students/{student}',                     [StudentController::class, 'show'])->name('students.show');
    Route::get('students/{student}/edit',                [StudentController::class, 'edit'])->name('students.edit');
    Route::put('students/{student}',                     [StudentController::class, 'update'])->name('students.update');
    Route::patch('students/{student}/reassign',          [StudentController::class, 'reassign'])->name('students.reassign');
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

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
