<?php

use Illuminate\Database\Seeder;
use App\People as People;

class PeopleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        //Clear Data in People Table
        People::truncate();
        
        factory(People::class, 10)->create();
    }
}
