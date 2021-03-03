<?php namespace Ncc\Blog\Components;

use Lang;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use October\Rain\Database\Collection;
use Ncc\Blog\Models\Tag as BlogTag;
use Ncc\Blog\Models\Post as BlogPost;

class PostsByTag extends ComponentBase

{
    /**
     * @var Ncc\BlogTags\Models\Tag
     */
    public $tag;

    /**
     * @var Collection
     */
    public $posts;

    /**
     * Reference to the page name for linking to posts.
     * @var string
     */
    public $postPage;

    /**
     * @var string Reference to the page name for linking to categories.
     */
    public $categoryPage;

    /**
     * Parameter to use for the page number
     * @var string
     */
    public $pageParam;

    /**
     * Component Registration
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name' => 'Posts by Tag',
            'description' => 'Provides a list of posts with a certain tag.'
        ];
    }

    /**
     * Component Properties
     * @return array
     */
    public function defineProperties()
    {
        return [
            'tag' => [
                'title' => 'ncc.blog::lang.tag_search.tagURL',
                'description' => 'ncc.blog::lang.tag_search.tagURL_description',
                'default' => '{{ :tag }}',
                'type' => 'string'
            ],
            'pageNumber' => [
                'title'       => 'ncc.blog::lang.settings.posts_pagination',
                'description' => 'ncc.blog::lang.settings.posts_pagination_description',
                'type'        => 'string',
                'default'     => '{{ :page }}',
            ],
            'postsPerPage' => [
                'title'             => 'ncc.blog::lang.settings.posts_per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'ncc.blog::lang.settings.posts_per_page_validation',
                'default'           => '10',
            ],
            'sortOrder' => [
                'title'       => 'ncc.blog::lang.settings.posts_order',
                'description' => 'ncc.blog::lang.settings.posts_order_description',
                'type'        => 'dropdown',
                'default'     => 'published_at desc'
            ],
            'categoryPage' => [
                'title'       => 'ncc.blog::lang.settings.posts_category',
                'description' => 'ncc.blog::lang.settings.posts_category_description',
                'type'        => 'dropdown',
                'default'     => 'blog/category',
                'group'       => 'ncc.blog::lang.settings.group_links',
            ],
            'postPage' => [
                'title'       => 'ncc.blog::lang.settings.posts_post',
                'description' => 'ncc.blog::lang.settings.posts_post_description',
                'type'        => 'dropdown',
                'default'     => 'blog/post',
                'group'       => 'ncc.blog::lang.settings.group_links',
            ],
        ];
    }

    public function getPostPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getSortOrderOptions()
    {
        $options = BlogPost::$allowedSortingOptions;

        foreach ($options as $key => $value) {
            $options[$key] = Lang::get($value);
        }
        return $options;
    }


    /**
     * Load a page of posts
     */
    public function onRun()
    {
        $this -> prepareVars();

        //Get tag
        $this->tag = BlogTag::where('slug', $this->property('tag'))->first();

        // Lists posts by tag and pagination
        if(empty($this->tag)) {
            $this->posts = null;
        } else {
            $this->posts = BlogPost::with('tags')
                ->whereHas('tags', function ($tag){
                    $tag->whereSlug($this->property('tag'));
                })->listFrontEnd([
                    'page'  => $this->property('pageNumber'),
                    'sort'  => $this->property('sortOrder'),
                    'perPage'  => $this->property('postsPerPage'),
                ]);

            // Add a "url" helper attribute for linking to each post
            $this->posts->each(function($post) {
                $post->setUrl($this->postPage,$this->controller);

                if($post->categories->count()) {
                    $post->categories->each(function($category){
                        $category->setUrl($this->categoryPage, $this->controller);
                    });
                }
            });
        }
    }


    /**
     * Query the tag and posts belonging to it
     */
    public function prepareVars()
    {
        $this->pageParam = $this->page['pageParam'] = $this->paramName('pageNumber');

        // Post and Category link
        $this->postPage = $this->page['postPage'] = $this->property('postPage');
        $this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
    }

}
