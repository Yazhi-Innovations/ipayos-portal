<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NccTransaction extends Model
{
    use HasFactory;

    protected $table = 'ncc_transaction';

    protected $guarded = [];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getStatusAttribute(): string
    {
        return match ($this->ncc_settlement_status) {
            1 => 'Settled',
            0 => 'Unsettled',
            default => 'Unknown',
        };
    }
}

