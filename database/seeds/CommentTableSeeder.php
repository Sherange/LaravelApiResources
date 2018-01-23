<?php

use Illuminate\Database\Seeder;
use App\Comment as Comment;

class CommentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Clear Data in Comment Table
        Comment::truncate();

        factory(Comment::class, 10)->create();
    }
}
