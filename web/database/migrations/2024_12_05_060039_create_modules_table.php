<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id'); // Foreign Key to the shop table
            $table->unsignedBigInteger('general_module_id'); // Foreign Key to the modules table
            $table->json('custom_settings')->nullable(); // Customizable settings specific to the shop
            $table->timestamps();

            $table->foreign('general_module_id')->references('id')->on('general_modules')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modules');
    }
}
