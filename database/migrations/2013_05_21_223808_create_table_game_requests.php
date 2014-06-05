<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableGameRequests extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('game_requests', function(Blueprint $table)
        {
            $table->bigInteger('rid');//TODO unique
            $table->bigInteger('id');
            $table->bigInteger('theme_id');
            $table->string('theme_name');
            $table->string('username');
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
