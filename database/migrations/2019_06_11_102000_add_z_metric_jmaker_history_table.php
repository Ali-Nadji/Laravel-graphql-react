<?php

use FrenchFrogs\Laravel\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddZMetricJmakerHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('z_metric_jmaker_history', function (\Illuminate\Database\Schema\Blueprint $table) {

            $table->string('uuid',32)->primary();
            $table->string('jmaker_uuid',32);

            $table->date('created_at');

            $table->string('jmaker_state');

            $table->integer('workshop_started');
            $table->integer('workshop_finished');

            $table->integer('step_finished');

            $table->integer('shared_ct');

            $table->foreign('jmaker_uuid')->references('uuid')->on('jmaker');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('z_metric_jmaker_history');
    }
}
