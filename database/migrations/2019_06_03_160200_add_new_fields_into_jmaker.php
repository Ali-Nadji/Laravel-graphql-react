<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsIntoJmaker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jmaker', function (Blueprint $table) {
            $table->string('client_uuid',32)->nullable()->after("uuid");
            $table->string('prescriber_uuid',32)->nullable()->after("client_uuid");
            $table->string('campaign_uuid',32)->nullable()->after("prescriber_uuid");
            $table->string('contract_uuid',32)->nullable()->after("campaign_uuid");

            $table->foreign('client_uuid')->references('uuid')->on('client');
            $table->foreign('prescriber_uuid')->references('uuid')->on('prescriber');
            $table->foreign('campaign_uuid')->references('uuid')->on('client_campaign');
            $table->foreign('contract_uuid')->references('uuid')->on('client_contract');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jmaker', function (Blueprint $table) {
            $table->dropColumn('client_uuid');
            $table->dropColumn('prescriber_uuid');
            $table->dropColumn('campaign_uuid');
            $table->dropColumn('contract_uuid');
        });
    }
}
