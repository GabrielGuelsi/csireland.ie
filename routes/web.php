<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AlertRuleController;
use App\Http\Controllers\Admin\Applications\ApplicationPipelineController;
use App\Http\Controllers\Admin\Applications\ApplicationStudentController;
use App\Http\Controllers\Admin\Applications\DispatchController;
use App\Http\Controllers\Admin\Applications\ServiceRequestAttachmentController as AppAttachmentController;
use App\Http\Controllers\Admin\Applications\ServiceRequestController as AppServiceRequestController;
use App\Http\Controllers\Admin\Applications\StudentChatController;
use App\Http\Controllers\Admin\AssignmentRuleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DuplicateController;
use App\Http\Controllers\Admin\InfluencerController;
use App\Http\Controllers\Admin\MessageSequenceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SalesConsultantController;
use App\Http\Controllers\Admin\SalesPartialController;
use App\Http\Controllers\Admin\SalesPeriodGoalController;
use App\Http\Controllers\Admin\SlaSettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\My\DashboardController as MyDashboardController;
use App\Http\Controllers\My\ServiceRequestController as MyServiceRequestController;
use App\Http\Controllers\My\NotificationController as MyNotificationController;
use App\Http\Controllers\My\StudentController as MyStudentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    // Applications subdomain → land on dispatch inbox for logged-in non-CS users
    if ($request->getHost() === 'app.ciireland.ie' && Auth::check() && !Auth::user()->isCsAgent()) {
        return redirect()->route('admin.applications.dispatch.index');
    }
    // Role-based landing
    if (Auth::check()) {
        return redirect(Auth::user()->defaultRoute());
    }
    return redirect()->route('login');
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

    // Sales Management — goals + partials
    Route::resource('sales-period-goals', SalesPeriodGoalController::class)
        ->names('sales-period-goals')
        ->except(['show']);

    Route::resource('partials', SalesPartialController::class)
        ->names('partials')
        ->except(['edit', 'update']);

    // Influencers (Sales Management)
    Route::resource('influencers', InfluencerController::class)->names('influencers')->except(['show', 'create', 'edit']);

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    // Applications team
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('dispatch',                 [DispatchController::class, 'index'])->name('dispatch.index');
        Route::post('dispatch/{student}/accept', [DispatchController::class, 'accept'])->name('dispatch.accept');
        Route::get('pipeline',                 [ApplicationPipelineController::class, 'index'])->name('pipeline.index');
        Route::get('service-requests/documentation',            [AppServiceRequestController::class, 'documentation'])->name('service-requests.documentation');
        Route::get('service-requests/refunds',                [AppServiceRequestController::class, 'refunds'])->name('service-requests.refunds');
        Route::get('service-requests/cancellations',          [AppServiceRequestController::class, 'cancellations'])->name('service-requests.cancellations');
        Route::get('service-requests/{serviceRequest}',       [AppServiceRequestController::class, 'show'])->name('service-requests.show');
        Route::patch('service-requests/{serviceRequest}',     [AppServiceRequestController::class, 'update'])->name('service-requests.update');
        Route::get('service-requests/attachments/{attachment}/download', [AppAttachmentController::class, 'download'])->name('service-requests.attachments.download');
        Route::get('service-requests/attachments/{attachment}/view',     [AppAttachmentController::class, 'view'])->name('service-requests.attachments.view');
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

// CS agent portal — own students only (admins allowed for preview)
Route::middleware(['auth', 'cs_agent'])->prefix('my')->name('my.')->group(function () {
    Route::get('dashboard', [MyDashboardController::class, 'index'])->name('dashboard');

    // Own students
    Route::get('students',                      [MyStudentController::class, 'index'])->name('students.index');
    Route::get('students/{student}',            [MyStudentController::class, 'show'])->name('students.show');
    Route::patch('students/{student}/stage',    [MyStudentController::class, 'updateStage'])->name('students.stage');
    Route::patch('students/{student}/priority', [MyStudentController::class, 'updatePriority'])->name('students.priority');
    Route::patch('students/{student}/exam',     [MyStudentController::class, 'updateExam'])->name('students.exam');
    Route::patch('students/{student}/payment',  [MyStudentController::class, 'updatePayment'])->name('students.payment');
    Route::patch('students/{student}/visa',     [MyStudentController::class, 'updateVisa'])->name('students.visa');
    Route::patch('students/{student}/gift-received', [MyStudentController::class, 'markGiftReceived'])->name('students.giftReceived');
    Route::patch('students/{student}/followup', [MyStudentController::class, 'updateFollowup'])->name('students.followup');
    Route::post('students/{student}/notes',     [MyStudentController::class, 'addNote'])->name('students.notes.store');
    Route::post('students/{student}/service-requests', [MyServiceRequestController::class, 'store'])->name('students.serviceRequests.store');
    Route::patch('scheduled-messages/{scheduledMessage}/sent', [MyStudentController::class, 'markScheduledSent'])->name('scheduledMessages.sent');

    // Notifications
    Route::get('notifications',                [MyNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [MyNotificationController::class, 'markRead'])->name('notifications.read');
});

require __DIR__.'/auth.php';
