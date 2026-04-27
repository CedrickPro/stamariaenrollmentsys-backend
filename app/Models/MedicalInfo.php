<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalInfo extends Model
{
    use HasFactory;

    protected $table = 'medical_info';

    protected $fillable = [
        'student_id',
        'diagnosis',
        'manifestations',
        'pwd_id',
        'pwd_details',
        'weight',
        'height',
        'height_squared',
        'bmi_result',
        'bmi_category',
        'hfa',
        'medical_remarks',
    ];

    protected $casts = [
        'diagnosis' => 'array',
        'manifestations' => 'array',
        'pwd_id' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
