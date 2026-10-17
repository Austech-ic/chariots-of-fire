<?php

use App\Http\Controllers\AppointmentManageController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CounsellingController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Member\AuthController;
use App\Http\Controllers\Member\PortalController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SermonController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Localised public website
|--------------------------------------------------------------------------
| Registered twice: unprefixed for the default language (English) and under
| /{locale} for every other enabled language. Use lroute() to build links.
*/
$public = function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/about/mandates/{mandate}', [PageController::class, 'mandate'])->name('mandates.show');
    Route::get('/founder', [PageController::class, 'founder'])->name('founder');
    Route::get('/pages/{page}', [PageController::class, 'show'])->name('pages.show');

    Route::get('/live', [LiveController::class, 'index'])->name('live');

    Route::get('/sermons', [SermonController::class, 'index'])->name('sermons.index');
    Route::get('/sermons/{sermon}', [SermonController::class, 'show'])->name('sermons.show');

    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/events/{event}/calendar.ics', [EventController::class, 'ics'])->name('events.ics');
    Route::post('/events/{event}/register', [EventController::class, 'register'])->middleware('throttle:forms')->name('events.register');

    Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
    Route::get('/gallery/{album}', [GalleryController::class, 'show'])->name('gallery.show');

    Route::get('/counselling', [CounsellingController::class, 'index'])->name('counselling.index');
    Route::get('/counselling/request', [CounsellingController::class, 'requestForm'])->name('counselling.request');
    Route::post('/counselling/request', [CounsellingController::class, 'storeRequest'])->middleware('throttle:forms')->name('counselling.request.store');
    Route::get('/counselling/book', [CounsellingController::class, 'book'])->name('counselling.book');
    Route::post('/counselling/book', [CounsellingController::class, 'storeBooking'])->middleware('throttle:booking')->name('counselling.book.store');
    Route::get('/counselling/thank-you', [CounsellingController::class, 'thanks'])->name('counselling.thanks');

    Route::get('/give', [DonationController::class, 'index'])->name('give');
    Route::post('/give', [DonationController::class, 'store'])->middleware('throttle:forms')->name('give.store');
    Route::get('/give/status/{donation}', [DonationController::class, 'status'])->name('give.status');

    Route::get('/contact', [ContactController::class, 'show'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:forms')->name('contact.store');

    Route::get('/newsletter/subscribe', [NewsletterController::class, 'show'])->name('newsletter.show');
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:forms')->name('newsletter.subscribe');
};

Route::group(['localized' => true], $public);

Route::prefix('{locale}')
    ->where(['locale' => '(?!up$)[a-z]{2}(?:-[a-z]{2})?'])
    ->name('l.')
    ->group(fn () => Route::group(['localized' => true], $public));

/*
|--------------------------------------------------------------------------
| Non-localised endpoints
|--------------------------------------------------------------------------
*/
Route::get('/media/{asset:uuid}', [MediaController::class, 'show'])->name('media.show');
Route::get('/media/{asset:uuid}/download', [MediaController::class, 'download'])->name('media.download');

Route::get('/give/callback/{donation}', [DonationController::class, 'callback'])->name('give.callback');
Route::get('/give/test-checkout/{reference}', [DonationController::class, 'testCheckout'])->name('give.test-checkout');
Route::post('/give/test-checkout/{reference}', [DonationController::class, 'testSettle'])->name('give.test-settle');

Route::post('/webhooks/payments/{provider}', [WebhookController::class, 'payments'])
    ->middleware('throttle:webhooks')->name('webhooks.payments');

Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{subscription}', [NewsletterController::class, 'unsubscribe'])->middleware('signed')->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{subscription}', [NewsletterController::class, 'unsubscribe'])->middleware('signed')->name('newsletter.unsubscribe.post');

Route::get('/appointments/{reference}', [AppointmentManageController::class, 'show'])->name('appointments.manage');
Route::post('/appointments/{reference}/accept', [AppointmentManageController::class, 'accept'])->middleware('throttle:forms')->name('appointments.accept');
Route::post('/appointments/{reference}/decline', [AppointmentManageController::class, 'decline'])->middleware('throttle:forms')->name('appointments.decline');
Route::post('/appointments/{reference}/cancel', [AppointmentManageController::class, 'cancel'])->middleware('throttle:forms')->name('appointments.cancel');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

/*
|--------------------------------------------------------------------------
| Member portal
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:forms');
    Route::get('/forgot-password', [AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:auth')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [AuthController::class, 'verifyNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1')->name('verification.send');
});

Route::prefix('member')->name('member.')->middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [PortalController::class, 'profile'])->name('profile');
    Route::put('/profile', [PortalController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [PortalController::class, 'updatePassword'])->name('password.update');
    Route::get('/preferences', [PortalController::class, 'preferences'])->name('preferences');
    Route::put('/preferences', [PortalController::class, 'updatePreferences'])->name('preferences.update');
    Route::get('/appointments', [PortalController::class, 'appointments'])->name('appointments');
    Route::get('/donations', [PortalController::class, 'donations'])->name('donations');
    Route::get('/registrations', [PortalController::class, 'registrations'])->name('registrations');
    Route::get('/resources', [PortalController::class, 'resources'])->name('resources');
    Route::get('/resources/{resource}', [PortalController::class, 'resource'])->name('resources.show');
    Route::get('/resources/{resource}/download', [PortalController::class, 'downloadResource'])->name('resources.download');
    Route::get('/account', [PortalController::class, 'account'])->name('account');
    Route::post('/account/requests', [PortalController::class, 'storeAccountRequest'])->middleware('throttle:forms')->name('account.request');
});

Route::get('/locale/{locale}', [PortalController::class, 'setLocale'])->name('locale.set');

/*
|--------------------------------------------------------------------------
| Web installer (shared hosting without SSH). Disabled after installation.
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
    Route::post('/run', [InstallController::class, 'run'])->name('run');
});
