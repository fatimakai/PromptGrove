<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CollectionAuditLogController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromptCollectionController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\PromptExportController;
use App\Http\Controllers\PromptReportController;
use App\Http\Controllers\PromptVersionController;
use App\Http\Controllers\TwoFactorAuthenticationController;
use App\Models\Prompt;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/prompts', [PromptController::class, 'index'])->name('prompts.index');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', function () {
        $topPrompts = Prompt::query()
            ->where('visibility', Prompt::VISIBILITY_PUBLIC)
            ->with(['user', 'tags'])
            ->withCount('upvotes')
            ->orderByDesc('upvotes_count')
            ->latest()
            ->limit(5)
            ->get();

        $topCreators = User::query()
            ->whereHas('prompts', fn ($query) => $query->where('visibility', Prompt::VISIBILITY_PUBLIC))
            ->withCount(['prompts' => fn ($query) => $query->where('visibility', Prompt::VISIBILITY_PUBLIC)])
            ->orderByDesc('prompts_count')
            ->limit(5)
            ->get();

        return view('dashboard', compact('topPrompts', 'topCreators'));
    })->name('dashboard');

    Route::get('/prompts/mine', [PromptController::class, 'mine'])->name('prompts.mine');
    Route::get('/prompts/bookmarked', [PromptController::class, 'bookmarked'])->name('prompts.bookmarked');
    Route::get('/prompts/create', [PromptController::class, 'create'])->name('prompts.create');
    Route::get('/prompts/{prompt:slug}/edit', [PromptController::class, 'edit'])->name('prompts.edit');
    Route::middleware('pro')->group(function (): void {
        Route::get('/prompts/{prompt:slug}/history', [PromptVersionController::class, 'index'])->name('prompts.history.index');
        Route::get('/prompts/{prompt:slug}/history/{version}', [PromptVersionController::class, 'show'])->name('prompts.history.show');
        Route::post('/prompts/{prompt:slug}/history/{version}/restore', [PromptVersionController::class, 'restore'])->name('prompts.history.restore');
    });
    Route::get('/exports/prompts.json', [PromptExportController::class, 'all'])->name('prompts.export.all');
    Route::post('/prompts/{prompt:slug}/reports', [PromptReportController::class, 'store'])->name('prompts.reports.store');

    Route::middleware('pro')->group(function (): void {
        Route::resource('collections', PromptCollectionController::class)->except('edit');
        Route::get('/collections/{collection:slug}/audit', [CollectionAuditLogController::class, 'index'])->name('collections.audit.index');
        Route::get('/collections/join/{token}', [PromptCollectionController::class, 'showInvite'])->name('collections.invites.show');
        Route::post('/collections/join/{token}', [PromptCollectionController::class, 'join'])->name('collections.join');
        Route::post('/collections/{collection:slug}/invite', [PromptCollectionController::class, 'invite'])->name('collections.invite');
        Route::delete('/collections/{collection:slug}/invite', [PromptCollectionController::class, 'revokeInvite'])->name('collections.invite.destroy');
        Route::patch('/collections/{collection:slug}/members/{user}', [PromptCollectionController::class, 'updateMember'])->name('collections.members.update');
        Route::delete('/collections/{collection:slug}/members/{user}', [PromptCollectionController::class, 'removeMember'])->name('collections.members.destroy');
        Route::delete('/collections/{collection:slug}/leave', [PromptCollectionController::class, 'leave'])->name('collections.leave');
        Route::post('/collections/{collection:slug}/prompts', [PromptCollectionController::class, 'addPrompt'])->name('collections.prompts.store');
        Route::delete('/collections/{collection:slug}/prompts/{prompt:slug}', [PromptCollectionController::class, 'removePrompt'])->name('collections.prompts.destroy');
    });

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/razorpay', [BillingController::class, 'razorpay'])->name('billing.razorpay');
    Route::post('/billing/razorpay/confirm', [BillingController::class, 'confirmRazorpay'])->name('billing.razorpay.confirm');
    Route::post('/billing/paypal', [BillingController::class, 'paypal'])->name('billing.paypal');
    Route::get('/billing/paypal/return', [BillingController::class, 'paypalReturn'])->name('billing.paypal.return');
    Route::post('/billing/stripe', [BillingController::class, 'stripe'])->name('billing.stripe');
    Route::get('/billing/stripe/return', [BillingController::class, 'stripeReturn'])->name('billing.stripe.return');
    Route::delete('/billing/subscriptions/{subscription}', [BillingController::class, 'cancel'])->name('billing.cancel');

    Route::middleware('permission:manage users')->group(function (): void {
        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::patch('/admin/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.role');
    });

    Route::middleware('permission:moderate prompts')->group(function (): void {
        Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
        Route::patch('/moderation/{report}/dismiss', [ModerationController::class, 'dismiss'])->name('moderation.dismiss');
        Route::patch('/moderation/{report}/hide', [ModerationController::class, 'hide'])->name('moderation.hide');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/two-factor', [TwoFactorAuthenticationController::class, 'store'])->name('two-factor.store');
    Route::post('/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/two-factor/recovery-codes', [TwoFactorAuthenticationController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');
    Route::delete('/two-factor', [TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.destroy');
});

Route::get('/prompts/{prompt:slug}', [PromptController::class, 'show'])->name('prompts.show');
Route::get('/prompts/{prompt:slug}/export', [PromptExportController::class, 'show'])->name('prompts.export');

require __DIR__.'/auth.php';
