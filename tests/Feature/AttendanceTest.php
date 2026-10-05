<?php

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

test('staff can clock in and real clock_in timestamp is recorded in database', function () {
    $user = User::factory()->create([
        'role' => 'staff',
    ]);

    $response = $this->actingAs($user)->post('/clock-in', [
        'remarks' => 'Pejabat',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $attendance = Attendance::where('user_id', $user->id)
        ->where('date', Carbon::today()->toDateString())
        ->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->clock_in)->not->toBeNull()
        ->and($attendance->clock_out)->toBeNull()
        ->and($attendance->remarks)->toBe('Pejabat');
});

test('admin cannot clock in', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->post('/clock-in', [
        'remarks' => 'Pejabat',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Akaun Admin tidak dibenarkan untuk clock in.');

    $this->assertDatabaseMissing('attendances', [
        'user_id' => $admin->id,
    ]);
});

test('staff cannot clock in twice without clocking out first', function () {
    $user = User::factory()->create([
        'role' => 'staff',
    ]);

    Attendance::create([
        'user_id' => $user->id,
        'date' => Carbon::today()->toDateString(),
        'clock_in' => '08:00:00',
        'remarks' => 'Sesi 1',
    ]);

    $response = $this->actingAs($user)->post('/clock-in', [
        'remarks' => 'Sesi 2',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('staff can clock out and real clock_out is recorded in database', function () {
    $user = User::factory()->create([
        'role' => 'staff',
    ]);

    $att = Attendance::create([
        'user_id' => $user->id,
        'date' => Carbon::today()->toDateString(),
        'clock_in' => '08:00:00',
    ]);

    $response = $this->actingAs($user)->post('/clock-out');

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $att->refresh();
    expect($att->clock_out)->not->toBeNull();
});

test('admin cannot clock out', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->post('/clock-out');

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Akaun Admin tidak dibenarkan untuk clock out.');
});

test('admin accessing dashboard is redirected to monthly report', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertRedirect(route('report'));
});

test('monthly report accurately calculates real hours and attendance scores for staff', function () {
    $user = User::factory()->create([
        'role' => 'staff',
    ]);

    $date = Carbon::now()->startOfMonth()->addDays(9)->toDateString();
    Attendance::create([
        'user_id' => $user->id,
        'date' => $date,
        'clock_in' => '08:00:00',
        'clock_out' => '14:00:00',
        'remarks' => 'Kerja Penuh',
    ]);

    $response = $this->actingAs($user)->get(route('report', [
        'month' => Carbon::now()->month,
        'year' => Carbon::now()->year,
    ]));

    $response->assertOk();
    $response->assertSee('Kerja Penuh');
    $response->assertSee('06:00');
    $response->assertSee('1.0');
});

test('admin can view staff attendance record for the month', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $staff = User::factory()->create([
        'role' => 'staff',
        'name' => 'Ahmad Albab',
        'staff_id' => 'STF100',
    ]);

    $date = Carbon::now()->startOfMonth()->addDays(4)->toDateString();
    Attendance::create([
        'user_id' => $staff->id,
        'date' => $date,
        'clock_in' => '08:30:00',
        'clock_out' => '17:30:00',
        'remarks' => 'Hadir Penuh',
    ]);

    $response = $this->actingAs($admin)->get(route('report', [
        'user_id' => $staff->id,
        'month' => Carbon::now()->month,
        'year' => Carbon::now()->year,
    ]));

    $response->assertOk();
    $response->assertSee('Ahmad Albab');
    $response->assertSee('STF100');
    $response->assertSee('Hadir Penuh');
});

test('staff can view dashboard and see active status', function () {
    $user = User::factory()->create([
        'role' => 'staff',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Status Bertugas Hari Ini');
    $response->assertSee('Belum Clock In');
});

test('admin can create new staff and view staff list', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.createStaff'));
    $response->assertOk();
    $response->assertSee('Daftar Akaun Staf Baru');

    $postResponse = $this->actingAs($admin)->post('/admin/create-staff', [
        'staff_id' => 'STF888',
        'name' => 'Siti Aminah',
        'password' => 'secret123',
    ]);

    $postResponse->assertRedirect();
    $postResponse->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'staff_id' => 'STF888',
        'name' => 'Siti Aminah',
        'role' => 'staff',
    ]);
});

test('non-admin cannot access admin create staff page', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
    ]);

    $response = $this->actingAs($staff)->get(route('admin.createStaff'));
    $response->assertForbidden();
});

test('admin can delete staff and associated attendances are cascaded', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $staff = User::factory()->create([
        'role' => 'staff',
        'name' => 'Kakitangan Ujian',
        'staff_id' => 'STF999',
    ]);

    Attendance::create([
        'user_id' => $staff->id,
        'date' => Carbon::today()->toDateString(),
        'clock_in' => '09:00:00',
    ]);

    $response = $this->actingAs($admin)->delete(route('admin.deleteStaff', $staff->id));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Akaun staf berjaya dipadam.');

    $this->assertDatabaseMissing('users', [
        'id' => $staff->id,
    ]);

    $this->assertDatabaseMissing('attendances', [
        'user_id' => $staff->id,
    ]);
});

test('non-admin cannot delete staff', function () {
    $staff1 = User::factory()->create(['role' => 'staff']);
    $staff2 = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff1)->delete(route('admin.deleteStaff', $staff2->id));

    $response->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $staff2->id,
    ]);
});

test('unclosed sessions on previous days automatically clock out at 18:00', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $pastAttendance = Attendance::create([
        'user_id' => $staff->id,
        'date' => Carbon::yesterday()->toDateString(),
        'clock_in' => '11:23:00',
        'clock_out' => null,
    ]);

    $this->actingAs($staff)->get(route('dashboard'));

    $pastAttendance->refresh();
    expect($pastAttendance->clock_out)->toBe('18:00:00');
});

test('auto clock out artisan command executes successfully', function () {
    $this->artisan('attendance:auto-clock-out')->assertSuccessful();
});

test('staff and admin can export monthly attendance report to PDF using TCPDF', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'name' => 'Ali bin Abu',
        'staff_id' => 'STF101',
    ]);

    Attendance::create([
        'user_id' => $staff->id,
        'date' => Carbon::now()->toDateString(),
        'clock_in' => '08:00:00',
        'clock_out' => '17:00:00',
    ]);

    $response = $this->actingAs($staff)->get(route('report.exportPdf', [
        'month' => Carbon::now()->month,
        'year' => Carbon::now()->year,
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});
