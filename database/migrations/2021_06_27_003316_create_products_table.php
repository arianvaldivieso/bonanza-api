<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('tax:product_type');
            $table->string('post_type')->default('product');
            $table->string('post_status')->default('publish');
            $table->string('post_title');
            $table->date('post_date');
            $table->text('post_content');
            $table->string('visibility');
            $table->string('sku');
            $table->float('regular_price');
            $table->float('sale_price');
            $table->string('manage_stock')->default('yes');
            $table->string('stock');
            $table->string('images');
            $table->string('tax:product_cat');
            $table->string('extra');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
