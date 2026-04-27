<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'lrn',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birth_date',
        'birth_place',
        'gender',
        'religion',
        'mother_tongue',
        'house_no',
        'street',
        'barangay',
        'city',
        'province',
        'country',
        'zip_code',
        'parent_id',
        'status',
        'grade_level',
        'previous_average',
        'preferred_adviser',
        'father_name',
        'father_contact',
        'mother_name',
        'mother_contact',
        'guardian_name',
        'guardian_contact',
        'ip_group',
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function medicalInfo()
    {
        return $this->hasOne(MedicalInfo::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }
}
