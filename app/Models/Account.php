<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['name', 'account_type_id', 'balance'];

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function fromTransactions()
    {
        return $this->hasMany(Transaction::class, 'from_account_id');
    }

    public function toTransactions()
    {
        return $this->hasMany(Transaction::class, 'to_account_id');
    }
}
