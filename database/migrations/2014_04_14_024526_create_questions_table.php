<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('questions_1', function(Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->longText('question');
            $table->boolean('has_img');
            $table->longText('ans1');
            $table->longText('ans2');
            $table->longText('ans3');
            $table->longText('ans4');
            $table->integer('right_ans');
            $table->string('img_url');
            $table->timestamps();

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
