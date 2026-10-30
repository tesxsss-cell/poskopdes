<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Alias barcode, selalu memakai harga master. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('barcode', 60)->unique();               
            $table->decimal('sell_price', 18, 2)->nullable();      
            $table->unsignedInteger('default_quantity')->default(1); 
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id']);
        });

        
    }
    public function down(): void { Schema::dropIfExists('product_barcodes'); }
};
