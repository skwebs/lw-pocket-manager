<?php
namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class TransactionService
{
    // public function createTransaction(array $data)
    // {
    //     // Validate that at least one account is provided
    //     $validator = Validator::make($data, [
    //         'from_account_id' => 'required_without:to_account_id|nullable|exists:accounts,id',
    //         'to_account_id' => 'required_without:from_account_id|nullable|exists:accounts,id',
    //         'transaction_type_id' => 'required|exists:transaction_types,id',
    //         'amount' => 'required|numeric|min:0.01',
    //         'description' => 'nullable|string|max:255',
    //         'is_loan_repayment' => 'boolean',
    //         'transaction_date' => 'nullable|date',
    //     ]);

    //     if ($validator->fails()) {
    //         throw new ValidationException($validator);
    //     }

    //     return DB::transaction(function () use ($data) {
    //         $transaction = Transaction::create($data);

    //         // Update balances
    //         if ($transaction->from_account_id) {
    //             $fromAccount = Account::find($transaction->from_account_id);
    //             $fromAccount->balance -= $transaction->amount;
    //             $fromAccount->save();
    //         }

    //         if ($transaction->to_account_id) {
    //             $toAccount = Account::find($transaction->to_account_id);
    //             $toAccount->balance += $transaction->amount;
    //             $toAccount->save();
    //         }

    //         return $transaction;
    //     });
    // }


    public function createTransaction(array $data)
{
    $transactionType = TransactionType::findOrFail($data['transaction_type_id']);

    $rules = [
        'transaction_type_id' => 'required|exists:transaction_types,id',
        'amount' => 'required|numeric|min:0.01',
        'description' => 'nullable|string|max:255',
        'is_loan_repayment' => 'boolean',
        'transaction_date' => 'nullable|date',
    ];

    // Adjust rules based on transaction type
    if ($transactionType->name === 'Income') {
        $rules['to_account_id'] = 'required|exists:accounts,id';
        $rules['from_account_id'] = 'prohibited';
    } elseif ($transactionType->name === 'Expense') {
        $rules['from_account_id'] = 'required|exists:accounts,id';
        $rules['to_account_id'] = 'prohibited';
    } else {
        // For Transfer, Loan, etc., at least one account is required
        $rules['from_account_id'] = 'required_without:to_account_id|nullable|exists:accounts,id';
        $rules['to_account_id'] = 'required_without:from_account_id|nullable|exists:accounts,id';
    }

    $validator = Validator::make($data, $rules);

    if ($validator->fails()) {
        throw new ValidationException($validator);
    }

    return DB::transaction(function () use ($data) {
        $transaction = Transaction::create($data);

        // Update balances
        if ($transaction->from_account_id) {
            $fromAccount = Account::find($transaction->from_account_id);
            $fromAccount->balance -= $transaction->amount;
            $fromAccount->save();
        }

        if ($transaction->to_account_id) {
            $toAccount = Account::find($transaction->to_account_id);
            $toAccount->balance += $transaction->amount;
            $toAccount->save();
        }

        return $transaction;
    });
}
}
