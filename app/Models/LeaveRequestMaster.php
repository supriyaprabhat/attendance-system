<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LeaveRequestMaster extends Model
{
    use HasFactory;

    protected $table = 'leave_requests_master';

    protected $fillable = [

        'leave_type_id',
        'no_of_days',
        'leave_requested_date',
        'leave_from',
        'leave_to',
        'reasons',
        'status',
        'admin_remark',
        'company_id',
        'requested_by',
        'early_exit',
        'request_updated_by',
        'referred_by',
        'start_time',
        'end_time',
        'title'
    ];

    const RECORDS_PER_PAGE = 10;

    const STATUS = ['pending','approved','rejected','cancelled'];

    public static function boot()
    {
        parent::boot();

//        static::creating(function ($model) {
//            $model->requested_by = Auth::user()->id;
//        });

        static::updating(function ($model) {
            $model->request_updated_by = Auth::user()->id;
        });
    }

    public function leaveRequestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    public function leaveRequestUpdatedBy()
    {
        return $this->belongsTo(User::class, 'request_updated_by', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'id');
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by', 'id');
    }

}
