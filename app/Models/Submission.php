<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Submission extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuids;

    protected $fillable = [
        'form_id',
        'submission_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_company',
        'status',
        'wa_notif_sent',
        'wa_notif_sent_at',
        'ip_address',
        'submitted_at',
    ];

    protected $casts = [
        'wa_notif_sent' => 'boolean',
        'wa_notif_sent_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function values()
    {
        return $this->hasMany(SubmissionValue::class);
    }

    public function notes()
    {
        return $this->hasMany(SubmissionNote::class);
    }

    public function syncJobs()
    {
        return $this->hasMany(SyncJob::class);
    }
}
