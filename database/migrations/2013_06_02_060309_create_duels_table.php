<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDuelsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('duels', function(Blueprint $table)
        {
            $table->string('id')->primary();
            $table->bigInteger('aid');
            $table->bigInteger('bid');
            $table->bigInteger('score1');
            $table->bigInteger('score2');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
	}

}
