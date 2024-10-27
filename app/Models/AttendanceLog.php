<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $table = 'attendance_logs';
    public $timestamps = true;

    protected $fillable = [
        'employee_id', 'attendance_type', 'identifier','created_at','updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }
}
