// database/migrations/2024_01_01_000003_create_vehicles_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('brand_id')->constrained('brands');
            $table->string('model');
            $table->string('type')->nullable();
            $table->integer('year');
            $table->decimal('price', 15, 2);
            $table->decimal('promo_price', 15, 2)->nullable();
            $table->string('condition')->default('baru'); // baru, bekas
            $table->string('transmission')->nullable(); // manual, otomatis
            $table->string('fuel_type')->nullable(); // bensin, diesel, listrik
            $table->string('engine_capacity')->nullable();
            $table->integer('kilometer')->default(0);
            $table->string('color')->nullable();
            $table->integer('stock')->default(1);
            $table->string('status')->default('tersedia'); // tersedia, booking, terjual
            $table->text('description');
            $table->text('features')->nullable();
            $table->string('main_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_promoted')->default(false);
            $table->integer('views')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
