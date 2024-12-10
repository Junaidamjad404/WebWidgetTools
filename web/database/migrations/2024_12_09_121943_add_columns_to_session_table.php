<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToSessionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('payload');
            $table->string('last_activity');
            $table->string('ip_address');
            $table->string('user_agent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->drop('payload')->nullable();
            $table->drop('last_activity')->nullable();
            $table->drop('ip_address')->nullable();
            $table->drop('user_agent')->nullable();
        });
    }
}
