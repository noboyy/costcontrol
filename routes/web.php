<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\CostCategoryController;
use App\Http\Controllers\CostTypeController;
use App\Http\Controllers\DailyCloseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FixedCostController;
use App\Http\Controllers\IncomeCategoryController;
use App\Http\Controllers\IncomeTypeController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PerusahaanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/beranda');
    }
    return redirect('/login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'active',
])->group(function () {
    Route::get('/beranda', [DashboardController::class, 'index'])->name('beranda');

    Route::get('/profil', [ProfileController::class, 'index'])->name('profil');
    Route::post('/profil/data', [ProfileController::class, 'updateData'])->name('profil.updateData');
    Route::post('/profil/password', [ProfileController::class, 'updatePassword'])->name('profil.updatePassword');
    Route::get('/profil/foto', [ProfileController::class, 'photo'])->name('profil.photo');
    Route::post('/profil/foto', [ProfileController::class, 'updatePhoto'])->name('profil.updatePhoto');

    Route::middleware(['role:SUPER ADMIN,ADMIN'])->group(function () {
        // Reports
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export', [ReportController::class, 'export'])->name('reports.export');

        foreach (['cost-centers', 'projects'] as $prefix) {
            $name = $prefix === 'projects' ? 'projects' : 'cost-centers';
            Route::get("/{$prefix}", [ProjectController::class, 'index'])->name("{$name}.index");
            Route::post("/{$prefix}", [ProjectController::class, 'store'])->name("{$name}.store");
            Route::get("/{$prefix}/cost/{id}/bukti", [ProjectController::class, 'costBukti'])->name("{$name}.costBukti");
            Route::get("/{$prefix}/income/{id}/bukti", [ProjectController::class, 'incomeBukti'])->name("{$name}.incomeBukti");
            Route::get("/{$prefix}/{id}", [ProjectController::class, 'show'])->name("{$name}.show");
            Route::post("/{$prefix}/{id}/update", [ProjectController::class, 'update'])->name("{$name}.update");
            Route::post("/{$prefix}/{id}/cost", [ProjectController::class, 'addCost'])->name("{$name}.addCost");
            Route::post("/{$prefix}/{id}/cost/{costId}/update", [ProjectController::class, 'updateCost'])->name("{$name}.updateCost");
            Route::post("/{$prefix}/{id}/income", [ProjectController::class, 'addIncome'])->name("{$name}.addIncome");
            Route::post("/{$prefix}/{id}/income/{incomeId}/update", [ProjectController::class, 'updateIncome'])->name("{$name}.updateIncome");
            Route::post("/{$prefix}/{id}/archive", [ProjectController::class, 'archive'])->name("{$name}.archive");
            Route::post("/{$prefix}/{id}/delete", [ProjectController::class, 'delete'])->name("{$name}.delete");
            Route::post("/{$prefix}/{id}/cost/{costId}/delete", [ProjectController::class, 'deleteCost'])->name("{$name}.deleteCost");
            Route::post("/{$prefix}/{id}/income/{incomeId}/delete", [ProjectController::class, 'deleteIncome'])->name("{$name}.deleteIncome");

            // Plans (RAB)
            Route::post("/{$prefix}/{id}/cost-plans", [ProjectController::class, 'storeCostPlan'])->name("{$name}.costPlans.store");
            Route::post("/{$prefix}/{id}/cost-plans/{planId}/update", [ProjectController::class, 'updateCostPlan'])->name("{$name}.costPlans.update");
            Route::post("/{$prefix}/{id}/cost-plans/{planId}/delete", [ProjectController::class, 'deleteCostPlan'])->name("{$name}.costPlans.delete");
            Route::post("/{$prefix}/{id}/income-plans", [ProjectController::class, 'storeIncomePlan'])->name("{$name}.incomePlans.store");
            Route::post("/{$prefix}/{id}/income-plans/{planId}/update", [ProjectController::class, 'updateIncomePlan'])->name("{$name}.incomePlans.update");
            Route::post("/{$prefix}/{id}/income-plans/{planId}/delete", [ProjectController::class, 'deleteIncomePlan'])->name("{$name}.incomePlans.delete");

            // Admins
            Route::post("/{$prefix}/{id}/admins", [ProjectController::class, 'syncAdmins'])->name("{$name}.admins.sync");

            // Fixed cost
            Route::post("/{$prefix}/{id}/fixed-costs", [FixedCostController::class, 'store'])->name("{$name}.fixedCosts.store");
            Route::post("/{$prefix}/{id}/fixed-costs/{fixedId}/update", [FixedCostController::class, 'update'])->name("{$name}.fixedCosts.update");
            Route::post("/{$prefix}/{id}/fixed-costs/{fixedId}/delete", [FixedCostController::class, 'delete'])->name("{$name}.fixedCosts.delete");

            // Daily close
            Route::post("/{$prefix}/{id}/daily-close", [DailyCloseController::class, 'store'])->name("{$name}.dailyClose.store");
            Route::post("/{$prefix}/{id}/daily-close/reopen", [DailyCloseController::class, 'destroy'])->name("{$name}.dailyClose.reopen");
        }

        // Master data
        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::post('/units/{id}/update', [UnitController::class, 'update'])->name('units.update');
        Route::post('/units/{id}/delete', [UnitController::class, 'delete'])->name('units.delete');

        Route::get('/cost-categories', [CostCategoryController::class, 'index'])->name('cost-categories.index');
        Route::post('/cost-categories', [CostCategoryController::class, 'store'])->name('cost-categories.store');
        Route::post('/cost-categories/{id}/update', [CostCategoryController::class, 'update'])->name('cost-categories.update');
        Route::post('/cost-categories/{id}/delete', [CostCategoryController::class, 'delete'])->name('cost-categories.delete');

        Route::get('/cost-types', [CostTypeController::class, 'index'])->name('cost-types.index');
        Route::post('/cost-types', [CostTypeController::class, 'store'])->name('cost-types.store');
        Route::post('/cost-types/{id}/update', [CostTypeController::class, 'update'])->name('cost-types.update');
        Route::post('/cost-types/{id}/delete', [CostTypeController::class, 'delete'])->name('cost-types.delete');

        Route::get('/income-categories', [IncomeCategoryController::class, 'index'])->name('income-categories.index');
        Route::post('/income-categories', [IncomeCategoryController::class, 'store'])->name('income-categories.store');
        Route::post('/income-categories/{id}/update', [IncomeCategoryController::class, 'update'])->name('income-categories.update');
        Route::post('/income-categories/{id}/delete', [IncomeCategoryController::class, 'delete'])->name('income-categories.delete');

        Route::get('/income-types', [IncomeTypeController::class, 'index'])->name('income-types.index');
        Route::post('/income-types', [IncomeTypeController::class, 'store'])->name('income-types.store');
        Route::post('/income-types/{id}/update', [IncomeTypeController::class, 'update'])->name('income-types.update');
        Route::post('/income-types/{id}/delete', [IncomeTypeController::class, 'delete'])->name('income-types.delete');

        Route::get('/asset', [AssetController::class, 'index'])->name('asset.index');
        Route::post('/asset', [AssetController::class, 'store'])->name('asset.store');
        Route::get('/asset/{id}/image', [AssetController::class, 'image'])->name('asset.image');
        Route::post('/asset/{id}/update', [AssetController::class, 'update'])->name('asset.update');
        Route::post('/asset/{id}/delete', [AssetController::class, 'delete'])->name('asset.delete');
        Route::post('/asset/{id}/sell', [AssetController::class, 'sell'])->name('asset.sell');
        Route::post('/asset/{id}/maintenance', [AssetController::class, 'addMaintenance'])->name('asset.addMaintenance');
        Route::post('/asset/maintenance/{id}/delete', [AssetController::class, 'deleteMaintenance'])->name('asset.deleteMaintenance');

        // Perusahaan (admin can edit own; super admin all)
        Route::get('/perusahaan', [PerusahaanController::class, 'index'])->name('perusahaan.index');
        Route::post('/perusahaan', [PerusahaanController::class, 'store'])->name('perusahaan.store');
        Route::post('/perusahaan/{id}/update', [PerusahaanController::class, 'update'])->name('perusahaan.update');
        Route::post('/perusahaan/{id}/delete', [PerusahaanController::class, 'delete'])->name('perusahaan.delete');
    });

    Route::middleware(['role:SUPER ADMIN'])->group(function () {
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::post('/pengguna/{id}/update', [PenggunaController::class, 'update'])->name('pengguna.update');
        Route::post('/pengguna/{id}/delete', [PenggunaController::class, 'delete'])->name('pengguna.delete');
    });
});
