<?php

use App\Http\Controllers\Api\AssetsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvestorController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'active', 'trial'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects/{id}', [ProjectController::class, 'show']);
        Route::put('/projects/{id}', [ProjectController::class, 'update']);
        Route::post('/projects/{id}/archive', [ProjectController::class, 'archive']);
        Route::delete('/projects/{id}', [ProjectController::class, 'delete']);
        Route::get('/projects/{id}/cash', [ProjectController::class, 'cash']);

        Route::post('/projects/{id}/costs', [ProjectController::class, 'addCost']);
        Route::put('/projects/{id}/costs/{costId}', [ProjectController::class, 'updateCost']);
        Route::delete('/projects/{id}/costs/{costId}', [ProjectController::class, 'deleteCost']);

        Route::post('/projects/{id}/incomes', [ProjectController::class, 'addIncome']);
        Route::put('/projects/{id}/incomes/{incomeId}', [ProjectController::class, 'updateIncome']);
        Route::delete('/projects/{id}/incomes/{incomeId}', [ProjectController::class, 'deleteIncome']);

        Route::put('/projects/{id}/admins', [ProjectController::class, 'syncAdmins']);

        Route::post('/projects/{id}/plans/{kind}', [ProjectController::class, 'upsertPlan']);
        Route::put('/projects/{id}/plans/{kind}/{planId}', [ProjectController::class, 'upsertPlan']);
        Route::delete('/projects/{id}/plans/{kind}/{planId}', [ProjectController::class, 'deletePlan']);

        Route::get('/categories/{kind}', [CategoriesController::class, 'index']);
        Route::post('/categories/{kind}', [CategoriesController::class, 'store']);
        Route::put('/categories/{kind}/{id}', [CategoriesController::class, 'update']);
        Route::delete('/categories/{kind}/{id}', [CategoriesController::class, 'delete']);

        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/export', [ReportController::class, 'export']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'updateData']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

        Route::middleware('not-super-admin')->group(function () {
            Route::get('/assets', [AssetsController::class, 'index']);
            Route::post('/assets', [AssetsController::class, 'store']);
            Route::put('/assets/{id}', [AssetsController::class, 'update']);
            Route::delete('/assets/{id}', [AssetsController::class, 'delete']);
            Route::post('/assets/{id}/sell', [AssetsController::class, 'sell']);
            Route::post('/assets/{id}/maintenance', [AssetsController::class, 'addMaintenance']);
            Route::delete('/assets/{id}/maintenance/{maintenanceId}', [AssetsController::class, 'deleteMaintenance']);
            Route::get('/assets/{id}/image', [AssetsController::class, 'image']);
        });

        Route::middleware('role:SUPER ADMIN')->group(function () {
            Route::get('/users', [UsersController::class, 'index']);
            Route::post('/users', [UsersController::class, 'store']);
            Route::put('/users/{id}', [UsersController::class, 'update']);
            Route::delete('/users/{id}', [UsersController::class, 'delete']);
        });

        // Investor management — ADMIN only
        Route::middleware('role:ADMIN')->group(function () {
            Route::get('/projects/{id}/investor', [ProjectController::class, 'showInvestor']);
            Route::post('/projects/{id}/investor', [ProjectController::class, 'assignInvestor']);
            Route::delete('/projects/{id}/investor', [ProjectController::class, 'revokeInvestor']);
            Route::post('/projects/{id}/investor/reset-password', [ProjectController::class, 'resetInvestorPassword']);
        });
    });

    // Investor read-only routes
    Route::middleware(['auth:sanctum', 'active', 'investor'])->group(function () {
        Route::get('/investor/project', [InvestorController::class, 'project']);
        Route::get('/investor/project/costs', [InvestorController::class, 'costs']);
        Route::get('/investor/project/incomes', [InvestorController::class, 'incomes']);
        Route::get('/investor/project/report', [InvestorController::class, 'report']);
    });
});
