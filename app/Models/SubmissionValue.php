<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SubmissionValue extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'form_field_id',
        'field_label',
        'value_text',
        'value_json',
    ];

    protected $casts = [
        'value_json' => 'array',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function field()
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
