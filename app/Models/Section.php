<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'grade_level',
        'classroom_id',
        'teacher_id',
        'school_year_id',
        'status',
    ];

    public function classroom() { return $this->belongsTo(Classroom::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function schoolYear() { return $this->belongsTo(SchoolYear::class); }
    public function enrollments() { return $this->hasMany(Enrollment::class); }
}
