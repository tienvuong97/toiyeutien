<?php namespace Ncc\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Ncc\Blog\Models\Category as CategoryModel;

class PostsAddMetadata extends Migration
{

    public function up()
    {
        if (Schema::hasColumn('ncc_blog_posts', 'metadata')) {
            return;
        }

        Schema::table('ncc_blog_posts', function($table)
        {
            $table->mediumText('metadata')->nullable();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('ncc_blog_posts', 'metadata')) {
            Schema::table('ncc_blog_posts', function ($table) {
                $table->dropColumn('metadata');
            });
        }
    }

}
