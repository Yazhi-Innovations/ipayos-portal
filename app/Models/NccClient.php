<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NccClient extends Model
{
    protected $table = 'ncc_clients';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
