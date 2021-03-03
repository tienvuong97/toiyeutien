<?php namespace Ncc\Blog\Updates;

use October\Rain\Database\Updates\Migration;
use October\Rain\Database\Schema\Blueprint;
use Schema;

class CreateTagsTable extends Migration
{
    public function up()
    {
        Schema::create('blogtags_tags', function(Blueprint $table)
            {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->string('name')->unique()->nullable();
                $table->string('slug')->unique()->nullable();
                $table->timestamps();
            });

            Schema::create('blogtags_post_tag', function(Blueprint $table)
            {
                $table->engine = 'InnoDB';
                $table->integer('tag_id')->unsigned()->nullable()->default(null);
                $table->integer('post_id')->unsigned()->nullable()->default(null);
                $table->index(['tag_id', 'post_id']);
                $table->foreign('tag_id')->references('id')->on('blogtags_tags')->onDelete('cascade');
                $table->foreign('post_id')->references('id')->on('ncc_blog_posts')->onDelete('cascade');
            });
    }

    public function down()
    {
        Schema::dropIfExists('blogtags_post_tag');
        Schema::dropIfExists('blogtags_tags');
    }
}
