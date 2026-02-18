<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketSupport extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tickets_support';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_resolution' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (! $ticket->numero_ticket) {
                $ticket->numero_ticket = 'TKT-'.now()->format('Ymd').'-'.str_pad(static::max('id') + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
