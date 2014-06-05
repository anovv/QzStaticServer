<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewFriendsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('friends', function(Blueprint $table)
        {
            $table->string('id')->primary();
            $table->bigInteger('uid1');
            $table->bigInteger('uid2');
            $table->string('name1');
            $table->string('name2');
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
