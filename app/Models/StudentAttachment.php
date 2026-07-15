<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttachment extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'file_path',
        'original_name',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
