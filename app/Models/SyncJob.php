<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SyncJob extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'job_type',
        'status',
        'attempts',
        'last_error',
        'next_retry_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'next_retry_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
