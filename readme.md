# Using Laravel 5.5 Resources to create {JSON:API} formatted 


## Introducing Laravel 5.5 Resource Classes
We now have Resource classes we can use for our APIs out of the box in Laravel 5.5 without having to install any 3rd party packages.


## What are Resource Classes and why should I use it?
A Resource class is a way to transform data from one format to another. Simply put, if we have a Article model and we want to manipulate the data or remove certain fields from the model before sending that in our response, it is difficult to do so by just responding with;

```
<?php
namespace App\Http\Controllers;
use App\Article;
class ArticleController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \App\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(Article $article)
    {
        return $article->toArray();
    }
}
```


>Granted this will give you a valid response, maybe something like this

```
{
  "id": 1,
  "title": "JSON API paints my bikeshed!",
}
```

But what if you wanted to format your responses to a standard like JSON:API or any one of the other numerous standards out there. We would need to override the Model’s `toArray` implementation and now we are breaking the single responsibility rule. Why should the model care how to format a response to the end user? It shouldn’t! and this is where we can now utilise Resource classes in Laravel 5.5. Instead of doing what we did above we can now create a Laravel Resource using `artisan` and transform our model in any way we like.

For the rest of this article I’m going to show you how to utilise Laravel’s new Resource classes to implement the example on the [JSON:API specification home page](http://jsonapi.org/), which looks something like this;

```
{
  "links": {
    "self": "http://example.com/articles",
    "next": "http://example.com/articles?page[offset]=2",
    "last": "http://example.com/articles?page[offset]=10"
  },
  "data": [{
    "type": "articles",
    "id": "1",
    "attributes": {
      "title": "JSON API paints my bikeshed!"
    },
    "relationships": {
      "author": {
        "links": {
          "self": "http://example.com/articles/1/relationships/author",
          "related": "http://example.com/articles/1/author"
        },
        "data": { "type": "people", "id": "9" }
      },
      "comments": {
        "links": {
          "self": "http://example.com/articles/1/relationships/comments",
          "related": "http://example.com/articles/1/comments"
        },
        "data": [
          { "type": "comments", "id": "5" },
          { "type": "comments", "id": "12" }
        ]
      }
    },
    "links": {
      "self": "http://example.com/articles/1"
    }
  }],
  "included": [{
    "type": "people",
    "id": "9",
    "attributes": {
      "first-name": "Dan",
      "last-name": "Gebhardt",
      "twitter": "dgeb"
    },
    "links": {
      "self": "http://example.com/people/9"
    }
  }, {
    "type": "comments",
    "id": "5",
    "attributes": {
      "body": "First!"
    },
    "relationships": {
      "author": {
        "data": { "type": "people", "id": "2" }
      }
    },
    "links": {
      "self": "http://example.com/comments/5"
    }
  }, {
    "type": "comments",
    "id": "12",
    "attributes": {
      "body": "I like XML better"
    },
    "relationships": {
      "author": {
        "data": { "type": "people", "id": "9" }
      }
    },
    "links": {
      "self": "http://example.com/comments/12"
    }
  }]
}
```


# Create our Laravel Application

First things first we need a test app to work with;

```
composer create-project --prefer-dist laravel/laravel laravelapiresources
```

Bear with me as I’ll try to fly through the boring setup parts as fast as possible. I’m also going to assume you know how to connect to a database and setup your environment, etc so that I won’t have to go through that in this article.

Continuing…

# Generate our database and models

First things first we need some data so lets make some migrations.

```
php artisan make:model Article -crmf
php artisan make:model People -mf
php artisan make:model Comment -mf
```

As this is a tutorial we don’t want to waste time on the boring stuff so this command creates the model, resource controller (cr), migration (m) and factory (f) all in one go. Woohoo!

Lets fill these up with some boiler plate so we can get to the good stuff.

Open your migration files and add;


```
<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreatePeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('people', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('twitter');
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
        Schema::dropIfExists('people');
    }
}
```

```
<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('author_id')->unsigned();
            $table->string('title');
            $table->timestamps();
            $table->foreign('author_id')->references('id')->on('people');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
```

```
<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->unsigned();
            $table->integer('author_id')->unsigned();
            $table->text('body');
            $table->timestamps();
            $table->foreign('article_id')->references('id')->on('articles');
            $table->foreign('author_id')->references('id')->on('people');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comments');
    }
}
```

Now that you have the migrations lets migrate;

```
php artisan migrate
```

You also have factory classes available to seed some test data but I’ll leave that up to you to fill in the blanks for those.

# Laravel Factory Example

```
<?php

use Faker\Generator as Faker;

$factory->define(App\Comment::class, function (Faker $faker) {
    return [
        'article_id' => function() { return factory(App\Article::class)->create()->id; },
        'author_id' => function() { return factory(App\People::class)->create()->id; },
        'body' => $faker->paragraph
 
    ];
});
```



