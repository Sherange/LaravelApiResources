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

# Laravel Seeder Example

```
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
```


# Building our JSON:API using Resource Classes

Now that we have our database and presumably you have seeded your tables with some fake data we can move on to the fun stuff and what you really came here for.

## A bit of Theory First
When dealing with resources and how to transform them into responses for the client we basically have 2 types, an item and a collection. An item resource as you might have guessed is basically a one to one representation of our model where as a collection is the representation of many items. Collections may also have meta data and other navigation information as well.


> In terms of JSON:API we can represent an item like this;


```
{
  "type": "articles",
  "id": "1",
  "attributes": {
    "title": "JSON API paints my bikeshed!"
  },
  "links": {
    "self": "http://example.com/api/articles/1"
  }
}
```

>Whereas a collection would be represented like this (in it’s basic form);


```
{
  "links": {
    "self": "http://example.com/articles",
    "first": "http://example.com/articles?page=1",
    "last": "http://example.com/articles?page=2",
    "prev": null,
    "next": "http://example.com/articles?page=2"
  },
  "data": [
    {
      "type": "articles",
      "id": "1",
      "attributes": {
        "title": "JSON API paints my bikeshed!"
      },
      "links": {
        "self": "http://example.com/api/articles/1"
      }
    },
    {
      "type": "articles",
      "id": "2",
      "attributes": {
        "title": "Build APIs You Won't Hate"
      },
      "links": {
        "self": "http://example.com/api/articles/2"
      }
    }
  ]
}
```

First we need to generate 2 classes one to handle the item and the other to handle the collection. Luckily we can utilise artisan to do this.


```
php artisan make:resource ArticleResource
php artisan make:resource ArticlesResource --collection
```


You will now have a new directory under `app/Http/Resources` where all these resources will reside.

In each one of these classes you will have a `toArray` method which is where we will transform our models the way we want them. So lets change the `App\Http\Resource\ArticleResource` so it transforms our Article model into a JSON:API resource;

```
<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\Resource;
class ArticleResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'type'          => 'articles',
            'id'            => (string)$this->id,
            'attributes'    => [
                'title' => $this->title,
            ],
        ];
    }
}
```

As you can see we have top level type, id and attributes keys to start with. We’ll expand on this to include links and relationships as well in a bit but first lets create our controller and routes so we can see this in action.

```
php artisan make:controller --resource ArticleController
```

This will give us a resource based controller to start putting our RESTful API together. Now we need to tell our controller actions to use our newly created resource in the show method so lets add that.

```
<?php
class ArticleController 
{
    /**
     * Display the specified resource.
     *
     * @param  \App\Article $article
     *
     * @return ArticleResource
     */
    public function show(Article $article)
    {
        return new ArticleResource($article);
    }
}
```

>And the route;

```
<?php
Route::resource('articles', 'ArticleController');
```

For simplicity we can just use a resource based route entry hear, we’ll be adding to it a bit so it not totally wasted.

So now you should have a fully operational API which can show an article resource by going to the `/api/articles/{id}` you should get a response back like this;

```
{
  "data": {
    "type": "articles",
    "id": "1",
    "attributes": {
      "title": "JSON API paints my bikeshed!"
    }
  }
}
```

