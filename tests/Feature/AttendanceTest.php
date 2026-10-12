<?php

use App\Http\Controllers\AttendanceController;
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
    $response->assertSee('AHMAD ALBAB');
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
        'name' => 'SITI AMINAH',
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

test('unclosed sessions on previous days automatically change status to pending_approval', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $pastAttendance = Attendance::create([
        'user_id' => $staff->id,
        'date' => Carbon::yesterday()->toDateString(),
        'clock_in' => '11:23:00',
        'clock_out' => null,
    ]);

    $response = $this->actingAs($staff)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Anda tidak clock out semalam, sila nyatakan masa anda pulang dan sebab');

    $pastAttendance->refresh();
    expect($pastAttendance->status)->toBe('pending_approval');
    expect($pastAttendance->clock_out)->toBeNull();
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

test('admin can view edit staff form', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);
    $staff = User::factory()->create([
        'role' => 'staff',
        'name' => 'Siti Aminah',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.editStaff', $staff->id));

    $response->assertOk();
    $response->assertSee('SITI AMINAH');
});

test('admin can update staff name and password', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);
    $staff = User::factory()->create([
        'role' => 'staff',
        'name' => 'Ahmad Albab',
        'password' => 'oldpassword123',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.updateStaff', $staff->id), [
        'name' => 'Ahmad Albab Updated',
        'password' => 'newpassword123',
    ]);

    $response->assertRedirect(route('admin.staffList'));
    $response->assertSessionHas('success');

    $staff->refresh();
    expect($staff->name)->toBe('AHMAD ALBAB UPDATED');
    expect(Hash::check('newpassword123', $staff->password))->toBeTrue();
});

test('admin can update staff name without changing password', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);
    $staff = User::factory()->create([
        'role' => 'staff',
        'name' => 'Ahmad Albab',
        'password' => 'oldpassword123',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.updateStaff', $staff->id), [
        'name' => 'ahmad albab new name',
        'password' => '',
    ]);

    $response->assertRedirect(route('admin.staffList'));
    $response->assertSessionHas('success');

    $staff->refresh();
    expect($staff->name)->toBe('AHMAD ALBAB NEW NAME');
    expect(Hash::check('oldpassword123', $staff->password))->toBeTrue();
});

test('staff cannot edit or update other staff accounts', function () {
    $staff1 = User::factory()->create([
        'role' => 'staff',
    ]);
    $staff2 = User::factory()->create([
        'role' => 'staff',
        'name' => 'Original Name',
    ]);

    $response = $this->actingAs($staff1)->get(route('admin.editStaff', $staff2->id));
    $response->assertForbidden();

    $response = $this->actingAs($staff1)->put(route('admin.updateStaff', $staff2->id), [
        'name' => 'Hacked Name',
    ]);
    $response->assertForbidden();
});

test('staff name and staff_id are automatically uppercase even if entered in lowercase', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->post('/admin/create-staff', [
        'staff_id' => 'stf009',
        'name' => 'aisyah malik',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('admin.staffList'));
    $user = User::where('staff_id', 'STF009')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('AISYAH MALIK');
    expect($user->staff_id)->toBe('STF009');
});

test('admin can view dedicated staff list page', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);
    $staff = User::factory()->create([
        'role' => 'staff',
        'name' => 'Kamal Adli',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.staffList'));
    $response->assertOk();
    $response->assertSee('Senarai Kakitangan Berdaftar');
    $response->assertSee('KAMAL ADLI');
});

test('non-admin cannot access admin staff list page', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
    ]);

    $response = $this->actingAs($staff)->get(route('admin.staffList'));
    $response->assertForbidden();
});

