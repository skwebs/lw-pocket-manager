<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $fillable = ['name', 'is_inflow', 'is_outflow'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    protected $casts = [
        'is_inflow' => 'boolean',
        'is_outflow' => 'boolean',
    ];
}
