<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePrescriberTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prescriber_template', function (Blueprint $table) {
            $table->increments('uuid')->unique();
            $table->string('prescriber_uuid');
            $table->string('type');
            $table->json('data');

            $table->foreign('prescriber_uuid')->references('uuid')->on('prescriber');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prescriber_template');
    }
}
