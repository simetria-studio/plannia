<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    protected $fillable = [
        'student_id',
        'school_id',
        'created_by',
        'approved_by',
        'type',
        'format',
        'file_path',
        'ai_content',
        'extracted_sources',
        'status',
        'shared_via_email_at',
        'shared_via_whatsapp_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'ai_content' => 'array',
            'extracted_sources' => 'array',
            'shared_via_email_at' => 'datetime',
            'shared_via_whatsapp_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === DocumentStatus::Aprovado;
    }

    public function isPending(): bool
    {
        return $this->status === DocumentStatus::Pendente;
    }
}
