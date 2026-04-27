<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'section_id',
        'school_year_id',
        'status',
        'enrolled_at',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function section() { return $this->belongsTo(Section::class); }
    public function schoolYear() { return $this->belongsTo(SchoolYear::class); }
}
