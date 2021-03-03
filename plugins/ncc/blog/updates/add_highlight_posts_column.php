<?php namespace Ncc\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddHighlightPost extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('ncc_blog_posts', 'highlight')) {
            return;
        }

        Schema::table('ncc_blog_posts', function($table)
        {
            $table->boolean('highlight')->default(false);
        });
    }

    public function down()
    {
        if (Schema::hasColumn('ncc_blog_posts', 'highlight')) {
            Schema::table('ncc_blog_posts', function ($table) {
                $table->dropColumn('highlight');
            });
        }
    }
}
