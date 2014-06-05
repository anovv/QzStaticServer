<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewTopScores extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
    {
        Schema::create('top_scores', function(Blueprint $table)
        {
            $table->string('id')->primary();
            $table->bigInteger('user_id')->index();
            $table->bigInteger('theme_id')->index();
            $table->bigInteger('score');
            $table->string('name');
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
