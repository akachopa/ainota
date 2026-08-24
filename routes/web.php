<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PlatformAdminController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceToolsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/u/{token}', [PublicController::class, 'uploadLink'])->name('upload-links.public');
Route::post('/u/{token}', [PublicController::class, 'storeUploadLink'])
    ->middleware('throttle:uploads')
    ->name('upload-links.public.store');

Route::get('/invitations/{token}', [PublicController::class, 'invitation'])->name('invitations.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/invitations/{token}', [PublicController::class, 'acceptInvitation'])->name('invitations.accept');

    Route::get('/dashboard', [DashboardController::class, 'account'])->name('dashboard');
    Route::get('/workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');

    Route::get('/platform', [PlatformAdminController::class, 'index'])->name('platform.index');

    Route::prefix('w/{workspace}')->middleware('workspace')->group(function () {
        Route::get('/', [DashboardController::class, 'workspace'])->name('workspaces.dashboard');
        Route::get('/settings', [WorkspaceController::class, 'settings'])->name('workspaces.settings');
        Route::patch('/settings', [WorkspaceController::class, 'update'])->name('workspaces.update');

        Route::get('/documents', [DocumentController::class, 'index'])->name('workspaces.documents.index');
        Route::post('/documents/batch', [DocumentController::class, 'createBatch'])->name('workspaces.documents.batch');
        Route::post('/documents', [DocumentController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('workspaces.documents.store');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('workspaces.documents.show');
        Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('workspaces.documents.preview');
        Route::post('/documents/{document}/reprocess', [DocumentController::class, 'reprocess'])->name('workspaces.documents.reprocess');
        Route::post('/documents/{document}/duplicate', [DocumentController::class, 'resolveDuplicate'])->name('workspaces.documents.duplicate');

        Route::get('/review', [WorkflowController::class, 'reviewQueue'])->name('workspaces.review');
        Route::get('/review/{transaction}', [WorkflowController::class, 'reviewShow'])->name('workspaces.review.show');
        Route::post('/review/{transaction}', [WorkflowController::class, 'reviewStore'])->name('workspaces.review.store');

        Route::get('/approval', [WorkflowController::class, 'approvalQueue'])->name('workspaces.approval');
        Route::post('/transactions/{transaction}/approve', [WorkflowController::class, 'approve'])->name('workspaces.transactions.approve');
        Route::post('/transactions/{transaction}/reject', [WorkflowController::class, 'reject'])->name('workspaces.transactions.reject');

        Route::get('/transactions', [WorkspaceToolsController::class, 'transactions'])->name('workspaces.transactions.index');

        Route::get('/accounts', [MasterDataController::class, 'accounts'])->name('workspaces.accounts.index');
        Route::post('/accounts', [MasterDataController::class, 'storeAccount'])->name('workspaces.accounts.store');
        Route::patch('/accounts/{account}', [MasterDataController::class, 'updateAccount'])->name('workspaces.accounts.update');
        Route::post('/accounts/import', [MasterDataController::class, 'importAccounts'])->name('workspaces.accounts.import');

        Route::get('/vendors', [MasterDataController::class, 'vendors'])->name('workspaces.vendors.index');
        Route::post('/vendors', [MasterDataController::class, 'storeVendor'])->name('workspaces.vendors.store');

        Route::get('/payments', [WorkspaceToolsController::class, 'paymentMappings'])->name('workspaces.payments.index');
        Route::post('/payments', [WorkspaceToolsController::class, 'storePaymentMapping'])->name('workspaces.payments.store');

        Route::get('/members', [MasterDataController::class, 'members'])->name('workspaces.members.index');
        Route::post('/members/invite', [MasterDataController::class, 'invite'])->name('workspaces.members.invite');
        Route::patch('/members/{member}', [MasterDataController::class, 'updateMember'])->name('workspaces.members.update');
        Route::delete('/members/{member}', [MasterDataController::class, 'removeMember'])->name('workspaces.members.destroy');

        Route::get('/exports', [WorkspaceToolsController::class, 'exportIndex'])->name('workspaces.exports.index');
        Route::post('/exports', [WorkspaceToolsController::class, 'storeExport'])->name('workspaces.exports.store');
        Route::get('/exports/{export}/download', [WorkspaceToolsController::class, 'downloadExport'])->name('workspaces.exports.download');

        Route::get('/upload-links', [WorkspaceToolsController::class, 'uploadLinks'])->name('workspaces.upload-links.index');
        Route::post('/upload-links', [WorkspaceToolsController::class, 'storeUploadLink'])->name('workspaces.upload-links.store');

        Route::get('/usage', [WorkspaceToolsController::class, 'usageIndex'])->name('workspaces.usage');
    });
});

require __DIR__.'/settings.php';
