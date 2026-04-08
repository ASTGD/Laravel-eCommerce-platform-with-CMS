<?php

namespace Platform\PlatformSupport\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'actor_type',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'payload_json',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
        ];
    }
}
