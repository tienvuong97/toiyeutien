<?php namespace Ncc\Blog\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('ncc_blog_comments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('posts_id')->unsigned();
            $table->string('name');
            $table->string('email');
            $table->string('content');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ncc_blog_comments');
    }
}
