<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionsUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

        Schema::create('questions_users_1', function(Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->bigInteger('question_id');
            $table->longText('ans_seq');
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
