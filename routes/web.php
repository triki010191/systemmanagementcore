<?php

use App\Enums\NetworkNodeType;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Network\AssetController;
use App\Http\Controllers\Network\AssetPhotoController;
use App\Http\Controllers\Network\AssetQrController;
use App\Http\Controllers\Network\BulkImportController;
use App\Http\Controllers\Network\CableController;
use App\Http\Controllers\Network\CoreJointController;
use App\Http\Controllers\Network\CoreManagementController;
use App\Http\Controllers\Network\CustomerConnectionController;
use App\Http\Controllers\Network\GisMapController;
use App\Http\Controllers\Network\ImpactAnalysisController;
use App\Http\Controllers\Network\MaintenanceScheduleController;
use App\Http\Controllers\Network\OdcDistributionController;
use App\Http\Controllers\Network\OtdrRecordController;
use App\Http\Controllers\Network\PathTracingController;
use App\Http\Controllers\Network\ScanController;
use App\Http\Controllers\Network\TroubleTicketController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch')
    ->whereIn('locale', ['id', 'en']);

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/scan/cable/{code}', [ScanController::class, 'cable'])->name('scan.cable');
Route::get('/scan/{uuid}', [ScanController::class, 'show'])->name('scan.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('can:path-tracing.view')->prefix('path-tracing')->name('path-tracing.')->group(function () {
        Route::get('/', [PathTracingController::class, 'index'])->name('index');
        Route::get('/search', [PathTracingController::class, 'search'])->name('search');
        Route::middleware('can:report.export')->get('/export-pdf', [PathTracingController::class, 'exportPdf'])->name('export-pdf');
    });

    Route::middleware('can:network.view')->prefix('impact-analysis')->name('impact-analysis.')->group(function () {
        Route::get('/', [ImpactAnalysisController::class, 'index'])->name('index');
    });

    Route::prefix('trouble-tickets')->name('trouble-tickets.')->group(function () {
        Route::middleware('can:fault.view')->get('/', [TroubleTicketController::class, 'index'])->name('index');
        Route::middleware('can:fault.manage')->get('/create', [TroubleTicketController::class, 'create'])->name('create');
        Route::middleware('can:fault.manage')->post('/', [TroubleTicketController::class, 'store'])->name('store');
        Route::middleware('can:fault.view')->get('/{troubleTicket}', [TroubleTicketController::class, 'show'])->name('show')->whereNumber('troubleTicket');
        Route::middleware('can:fault.manage')->group(function () {
            Route::get('/{troubleTicket}/edit', [TroubleTicketController::class, 'edit'])->name('edit')->whereNumber('troubleTicket');
            Route::put('/{troubleTicket}', [TroubleTicketController::class, 'update'])->name('update')->whereNumber('troubleTicket');
            Route::delete('/{troubleTicket}', [TroubleTicketController::class, 'destroy'])->name('destroy')->whereNumber('troubleTicket');
        });
    });

    Route::prefix('otdr-records')->name('otdr-records.')->group(function () {
        Route::middleware('can:cable.manage')->get('/', [OtdrRecordController::class, 'index'])->name('index');
        Route::middleware('can:cable.manage')->get('/create', [OtdrRecordController::class, 'create'])->name('create');
        Route::middleware('can:cable.manage')->post('/parse-sor', [OtdrRecordController::class, 'parseSor'])->name('parse-sor');
        Route::middleware('can:cable.manage')->post('/', [OtdrRecordController::class, 'store'])->name('store');
        Route::middleware('can:cable.manage')->get('/{otdrRecord}/trace', [OtdrRecordController::class, 'downloadTrace'])->name('trace.download')->whereNumber('otdrRecord');
        Route::middleware('can:cable.manage')->get('/{otdrRecord}', [OtdrRecordController::class, 'show'])->name('show')->whereNumber('otdrRecord');
        Route::middleware('can:cable.manage')->get('/{otdrRecord}/edit', [OtdrRecordController::class, 'edit'])->name('edit')->whereNumber('otdrRecord');
        Route::middleware('can:cable.manage')->put('/{otdrRecord}', [OtdrRecordController::class, 'update'])->name('update')->whereNumber('otdrRecord');
        Route::middleware('can:cable.manage')->delete('/{otdrRecord}', [OtdrRecordController::class, 'destroy'])->name('destroy')->whereNumber('otdrRecord');
    });

    Route::prefix('maintenance-schedules')->name('maintenance-schedules.')->group(function () {
        Route::middleware('can:maintenance.view')->get('/', [MaintenanceScheduleController::class, 'index'])->name('index');
        Route::middleware('can:maintenance.view')->get('/calendar', [MaintenanceScheduleController::class, 'calendar'])->name('calendar');
        Route::middleware('can:maintenance.manage')->get('/create', [MaintenanceScheduleController::class, 'create'])->name('create');
        Route::middleware('can:maintenance.manage')->post('/', [MaintenanceScheduleController::class, 'store'])->name('store');
        Route::middleware('can:maintenance.view')->get('/{maintenanceSchedule}', [MaintenanceScheduleController::class, 'show'])->name('show')->whereNumber('maintenanceSchedule');
        Route::middleware('can:maintenance.manage')->group(function () {
            Route::get('/{maintenanceSchedule}/edit', [MaintenanceScheduleController::class, 'edit'])->name('edit')->whereNumber('maintenanceSchedule');
            Route::put('/{maintenanceSchedule}', [MaintenanceScheduleController::class, 'update'])->name('update')->whereNumber('maintenanceSchedule');
            Route::delete('/{maintenanceSchedule}', [MaintenanceScheduleController::class, 'destroy'])->name('destroy')->whereNumber('maintenanceSchedule');
        });
    });

    Route::middleware('can:audit.view')->prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/export', [AuditLogController::class, 'export'])->name('export');
        Route::get('/{auditLog}', [AuditLogController::class, 'show'])->name('show')->whereNumber('auditLog');
    });

    Route::middleware('can:cms.settings.manage')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
    });

    Route::middleware('can:users.manage')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::get('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
    });

    Route::middleware('can:gis-map.view')->group(function () {
        Route::get('/gis', [GisMapController::class, 'index'])->name('gis.index');
        Route::get('/gis/route', [GisMapController::class, 'route'])->name('gis.route');
    });

    Route::prefix('customer-connections')->name('customer-connections.')->group(function () {
        Route::middleware('can:customer.view')->group(function () {
            Route::get('/', [CustomerConnectionController::class, 'index'])->name('index');
            Route::get('/{connection}', [CustomerConnectionController::class, 'show'])->name('show')->whereNumber('connection');
        });
        Route::middleware('can:customer.manage')->group(function () {
            Route::get('/odp-options/{odp}', [CustomerConnectionController::class, 'odpOptions'])->name('odp-options')->whereNumber('odp');
            Route::get('/create', [CustomerConnectionController::class, 'create'])->name('create');
            Route::post('/', [CustomerConnectionController::class, 'store'])->name('store');
            Route::get('/{connection}/edit', [CustomerConnectionController::class, 'edit'])->name('edit')->whereNumber('connection');
            Route::put('/{connection}', [CustomerConnectionController::class, 'update'])->name('update')->whereNumber('connection');
            Route::delete('/{connection}', [CustomerConnectionController::class, 'destroy'])->name('destroy')->whereNumber('connection');
        });
    });

    Route::middleware('can:cable.manage')->prefix('cables')->name('cables.')->group(function () {
        Route::get('/', [CableController::class, 'index'])->name('index');
        Route::get('/create', [CableController::class, 'create'])->name('create');
        Route::post('/', [CableController::class, 'store'])->name('store');
        Route::get('/{cable}/edit', [CableController::class, 'edit'])->name('edit');
        Route::put('/{cable}', [CableController::class, 'update'])->name('update');
        Route::post('/{cable}/activate', [CableController::class, 'activate'])->name('activate');
        Route::delete('/{cable}', [CableController::class, 'destroy'])->name('destroy');
        Route::post('/{cable}/qr', [AssetQrController::class, 'generateCable'])->name('qr.generate');
        Route::get('/{cable}', [CableController::class, 'show'])->name('show');
    });

    Route::middleware('can:cable.manage')->prefix('core-joints')->name('core-joints.')->group(function () {
        Route::get('/', [CoreJointController::class, 'index'])->name('index');
        Route::get('/create', [CoreJointController::class, 'create'])->name('create');
        Route::get('/options', [CoreJointController::class, 'options'])->name('options');
        Route::post('/', [CoreJointController::class, 'store'])->name('store');
        Route::get('/{coreJoint}/edit', [CoreJointController::class, 'edit'])->name('edit');
        Route::put('/{coreJoint}', [CoreJointController::class, 'update'])->name('update');
        Route::delete('/{coreJoint}', [CoreJointController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('can:core.manage')->prefix('cores')->name('cores.')->group(function () {
        Route::get('/', [CoreManagementController::class, 'index'])->name('index');
        Route::get('/{core}/edit', [CoreManagementController::class, 'edit'])->name('edit')->whereNumber('core');
        Route::put('/{core}', [CoreManagementController::class, 'update'])->name('update')->whereNumber('core');
        Route::delete('/{core}', [CoreManagementController::class, 'destroy'])->name('destroy')->whereNumber('core');
    });

    Route::middleware('can:network.create')->prefix('bulk-import')->name('bulk-import.')->group(function () {
        Route::get('/', [BulkImportController::class, 'index'])->name('index');
        Route::get('/template/{type}', [BulkImportController::class, 'template'])->name('template')->where('type', 'odp|customer');
        Route::post('/{type}', [BulkImportController::class, 'import'])->name('import')->where('type', 'odp|customer');
    });

    Route::middleware('can:network.edit')->delete('/photos/{photo}', [AssetPhotoController::class, 'destroy'])->name('asset-photos.destroy')->whereNumber('photo');

    $assetTypes = implode('|', array_map(fn (NetworkNodeType $t) => $t->routeSlug(), NetworkNodeType::cases()));

    Route::redirect('/network', '/network/pops')->name('network.home');

    Route::middleware('can:network.view')->prefix('network')->name('network.')->group(function () use ($assetTypes) {
        Route::get('{type}', [AssetController::class, 'index'])->name('assets.index')->where('type', $assetTypes);
        Route::middleware('can:network.create')->group(function () use ($assetTypes) {
            Route::get('{type}/create', [AssetController::class, 'create'])->name('assets.create')->where('type', $assetTypes);
            Route::post('{type}', [AssetController::class, 'store'])->name('assets.store')->where('type', $assetTypes);
        });
        Route::get('{type}/{asset}', [AssetController::class, 'show'])->name('assets.show')->where('type', $assetTypes)->whereNumber('asset');
        Route::middleware('can:network.edit')->group(function () use ($assetTypes) {
            Route::get('{type}/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit')->where('type', $assetTypes)->whereNumber('asset');
            Route::put('{type}/{asset}', [AssetController::class, 'update'])->name('assets.update')->where('type', $assetTypes)->whereNumber('asset');
            Route::delete('{type}/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy')->where('type', $assetTypes)->whereNumber('asset');
            Route::post('{type}/{asset}/qr', [AssetQrController::class, 'generateNode'])->name('assets.qr.generate')->where('type', $assetTypes)->whereNumber('asset');
            Route::post('{type}/{asset}/photos', [AssetPhotoController::class, 'store'])->name('assets.photos.store')->where('type', $assetTypes)->whereNumber('asset');
            Route::get('odc/{odc}/distribution/options', [OdcDistributionController::class, 'options'])->name('odc.distribution.options')->whereNumber('odc');
            Route::put('odc/{odc}/distribution', [OdcDistributionController::class, 'update'])->name('odc.distribution.update')->whereNumber('odc');
        });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
