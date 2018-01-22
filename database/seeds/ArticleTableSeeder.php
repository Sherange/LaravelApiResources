<?php

use Illuminate\Database\Seeder;
use App\Article as Article;

class ArticleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Clear Data in Article Table
        Article::truncate();

        factory(Article::class, 10)->create();
    }
}
