<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditInvoiceItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_invoice_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('invoice_id')->constrained('credit_invoices')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('shift_payment_id')->nullable()->constrained('shift_payments')->onDelete('set null');

            // Item details
            $table->string('description');
            $table->date('service_date'); // Shift date
            $table->integer('quantity')->default(1); // Usually hours or 1 for per-shift
            $table->decimal('unit_price', 10, 2); // Price per unit
            $table->decimal('amount', 12, 2); // Total for this line item

            // Metadata
            $table->json('metadata')->nullable(); // Worker name, shift details, etc.

            $table->timestamps();

            // Indexes
            $table->index('invoice_id');
            $table->index('shift_id');
            $table->index('service_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_invoice_items');
    }
}
