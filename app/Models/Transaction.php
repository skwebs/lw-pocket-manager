<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_type_id', 'from_account_id', 'to_account_id',
        'amount', 'description', 'transaction_date', 'is_loan_repayment'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($transaction) {
            $validator = Validator::make($transaction->toArray(), [
                'from_account_id' => 'required_without:to_account_id|nullable',
                'to_account_id' => 'required_without:from_account_id|nullable',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        });
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
