<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TurmaController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Alunos e Turmas
    Route::resource('students', StudentController::class)->except(['show']);
    Route::resource('turmas', TurmaController::class)->except(['show']);

    // Upload e geração PEI/PAEE
    Route::get('/students/{student}/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/students/{student}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('/documents/{document}/approve', [DocumentController::class, 'approve'])->name('documents.approve');
    Route::post('/documents/{document}/share-email', [DocumentController::class, 'shareEmail'])->name('documents.share-email');
    Route::post('/documents/{document}/share-whatsapp', [DocumentController::class, 'shareWhatsapp'])->name('documents.share-whatsapp');

    // Histórico
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

    // Cadastros (apenas direção)
    Route::middleware('direcao')->group(function () {
        Route::get('/school/edit', [SchoolController::class, 'edit'])->name('schools.edit');
        Route::put('/school', [SchoolController::class, 'update'])->name('schools.update');
        Route::resource('users', UserManagementController::class)->except(['show']);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
