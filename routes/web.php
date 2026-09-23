<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignInfluencerController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliverableController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InfluencerController;
use App\Http\Controllers\InfluencerLoginController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ClientLoginController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\ShortlistController;
use App\Http\Controllers\InfluencerPortal\CampaignController as InfluencerPortalCampaignController;
use App\Http\Controllers\InfluencerPortal\DashboardController as InfluencerPortalDashboardController;
use App\Http\Controllers\InfluencerPortal\DeliverableController as InfluencerPortalDeliverableController;
use App\Http\Controllers\InfluencerPortal\PaymentController as InfluencerPortalPaymentController;
use App\Http\Controllers\InfluencerPortal\ProfileController as InfluencerPortalProfileController;
use App\Http\Controllers\ClientPortal\ApprovalController as ClientPortalApprovalController;
use App\Http\Controllers\ClientPortal\CampaignController as ClientPortalCampaignController;
use App\Http\Controllers\ClientPortal\DashboardController as ClientPortalDashboardController;
use App\Http\Controllers\ClientPortal\NotificationController as ClientPortalNotificationController;
use App\Http\Controllers\ClientPortal\ProfileController as ClientPortalProfileController;
use App\Http\Controllers\ClientPortal\RequirementController as ClientPortalRequirementController;
use App\Http\Controllers\ClientPortal\ShortlistController as ClientPortalShortlistController;
use App\Support\AuthRedirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect(AuthRedirect::home(Auth::user()))
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified', 'staff'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/influencers/check-duplicate', [InfluencerController::class, 'checkDuplicate'])->name('influencers.check-duplicate');
    Route::post('/influencers/preferences', [InfluencerController::class, 'preferences'])->name('influencers.preferences');
    Route::resource('influencers', InfluencerController::class);

    Route::post('/influencers/{influencer}/login', [InfluencerLoginController::class, 'store'])->name('influencers.login.store');
    Route::post('/influencers/{influencer}/login/reset-password', [InfluencerLoginController::class, 'resetPassword'])->name('influencers.login.reset-password');
    Route::post('/influencers/{influencer}/login/disable', [InfluencerLoginController::class, 'disable'])->name('influencers.login.disable');
    Route::post('/influencers/{influencer}/login/enable', [InfluencerLoginController::class, 'enable'])->name('influencers.login.enable');

    Route::resource('campaigns', CampaignController::class);
    Route::patch('/campaign-influencers/{campaignInfluencer}/status', [CampaignInfluencerController::class, 'updateStatus'])
        ->name('campaign-influencers.status');
    Route::patch('/campaign-influencers/{campaignInfluencer}/pricing', [CampaignInfluencerController::class, 'updatePricing'])
        ->name('campaign-influencers.pricing');

    Route::post('/deliverables', [DeliverableController::class, 'store'])->name('deliverables.store');
    Route::patch('/deliverables/{deliverable}', [DeliverableController::class, 'update'])->name('deliverables.update');
    Route::delete('/deliverables/{deliverable}', [DeliverableController::class, 'destroy'])->name('deliverables.destroy');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::get('/payments/status/{campaignInfluencer}', [PaymentController::class, 'status'])->name('payments.status');

    Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
    Route::post('/follow-ups', [FollowUpController::class, 'store'])->name('follow-ups.store');
    Route::patch('/follow-ups/{followUp}', [FollowUpController::class, 'update'])->name('follow-ups.update');
    Route::post('/follow-ups/{followUp}/complete', [FollowUpController::class, 'complete'])->name('follow-ups.complete');
    Route::post('/follow-ups/{followUp}/cancel', [FollowUpController::class, 'cancel'])->name('follow-ups.cancel');
    Route::post('/follow-ups/{followUp}/reschedule', [FollowUpController::class, 'reschedule'])->name('follow-ups.reschedule');

    Route::get('/clients/check-duplicate', [ClientController::class, 'checkDuplicate'])->name('clients.check-duplicate');
    Route::post('/clients/{client}/activities', [ClientController::class, 'storeActivity'])->name('clients.activities.store');
    Route::resource('clients', ClientController::class);

    Route::post('/clients/{client}/login', [ClientLoginController::class, 'store'])->name('clients.login.store');
    Route::post('/clients/{client}/login/reset-password', [ClientLoginController::class, 'resetPassword'])->name('clients.login.reset-password');
    Route::post('/clients/{client}/login/disable', [ClientLoginController::class, 'disable'])->name('clients.login.disable');
    Route::post('/clients/{client}/login/enable', [ClientLoginController::class, 'enable'])->name('clients.login.enable');

    Route::get('/requirements', [RequirementController::class, 'index'])->name('requirements.index');
    Route::get('/requirements/create', [RequirementController::class, 'create'])->name('requirements.create');
    Route::post('/requirements', [RequirementController::class, 'store'])->name('requirements.store');
    Route::get('/requirements/{requirement}', [RequirementController::class, 'show'])->name('requirements.show');
    Route::patch('/requirements/{requirement}', [RequirementController::class, 'update'])->name('requirements.update');
    Route::patch('/requirements/{requirement}/status', [RequirementController::class, 'updateStatus'])->name('requirements.status');
    Route::patch('/requirements/{requirement}/assign', [RequirementController::class, 'assign'])->name('requirements.assign');
    Route::post('/requirements/{requirement}/convert-to-campaign', [RequirementController::class, 'convertToCampaign'])->name('requirements.convert-to-campaign');
    Route::post('/requirements/{requirement}/messages', [RequirementController::class, 'storeMessage'])->name('requirements.messages.store');
    Route::post('/requirements/{requirement}/attachments', [RequirementController::class, 'storeAttachment'])->name('requirements.attachments.store');
    Route::get('/requirement-attachments/{attachment}/download', [RequirementController::class, 'downloadAttachment'])->name('requirements.attachments.download');

    Route::post('/requirements/{requirement}/shortlists', [ShortlistController::class, 'store'])->name('requirements.shortlists.store');
    Route::patch('/shortlists/{shortlist}', [ShortlistController::class, 'update'])->name('shortlists.update');
    Route::post('/shortlists/{shortlist}/items', [ShortlistController::class, 'addItem'])->name('shortlists.items.store');
    Route::delete('/shortlists/{shortlist}/items/{item}', [ShortlistController::class, 'removeItem'])->name('shortlists.items.destroy');
    Route::post('/shortlists/{shortlist}/share', [ShortlistController::class, 'share'])->name('shortlists.share');
    Route::post('/shortlists/{shortlist}/withdraw', [ShortlistController::class, 'withdraw'])->name('shortlists.withdraw');

    Route::post('/campaigns/{campaign}/share-with-client', [CampaignController::class, 'shareWithClient'])->name('campaigns.share-with-client');
    Route::post('/campaigns/{campaign}/unshare-with-client', [CampaignController::class, 'unshareWithClient'])->name('campaigns.unshare-with-client');
    Route::patch('/campaigns/{campaign}/visibility', [CampaignController::class, 'updateVisibility'])->name('campaigns.visibility');

    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/exports/influencers', [ExportController::class, 'influencers'])->name('exports.influencers');
    Route::get('/exports/campaigns', [ExportController::class, 'campaigns'])->name('exports.campaigns');
    Route::get('/exports/payments', [ExportController::class, 'payments'])->name('exports.payments');

    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/upload', [ImportController::class, 'upload'])->name('import.upload');
    Route::get('/import/{importJob}/map', [ImportController::class, 'map'])->name('import.map');
    Route::post('/import/{importJob}/map', [ImportController::class, 'saveMapping'])->name('import.save-mapping');
    Route::get('/import/{importJob}/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/{importJob}/process', [ImportController::class, 'process'])->name('import.process');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'influencer'])->prefix('influencer')->name('influencer.')->group(function () {
    Route::get('/dashboard', [InfluencerPortalDashboardController::class, 'index'])->name('portal.dashboard');

    Route::get('/campaigns', [InfluencerPortalCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/{campaignInfluencer}', [InfluencerPortalCampaignController::class, 'show'])->name('campaigns.show');

    Route::get('/deliverables', [InfluencerPortalDeliverableController::class, 'index'])->name('deliverables.index');
    Route::patch('/deliverables/{deliverable}', [InfluencerPortalDeliverableController::class, 'update'])->name('deliverables.update');

    Route::get('/payments', [InfluencerPortalPaymentController::class, 'index'])->name('payments.index');

    Route::get('/profile', [InfluencerPortalProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [InfluencerPortalProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', [ClientPortalDashboardController::class, 'index'])->name('portal.dashboard');

    Route::get('/requirements', [ClientPortalRequirementController::class, 'index'])->name('requirements.index');
    Route::get('/requirements/create', [ClientPortalRequirementController::class, 'create'])->name('requirements.create');
    Route::post('/requirements', [ClientPortalRequirementController::class, 'store'])->name('requirements.store');
    Route::get('/requirements/{requirement}', [ClientPortalRequirementController::class, 'show'])->name('requirements.show');
    Route::post('/requirements/{requirement}/messages', [ClientPortalRequirementController::class, 'storeMessage'])->name('requirements.messages.store');
    Route::post('/requirements/{requirement}/attachments', [ClientPortalRequirementController::class, 'storeAttachment'])->name('requirements.attachments.store');
    Route::get('/requirement-attachments/{attachment}/download', [ClientPortalRequirementController::class, 'downloadAttachment'])->name('requirements.attachments.download');

    Route::get('/shortlists', [ClientPortalShortlistController::class, 'index'])->name('shortlists.index');
    Route::get('/shortlists/{shortlist}', [ClientPortalShortlistController::class, 'show'])->name('shortlists.show');
    Route::post('/shortlist-items/{item}/respond', [ClientPortalShortlistController::class, 'respond'])->name('shortlists.respond');

    Route::get('/campaigns', [ClientPortalCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/{campaign}', [ClientPortalCampaignController::class, 'show'])->name('campaigns.show');
    Route::get('/approvals', [ClientPortalApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/profile', [ClientPortalProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/notifications/{notification}/read', [ClientPortalNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [ClientPortalNotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

require __DIR__.'/auth.php';
