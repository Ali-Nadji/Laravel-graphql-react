<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableClientTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_template', function (Blueprint $table) {
            $table->increments('uuid')->unique();
            $table->string('client_uuid');
            $table->string('type');
            $table->json('data');

            $table->foreign('client_uuid')->references('uuid')->on('client');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_template');
    }
}
