<?php

use FrenchFrogs\Laravel\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddColumnInClientWeekMetrics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('client_metric_jmaker', function ($table) {
            $table->integer('totalPrescriber')->default(0);
            $table->integer('activePrescriberLast30Days')->default(0);
            $table->integer('invitationCountLast30Days')->default(0);
        });

        Schema::table('client_metric_jmaker_history', function ($table) {
            $table->integer('totalPrescriber')->default(0)->after('trim_avg_delay_between_invit_active');
            $table->integer('activePrescriberLast30Days')->default(0)->after('totalPrescriber');
            $table->integer('invitationCountLast30Days')->default(0)->after('activePrescriberLast30Days');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_metric_jmaker', function ($table) {
            $table->dropColumn('totalPrescriber');
            $table->dropColumn('activePrescriberLast30Days');
            $table->dropColumn('invitationCountLast30Days');
        });

        Schema::table('client_metric_jmaker_history', function ($table) {
            $table->dropColumn('totalPrescriber');
            $table->dropColumn('activePrescriberLast30Days');
            $table->dropColumn('invitationCountLast30Days');
        });

    }
}