test('admin staff list is paginated when there are more than 10 staff', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    User::factory()->count(15)->create([
        'role' => 'staff',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.staffList'));
    $response->assertOk();
    $response->assertSee('Menunjukkan');
    $response->assertSee('daripada 15 staf');
});

test('monthly report completes all days of the month with zero score for days without clock in', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
    ]);

    Attendance::create([
        'user_id' => $staff->id,
        'date' => '2026-02-15',
        'clock_in' => '08:00:00',
        'clock_out' => '17:00:00',
    ]);

    $response = $this->actingAs($staff)->get(route('report', [
        'month' => 2,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertViewHas('reportData', function ($reportData) {
        return count($reportData) === 28
            && $reportData[0]['hb'] === 1
            && $reportData[0]['keterangan'] === 'Tidak hadir'
            && $reportData[0]['skor'] === '0.0'
            && $reportData[14]['hb'] === 15
            && $reportData[14]['keterangan'] === 'Hadir'
            && $reportData[14]['skor'] === '1.0'
            && $reportData[27]['hb'] === 28;
    });
});

test('staff can submit appeal for unclosed session from previous day', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $attendance = Attendance::create([
        'user_id' => $staff->id,
        'date' => Carbon::yesterday()->toDateString(),
        'clock_in' => '08:00:00',
        'clock_out' => null,
        'status' => 'pending_approval',
    ]);

    $response = $this->actingAs($staff)->post(route('attendance.submitAppeal', $attendance->id), [
        'proposed_clock_out' => '16:00',
        'appeal_reason' => 'Terlupa clock out kerana bergegas balik',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $attendance->refresh();
    expect($attendance->proposed_clock_out)->toBe('16:00:00');
    expect($attendance->appeal_reason)->toBe('Terlupa clock out kerana bergegas balik');
});

test('admin can view pending appeals list', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = User::factory()->create(['role' => 'staff', 'name' => 'John Doe']);

    Attendance::create([
        'user_id' => $staff->id,
        'date' => Carbon::yesterday()->toDateString(),
        'clock_in' => '08:00:00',
        'status' => 'pending_approval',
        'proposed_clock_out' => '16:00:00',
        'appeal_reason' => 'Terlupa clock out',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.appeals'));
    $response->assertOk();
    $response->assertSee('Pengesahan Keluar Kakitangan');
    $response->assertSee('JOHN DOE');
    $response->assertSee('Terlupa clock out');
});

test('admin can approve appeal and clock_out is updated with score calculated', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = User::factory()->create(['role' => 'staff']);

    $attendance = Attendance::create([
        'user_id' => $staff->id,
        'date' => '2026-03-10',
        'clock_in' => '08:00:00',
        'status' => 'pending_approval',
        'proposed_clock_out' => '16:00:00',
        'appeal_reason' => 'Balik jam 4 petang',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.approveAppeal', $attendance->id));
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $attendance->refresh();
    expect($attendance->status)->toBe('approved');
    expect($attendance->clock_out)->toBe('16:00:00');
    expect($attendance->approved_by)->toBe($admin->id);

    $reportResponse = $this->actingAs($staff)->get(route('report', [
        'month' => 3,
        'year' => 2026,
    ]));

    $reportResponse->assertOk();
    $reportResponse->assertViewHas('reportData', function ($reportData) {
        $day10 = $reportData[9];

        return $day10['hb'] === 10
            && $day10['keterangan'] === 'Disahkan Admin'
            && $day10['jumlah_masa'] === '08:00'
            && $day10['skor'] === '1.0';
    });
});

test('admin can reject appeal and status becomes rejected with zero score', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = User::factory()->create(['role' => 'staff']);

    $attendance = Attendance::create([
        'user_id' => $staff->id,
        'date' => '2026-03-10',
        'clock_in' => '08:00:00',
        'status' => 'pending_approval',
        'proposed_clock_out' => '16:00:00',
        'appeal_reason' => 'Balik jam 4 petang',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.rejectAppeal', $attendance->id));
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $attendance->refresh();
    expect($attendance->status)->toBe('rejected');
    expect($attendance->approved_by)->toBe($admin->id);

    $reportResponse = $this->actingAs($staff)->get(route('report', [
        'month' => 3,
        'year' => 2026,
    ]));

    $reportResponse->assertOk();
    $reportResponse->assertViewHas('reportData', function ($reportData) {
        $day10 = $reportData[9];

        return $day10['hb'] === 10
            && $day10['keterangan'] === 'Ditolak Admin'
            && $day10['jumlah_masa'] === '00:00'
            && $day10['skor'] === '0.0';
    });
});

test('staff cannot submit appeal with empty reason or dash characters', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $attendance = Attendance::create([
        'user_id' => $staff->id,
        'date' => Carbon::yesterday()->toDateString(),
        'clock_in' => '08:00:00',
        'status' => 'pending_approval',
    ]);

    $response = $this->actingAs($staff)->post(route('attendance.submitAppeal', $attendance->id), [
        'proposed_clock_out' => '16:00',
        'appeal_reason' => '-',
    ]);

    $response->assertSessionHasErrors('appeal_reason');

    $response2 = $this->actingAs($staff)->post(route('attendance.submitAppeal', $attendance->id), [
        'proposed_clock_out' => '16:00',
        'appeal_reason' => '   ---   ',
    ]);

    $response2->assertSessionHasErrors('appeal_reason');
});

test('admin appeals page displays past records and history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = User::factory()->create(['role' => 'staff', 'name' => 'Siti Nurhaliza']);

    Attendance::create([
        'user_id' => $staff->id,
        'date' => '2026-03-05',
        'clock_in' => '08:00:00',
        'clock_out' => '16:00:00',
        'proposed_clock_out' => '16:00:00',
        'appeal_reason' => 'Ada kecemasan keluarga di kampung',
        'status' => 'approved',
        'approved_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.appeals'));
    $response->assertOk();
    $response->assertSee('Rekod Lepas Pengesahan Keluar');
    $response->assertSee('SITI NURHALIZA');
    $response->assertSee('Ada kecemasan keluarga di kampung');
    $response->assertSee('Disahkan');
});

test('monthly report leaves future dates blank with dash instead of tidak hadir', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $futureMonth = Carbon::now()->addMonths(2);

    $response = $this->actingAs($staff)->get(route('report', [
        'month' => $futureMonth->month,
        'year' => $futureMonth->year,
    ]));

    $response->assertOk();
    $response->assertViewHas('reportData', function ($reportData) {
        return $reportData[0]['keterangan'] === '-'
            && $reportData[0]['jumlah_masa'] === '-'
            && $reportData[0]['skor'] === '-';
    });
});

test('auto detects daytime and nighttime sessions correctly', function () {
    expect(AttendanceController::detectSessionType('06:00:00'))->toBe('siang')
        ->and(AttendanceController::detectSessionType('12:00:00'))->toBe('siang')
        ->and(AttendanceController::detectSessionType('18:30:00'))->toBe('siang')
        ->and(AttendanceController::detectSessionType('18:30:01'))->toBe('malam')
        ->and(AttendanceController::detectSessionType('23:00:00'))->toBe('malam')
        ->and(AttendanceController::detectSessionType('03:00:00'))->toBe('malam')
        ->and(AttendanceController::detectSessionType('05:59:59'))->toBe('malam');
});

test('clock in auto detects and records session_type', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    // Daytime clock in at 09:00 AM
    Carbon::setTestNow('2026-10-12 09:00:00');
    $responseDay = $this->actingAs($staff)->post(route('clock.in'));
    $responseDay->assertSessionHas('success');

    $dayAttendance = Attendance::where('user_id', $staff->id)->first();
    expect($dayAttendance->session_type)->toBe('siang');

    // Clock out daytime session
    $this->actingAs($staff)->post(route('clock.out'));

    // Nighttime clock in at 11:00 PM (23:00)
    Carbon::setTestNow('2026-10-12 23:00:00');
    $responseNight = $this->actingAs($staff)->post(route('clock.in'));
    $responseNight->assertSessionHas('success');

    $nightAttendance = Attendance::where('user_id', $staff->id)->latest('id')->first();
    expect($nightAttendance->session_type)->toBe('malam');

    Carbon::setTestNow();
});

test('scenario 2: staff clock in night session 11pm and clocks out 3am cross-midnight', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    // Night shift starts at 11:00 PM on 2026-10-12
    Carbon::setTestNow('2026-10-12 23:00:00');
    $this->actingAs($staff)->post(route('clock.in'));

    $session = Attendance::where('user_id', $staff->id)->first();
    expect($session->date)->toBe('2026-10-12')
        ->and($session->clock_in)->toBe('23:00:00')
        ->and($session->session_type)->toBe('malam')
        ->and($session->clock_out)->toBeNull();

    // At 3:00 AM next day (2026-10-13), session is still active and visible on dashboard
    Carbon::setTestNow('2026-10-13 03:00:00');
    $dashResponse = $this->actingAs($staff)->get(route('dashboard'));
    $dashResponse->assertOk();
    $dashResponse->assertSee('Aktif bertugas');
    $dashResponse->assertSee('Sesi Malam');

    // Staff clocks out at 3:00 AM
    $outResponse = $this->actingAs($staff)->post(route('clock.out'));
    $outResponse->assertSessionHas('success');

    $session->refresh();
    expect($session->clock_out)->toBe('03:00:00');

    // Check monthly report calculation for this cross-midnight shift (23:00 to 03:00 = 4 hours)
    $reportResponse = $this->actingAs($staff)->get(route('report', [
        'month' => 10,
        'year' => 2026,
    ]));

    $reportResponse->assertOk();
    $reportResponse->assertViewHas('reportData', function ($reportData) {
        $row12 = collect($reportData)->firstWhere('hb', 12);

        return $row12['jumlah_masa'] === '04:00'
            && $row12['skor'] === '0.5'
            && $row12['sessions'][0]['keluar'] === '03:00 AM';
    });

    Carbon::setTestNow();
});

