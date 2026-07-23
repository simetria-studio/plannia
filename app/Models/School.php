<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class School extends Model
{
    protected $fillable = [
        'name',
        'address',
        'logo_path',
        'cnpj',
        'phone',
        'inep',
    ];

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function logoAbsolutePath(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        $path = Storage::disk('public')->path($this->logo_path);

        return is_file($path) ? $path : null;
    }

    public function logoDataUri(): ?string
    {
        $path = $this->logoAbsolutePath();

        if (! $path) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
