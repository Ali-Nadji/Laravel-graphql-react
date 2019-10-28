<?php

use FrenchFrogs\Laravel\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnSizeTableOperatorIhmFilterParam extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operator_ihm_filter_param', function ($table) {
            $table->text('param')->change();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //DoNothing
    }
}
