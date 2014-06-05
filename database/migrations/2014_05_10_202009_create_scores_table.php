<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScoresTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
    {
        Schema::create('scores', function(Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->bigInteger('theme_id');
            $table->bigInteger('score');
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