test('night shift does not expire before 6am but expires after 6am', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    // Night shift starts at 11:00 PM on 2026-10-12
    Carbon::setTestNow('2026-10-12 23:00:00');
    $this->actingAs($staff)->post(route('clock.in'));

    // Check at 04:00 AM on 2026-10-13 -> not expired
    Carbon::setTestNow('2026-10-13 04:00:00');
    AttendanceController::autoClockOutForgottenSessions();

    $att = Attendance::where('user_id', $staff->id)->first();
    expect($att->status)->toBe('normal');

    // Check at 06:15 AM on 2026-10-13 -> expired (pending_approval)
    Carbon::setTestNow('2026-10-13 06:15:00');
    AttendanceController::autoClockOutForgottenSessions();

    $att->refresh();
    expect($att->status)->toBe('pending_approval');

    Carbon::setTestNow();
});

test('monthly report can filter by session type siang and malam', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    // Day session on 2026-10-05
    Attendance::create([
        'user_id' => $staff->id,
        'date' => '2026-10-05',
        'clock_in' => '08:00:00',
        'clock_out' => '17:00:00',
        'session_type' => 'siang',
        'status' => 'normal',
    ]);

    // Night session on 2026-10-06
    Attendance::create([
        'user_id' => $staff->id,
        'date' => '2026-10-06',
        'clock_in' => '23:00:00',
        'clock_out' => '03:00:00',
        'session_type' => 'malam',
        'status' => 'normal',
    ]);

    // Filter by malam
    $responseMalam = $this->actingAs($staff)->get(route('report', [
        'month' => 10,
        'year' => 2026,
        'session_type' => 'malam',
    ]));

    $responseMalam->assertOk();
    $responseMalam->assertViewHas('reportData', function ($reportData) {
        $row5 = collect($reportData)->firstWhere('hb', 5);
        $row6 = collect($reportData)->firstWhere('hb', 6);

        // Row 5 has no night shift, so sessions show '-'
        return $row5['sessions'][0]['masuk'] === '-'
            && $row6['sessions'][0]['masuk'] === '11:00 PM';
    });
});

