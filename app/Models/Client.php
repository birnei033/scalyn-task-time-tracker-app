<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'company',
        'status',
        'budget_per_month',
        'notes',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'budget_per_month' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function timeEntries()
    {
        return $this->hasManyThrough(TimeEntry::class, Task::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived')->whereNotNull('archived_at');
    }

    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'status' => 'active',
            'archived_at' => null,
        ]);
    }
}
