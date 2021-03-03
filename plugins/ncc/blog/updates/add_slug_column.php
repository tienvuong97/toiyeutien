<?php namespace Ncc\Blog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Ncc\Blog\Models\Tag;
use System\Classes\PluginManager;

class AddTagSlug extends Migration
{
    public function up()
    {
        Schema::table('blogtags_tags', function($table)
        {
            $table->string('slug')->unique()->nullable();
        });

        $this->fillSlugs();
    }

    public function down()
    {
        Schema::table('blogtags_tags', function($table)
        {
            $table->dropColumn('slug');
        });
    }

    private function fillSlugs()
    {
        $tags = Tag::all();

        foreach ($tags as $tag) {
            $tag->slug = str_slug($tag->name);
            $tag->save();
        }
    }
}
