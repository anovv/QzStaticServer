<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewRequests extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('requests', function(Blueprint $table)
        {
            $table->string('id')->primary();
            $table->bigInteger('aid')->index();
            $table->bigInteger('bid')->index();
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
