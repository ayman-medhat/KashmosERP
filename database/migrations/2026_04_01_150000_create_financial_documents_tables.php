<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_no')->unique();
            $table->foreignId('sales_delivery_id')->unique()->constrained('sales_deliveries');
            $table->foreignId('sales_order_id')->constrained('sales_orders');
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('invoice_date')->index();
            $table->date('due_date')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);
            $table->decimal('paid_total', 19, 4)->default(0);
            $table->json('notes_translations')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sales_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('sales_delivery_item_id')->unique()->constrained('sales_delivery_items');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 19, 6);
            $table->decimal('unit_price', 19, 4);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('line_tax', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('sales_receipts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('receipt_no')->unique();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices');
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('receipt_date')->index();
            $table->string('status')->default('confirmed')->index();
            $table->decimal('amount', 19, 4);
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_no', 100)->nullable()->index();
            $table->json('notes_translations')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('supplier_bills', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('bill_no')->unique();
            $table->foreignId('purchase_receipt_id')->unique()->constrained('purchase_receipts');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('bill_date')->index();
            $table->date('due_date')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);
            $table->decimal('paid_total', 19, 4)->default(0);
            $table->json('notes_translations')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_bill_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_bill_id')->constrained('supplier_bills')->cascadeOnDelete();
            $table->foreignId('purchase_receipt_item_id')->unique()->constrained('purchase_receipt_items');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 19, 6);
            $table->decimal('unit_cost', 19, 4);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('line_tax', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('payment_no')->unique();
            $table->foreignId('supplier_bill_id')->constrained('supplier_bills');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('payment_date')->index();
            $table->string('status')->default('confirmed')->index();
            $table->decimal('amount', 19, 4);
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_no', 100)->nullable()->index();
            $table->json('notes_translations')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_bill_items');
        Schema::dropIfExists('supplier_bills');
        Schema::dropIfExists('sales_receipts');
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
    }
};

