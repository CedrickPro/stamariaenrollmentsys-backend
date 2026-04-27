<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = ['year_label', 'is_active', 'year', 'start_date', 'end_date', 'status'];

    protected $casts = ['is_active' => 'boolean'];

    public function sections() { return $this->hasMany(Section::class); }
    public function enrollments() { return $this->hasMany(Enrollment::class); }

    // Activate this SY and deactivate all others
    public function activate()
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }
}
