<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLoginForm']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AttendanceController::class, 'dashboard'])->name('dashboard');
    Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clock.in');
    Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clock.out');
    Route::post('/report-absent', [AttendanceController::class, 'reportAbsent'])->name('report.absent');
    Route::post('/report-absent-attachment', [AttendanceController::class, 'uploadAbsentAttachment'])->name('report.absent.attachment');
    Route::post('/attendance/appeal/{id}', [AttendanceController::class, 'submitAppeal'])->name('attendance.submitAppeal');
    Route::get('/report', [AttendanceController::class, 'monthlyReport'])->name('report');
    Route::get('/report/export-pdf', [AttendanceController::class, 'exportPdf'])->name('report.exportPdf');
    Route::get('/admin/staff', [AdminController::class, 'staffList'])->name('admin.staffList');
    Route::get('/admin/appeals', [AdminController::class, 'appealsList'])->name('admin.appeals');
    Route::post('/admin/appeals/{id}/approve', [AdminController::class, 'approveAppeal'])->name('admin.approveAppeal');
    Route::post('/admin/appeals/{id}/reject', [AdminController::class, 'rejectAppeal'])->name('admin.rejectAppeal');
    Route::get('/admin/create-staff', [AdminController::class, 'createStaffForm'])->name('admin.createStaff');
    Route::post('/admin/create-staff', [AdminController::class, 'storeStaff']);
    Route::get('/admin/staff/{id}/edit', [AdminController::class, 'editStaffForm'])->name('admin.editStaff');
    Route::put('/admin/staff/{id}', [AdminController::class, 'updateStaff'])->name('admin.updateStaff');
    Route::delete('/admin/staff/{id}', [AdminController::class, 'deleteStaff'])->name('admin.deleteStaff');
    Route::post('/admin/staff/{id}/restore', [AdminController::class, 'restoreStaff'])->name('admin.restoreStaff');
    Route::delete('/admin/staff/{id}/force', [AdminController::class, 'forceDeleteStaff'])->name('admin.forceDeleteStaff');
});

// Admin routes with middleware for admin access
// Route::middleware(['auth', 'admin'])->group(function () {