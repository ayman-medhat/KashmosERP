<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('delivery_no')->unique();
            $table->foreignId('sales_order_id')->constrained('sales_orders');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('delivery_date')->index();
            $table->string('status')->default('confirmed')->index();
            $table->json('notes_translations')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sales_delivery_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_delivery_id')->constrained('sales_deliveries')->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('ordered_qty', 19, 6);
            $table->decimal('delivered_qty', 19, 6);
            $table->decimal('unit_price', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);
            $table->timestamps();

            $table->index(['sales_order_item_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_delivery_items');
        Schema::dropIfExists('sales_deliveries');
    }
};