test('points are only calculated from clock in to clock out if admin approves appeal', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = User::factory()->create(['role' => 'staff']);

    // Staff clocked in at 8:00 AM on 10th Oct, forgot to clock out
    $attendance = Attendance::create([
        'user_id' => $staff->id,
        'date' => '2026-10-10',
        'clock_in' => '08:00:00',
        'clock_out' => null,
        'status' => 'pending_approval',
        'proposed_clock_out' => '16:00:00',
        'appeal_reason' => 'Terlupa clock out kerana kecemasan',
    ]);

    // 1. Before Admin approves (status: pending_approval): Score must be 0.0, time must be 00:00
    $pendingReport = $this->actingAs($staff)->get(route('report', ['month' => 10, 'year' => 2026]));
    $pendingReport->assertViewHas('reportData', function ($reportData) {
        $row10 = collect($reportData)->firstWhere('hb', 10);

        return $row10['skor'] === '0.0' && $row10['jumlah_masa'] === '00:00';
    });

    // 2. Admin approves the appeal
    $this->actingAs($admin)->post(route('admin.approveAppeal', $attendance->id));

    // 3. After Admin approves (status: approved): Score and time MUST be calculated from clock_in to clock_out (08:00 to 16:00 = 8 hours, score 1.0)
    $approvedReport = $this->actingAs($staff)->get(route('report', ['month' => 10, 'year' => 2026]));
    $approvedReport->assertViewHas('reportData', function ($reportData) {
        $row10 = collect($reportData)->firstWhere('hb', 10);

        return $row10['skor'] === '1.0'
            && $row10['jumlah_masa'] === '08:00'
            && $row10['keterangan'] === 'Disahkan Admin';
    });
});
