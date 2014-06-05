<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewScores extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('scores', function(Blueprint $table)
        {
            $table->string('id')->primary();
            $table->bigInteger('user_id')->index();
            $table->bigInteger('theme_id')->index();
            $table->bigInteger('score');
            $table->string('theme_name');
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
