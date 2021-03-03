<?php namespace Ncc\Blog\Components;

use Ncc\Blog\Models\Tag as BlogTag;
use Cms\Classes\ComponentBase;
use DB;
use Carbon\Carbon;
use Cms\Classes\Page;

class Tags extends ComponentBase
{
   /**
     * @var Collection A collection of tags to display
     */
    public $tags;

    /**
     * @var string Reference to the page name for linking to tags.
     */
    public $tagPage;

    /**
     * @var string Reference to the current tag slug.
     */
    public $currentTagSlug;

    /**
     * Component Registration
     *
     * @return  array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'ncc.blog::lang.settings.tags_list_title',
            'description' => 'ncc.blog::lang.settings.tags_list_description',
        ];
    }

    /**
     * Component Properties
     *
     * @return  array
     */
    public function defineProperties()
    {
        return [
            'slug' => [
                'title'       => 'ncc.blog::lang.tags.tag_slug',
                'description' => 'ncc.blog::lang.tags.tag_slug_description',
                'default'     => '{{ :tag }}',
                'type'        => 'string',
            ],
            'hideOrphans' => [
                'title'             => 'ncc.blog::lang.tags.hide_title',
                'description'       => 'ncc.blog::lang.tags.hide_description',
                'default' => 0,
                'type'              => 'checkbox',
            ],
            'tagPage' => [
                'title'       => 'ncc.blog::lang.tags.tags_page',
                'description' => 'ncc.blog::lang.tags.tags_page_description',
                'type'        => 'dropdown',
                'default'     => 'blog/tag',
                'group'       => 'ncc.blog::lang.settings.group_links',
            ],
        ];
    }

    public function getTagPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $this->currentTagSlug = $this->page['currentTagSlug'] = $this->property('slug');
        $this->tagPage = $this->page['tagPage'] = $this->property('tagPage');
        $this->tags = $this->page['tags'] = $this->loadTags();
    }

    /**
     * Load all categories or, depending on the <displayEmpty> option, only those that have blog posts
     * @return mixed
     */
    protected function loadTags()
    {
        $tags = BlogTag::with('posts');
        if ($this->property('hideOrphans'))
            $tags->has('posts', '>', 0);
        $tags = $tags->get();
        /*
         * Add a "url" helper attribute for linking to each tag
         */
        return $this->linkTags($tags);
    }

    /**
     * Sets the URL on each tag according to the defined tag page
     * @return void
     */
    protected function linkTags($tags)
    {
        return $tags->each(function ($tag) {
            $tag->setUrl($this->tagPage, $this->controller);
        });
    }
}
