<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_type_id')->constrained('transaction_types')->onDelete('cascade');
            $table->foreignId('from_account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('description', 255)->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->boolean('is_loan_repayment')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
