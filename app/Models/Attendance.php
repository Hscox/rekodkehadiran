<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'remarks',
        'is_absent',
        'session_type',
        'status',
        'proposed_clock_out',
        'appeal_reason',
        'approved_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function isLate(): bool
    {
        if (! $this->clock_in || $this->is_absent) {
            return false;
        }

        $time = Carbon::parse($this->clock_in)->format('H:i:s');

        return $time > '08:15:00';
    }
}
