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
        Route::get('/projects/{project}', [ProjectController::class, 'show']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);
        Route::delete('/projects/{project}', [ProjectController::class, 'delete']);

        Route::post('/projects/{project}/costs', [ProjectController::class, 'addCost']);
        Route::put('/projects/{project}/costs/{cost}', [ProjectController::class, 'updateCost']);
        Route::delete('/projects/{project}/costs/{cost}', [ProjectController::class, 'deleteCost']);

        Route::post('/projects/{project}/incomes', [ProjectController::class, 'addIncome']);
        Route::put('/projects/{project}/incomes/{income}', [ProjectController::class, 'updateIncome']);
        Route::delete('/projects/{project}/incomes/{income}', [ProjectController::class, 'deleteIncome']);

        Route::put('/projects/{project}/admins', [ProjectController::class, 'syncAdmins']);

        Route::post('/projects/{project}/plans/{kind}', [ProjectController::class, 'upsertPlan']);
        Route::put('/projects/{project}/plans/{kind}/{plan}', [ProjectController::class, 'upsertPlan']);
        Route::delete('/projects/{project}/plans/{kind}/{plan}', [ProjectController::class, 'deletePlan']);

        Route::get('/categories/{kind}', [CategoriesController::class, 'index']);
        Route::post('/categories/{kind}', [CategoriesController::class, 'store']);
        Route::put('/categories/{kind}/{type}', [CategoriesController::class, 'update']);
        Route::delete('/categories/{kind}/{type}', [CategoriesController::class, 'delete']);

        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/export', [ReportController::class, 'export']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'updateData']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

        Route::get('/assets', [AssetsController::class, 'index']);
        Route::post('/assets', [AssetsController::class, 'store']);
        Route::put('/assets/{asset}', [AssetsController::class, 'update']);
        Route::delete('/assets/{asset}', [AssetsController::class, 'delete']);
        Route::post('/assets/{asset}/sell', [AssetsController::class, 'sell']);
        Route::post('/assets/{asset}/maintenance', [AssetsController::class, 'addMaintenance']);
        Route::delete('/assets/{asset}/maintenance/{maintenance}', [AssetsController::class, 'deleteMaintenance']);
        Route::get('/assets/{asset}/image', [AssetsController::class, 'image']);

        Route::middleware('role:SUPER ADMIN')->group(function () {
            Route::get('/users', [UsersController::class, 'index']);
            Route::post('/users', [UsersController::class, 'store']);
            Route::put('/users/{pengguna}', [UsersController::class, 'update']);
            Route::delete('/users/{pengguna}', [UsersController::class, 'delete']);
        });

        // Investor management — ADMIN only
        Route::middleware('role:ADMIN')->group(function () {
            Route::get('/projects/{project}/investor', [ProjectController::class, 'showInvestor']);
            Route::post('/projects/{project}/investor', [ProjectController::class, 'assignInvestor']);
            Route::delete('/projects/{project}/investor', [ProjectController::class, 'revokeInvestor']);
            Route::post('/projects/{project}/investor/reset-password', [ProjectController::class, 'resetInvestorPassword']);
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
