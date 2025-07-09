<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'qobject',
        'status'
    ];

    protected $attributes = [
        'status' => 'QUEUE'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'enrollments_user_project_relateds');
    }
}
