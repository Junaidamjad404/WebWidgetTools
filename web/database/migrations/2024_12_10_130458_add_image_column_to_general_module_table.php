<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageColumnToGeneralModuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_modules', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description'); // Add the image column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_modules', function (Blueprint $table) {
            $table->dropColumn('image'); // Drop the image column
        });
    }
}
