<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LeadConversionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('leads/{lead}/convert', [LeadConversionController::class, 'show'])->name('leads.convert.show');
    Route::post('leads/{lead}/convert', [LeadConversionController::class, 'store'])->name('leads.convert.store');
    Route::resource('leads', LeadController::class);
    Route::resource('companies', CompanyController::class);
    Route::post('companies/{company}/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::get('contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::get('contacts/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
    Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::post('activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::get('pipeline', [PipelineController::class, 'index'])->name('pipeline.index');
    Route::patch('deals/{deal}/stage', [DealController::class, 'changeStage'])->name('deals.stage');
    Route::resource('deals', DealController::class);
    Route::get('tasks/reminders', [TaskController::class, 'reminders'])->name('tasks.reminders');
    Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('tasks/{task}/reopen', [TaskController::class, 'reopen'])->name('tasks.reopen');
    Route::resource('tasks', TaskController::class);
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/conversion', [ReportController::class, 'conversion'])->name('reports.conversion');
    Route::get('reports/conversion/export', [ReportController::class, 'exportConversion'])->name('reports.conversion.export');
    Route::get('reports/pipeline', [ReportController::class, 'pipeline'])->name('reports.pipeline');
    Route::get('reports/pipeline/export', [ReportController::class, 'exportPipeline'])->name('reports.pipeline.export');
    Route::get('reports/team', [ReportController::class, 'team'])->name('reports.team');
    Route::get('reports/team/export', [ReportController::class, 'exportTeam'])->name('reports.team.export');
    Route::get('activities/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
