<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';
    protected $fillable = ['user_id', 'subject', 'message', 'status', 'reply', 'replied_at', 'rating', 'translation', 'role'];

    public function user() { return $this->belongsTo(User::class); }
}
