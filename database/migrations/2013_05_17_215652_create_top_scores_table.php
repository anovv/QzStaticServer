<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTopScoresTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('top_scores', function(Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('name');
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
