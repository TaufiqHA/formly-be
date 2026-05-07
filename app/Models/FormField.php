<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class FormField extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuids;

    protected $fillable = [
        'form_id',
        'label',
        'field_type',
        'placeholder',
        'is_required',
        'options',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
        'sort_order' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
