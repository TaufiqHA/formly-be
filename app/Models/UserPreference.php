<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class UserPreference extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'notif_email_new_order',
        'notif_wa_auto_confirm',
        'theme',
    ];

    protected $casts = [
        'notif_email_new_order' => 'boolean',
        'notif_wa_auto_confirm' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
