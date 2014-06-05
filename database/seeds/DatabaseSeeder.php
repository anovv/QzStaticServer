<?php

class DatabaseSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{

		$this->call('QuestionsTableSeeder');
	}
}

class QuestionsTableSeeder extends Seeder {

    public function run()
    {
        DB::table('themes')->insert(array(
            'name' => 'General Questions',
            'description' => 'Test Your Might',
            'parent' => 1
        ));
        //DB::table('themes')->where('id', 1)->update(array('name' => 'General Knowledge'));
    }
}