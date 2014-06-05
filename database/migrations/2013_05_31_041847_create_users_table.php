<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('users', function(Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->string('email')->index();
            $table->string('password');
            $table->boolean('login');
            $table->string('key');
            $table->bigInteger('wins');
            $table->bigInteger('total');
            $table->integer('is_available');
            $table->bigInteger('vk_id')->index();
            $table->string('fullname')->index();
            $table->longText('gcm_id');
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
