<?php namespace Ncc\Blog\Components;

use Lang;
use Redirect;
use BackendAuth;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use October\Rain\Database\Model;
use October\Rain\Database\Collection;
use Ncc\Blog\Models\Post as BlogPost;
use Ncc\Blog\Models\Category as BlogCategory;
use Ncc\Blog\Models\Settings as BlogSettings;

class Posts extends ComponentBase
{
    /**
     * A collection of posts to display
     *
     * @var Collection
     */
    public $posts;

    /**
     * Parameter to use for the page number
     *
     * @var string
     */
    public $pageParam;

    /**
     * If the post list should be filtered by a category, the model to use
     *
     * @var Model
     */
    public $category;


    /**
     * Message to display when there are no messages
     *
     * @var string
     */
    public $noPostsMessage;

    /**
     * Reference to the page name for linking to posts
     *
     * @var string
     */
    public $postPage;

    /**
     * Reference to the page name for linking to categories
     *
     * @var string
     */
    public $categoryPage;

    /**
     * If the post list should be ordered by another attribute
     *
     * @var string
     */
    public $sortOrder;

    public function componentDetails()
    {
        return [
            'name'        => 'ncc.blog::lang.settings.posts_title',
            'description' => 'ncc.blog::lang.settings.posts_description'
        ];
    }

    public function defineProperties()
    {
        return [
            'pageNumber' => [
                'title'       => 'ncc.blog::lang.settings.posts_pagination',
                'description' => 'ncc.blog::lang.settings.posts_pagination_description',
                'type'        => 'string',
                'default'     => '{{ :page }}',
            ],
            'categoryFilter' => [
                'title'       => 'ncc.blog::lang.settings.posts_filter',
                'description' => 'ncc.blog::lang.settings.posts_filter_description',
                'type'        => 'string',
                'default'     => '',
            ],

            'postsFilter' => [
                'title'       => 'Posts Filter',
                'description' => 'ncc.blog::lang.settings.posts_filter_description',
                'type'        => 'dropdown',
                'default'     => 'Highlight',
            ],

            'postsPerPage' => [
                'title'             => 'ncc.blog::lang.settings.posts_per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'ncc.blog::lang.settings.posts_per_page_validation',
                'default'           => '10',
            ],
            'noPostsMessage' => [
                'title'             => 'ncc.blog::lang.settings.posts_no_posts',
                'description'       => 'ncc.blog::lang.settings.posts_no_posts_description',
                'type'              => 'string',
                'default'           => 'ncc.blog::lang.settings.posts_no_posts_default',
                'showExternalParam' => false,
            ],
            'sortOrder' => [
                'title'       => 'ncc.blog::lang.settings.posts_order',
                'description' => 'ncc.blog::lang.settings.posts_order_description',
                'type'        => 'dropdown',
                'default'     => 'published_at desc',
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
            'exceptPost' => [
                'title'             => 'ncc.blog::lang.settings.posts_except_post',
                'description'       => 'ncc.blog::lang.settings.posts_except_post_description',
                'type'              => 'string',
                'validationPattern' => '^[a-z0-9\-_,\s]+$',
                'validationMessage' => 'ncc.blog::lang.settings.posts_except_post_validation',
                'default'           => '',
                'group'             => 'ncc.blog::lang.settings.group_exceptions',
            ],
            'exceptCategories' => [
                'title'             => 'ncc.blog::lang.settings.posts_except_categories',
                'description'       => 'ncc.blog::lang.settings.posts_except_categories_description',
                'type'              => 'string',
                'validationPattern' => '^[a-z0-9\-_,\s]+$',
                'validationMessage' => 'ncc.blog::lang.settings.posts_except_categories_validation',
                'default'           => '',
                'group'             => 'ncc.blog::lang.settings.group_exceptions',
            ],
        ];
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getPostPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getPostsFilterOptions()
    {
        return ['highlight' => "Highlight", 'none'=>'None'];
    }

    public function getSortOrderOptions()
    {
        $options = BlogPost::$allowedSortingOptions;

        foreach ($options as $key => $value) {
            $options[$key] = Lang::get($value);
        }

        return $options;
    }

    public function onRun()
    {
        $this->prepareVars();

        $this->category = $this->page['category'] = $this->loadCategory();
        $this->posts = $this->page['posts'] = $this->listPosts();

        /*
         * If the page number is not valid, redirect
         */
        if ($pageNumberParam = $this->paramName('pageNumber')) {
            $currentPage = $this->property('pageNumber');

            if ($currentPage > ($lastPage = $this->posts->lastPage()) && $currentPage > 1) {
                return Redirect::to($this->currentPageUrl([$pageNumberParam => $lastPage]));
            }
        }
    }

    protected function prepareVars()
    {
        $this->pageParam = $this->page['pageParam'] = $this->paramName('pageNumber');
        $this->noPostsMessage = $this->page['noPostsMessage'] = $this->property('noPostsMessage');

        /*
         * Page links
         */
        $this->postPage = $this->page['postPage'] = $this->property('postPage');
        $this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
    }

    protected function listPosts()
    {
        $category = $this->category ? $this->category->id : null;

        /*
         * List all the posts, eager load their categories
         */
        $isPublished = $this->checkEditor();
        $posts = $this->property('postsFilter') == 'none' ? BlogPost::with('categories')->listFrontEnd($this->elements($category, $isPublished)) : BlogPost::with('categories')->where('highlight', true)->listFrontEnd($this->elements($category, $isPublished));

        /*
         * Add a "url" helper attribute for linking to each post and category
         */
        $posts->each(function($post) {
            $post->setUrl($this->postPage, $this->controller);

            $post->categories->each(function($category) {
                $category->setUrl($this->categoryPage, $this->controller);
            });
        });

        return $posts;
    }

    protected function loadCategory()
    {
        if (!$slug = $this->property('categoryFilter')) {
            return null;
        }

        $category = new BlogCategory;

        $category = $category->isClassExtendedWith('Ncc.Translate.Behaviors.TranslatableModel')
            ? $category->transWhere('slug', $slug)
            : $category->where('slug', $slug);

        $category = $category->first();

        return $category ?: null;
    }

    protected function elements($category,$isPublished )
    {
        return [
            'page'             => $this->property('pageNumber'),
            'sort'             => $this->property('sortOrder'),
            'perPage'          => $this->property('postsPerPage'),
            'search'           => trim(input('search')),
            'category'         => $category,
            'published'        => $isPublished,
            'exceptPost'       => is_array($this->property('exceptPost'))
                ? $this->property('exceptPost')
                : preg_split('/,\s*/', $this->property('exceptPost'), -1, PREG_SPLIT_NO_EMPTY),
            'exceptCategories' => is_array($this->property('exceptCategories'))
                ? $this->property('exceptCategories')
                : preg_split('/,\s*/', $this->property('exceptCategories'), -1, PREG_SPLIT_NO_EMPTY),
        ];
    }

    protected function checkEditor()
    {
        $backendUser = BackendAuth::getUser();

        return $backendUser && $backendUser->hasAccess('ncc.blog.access_posts') && BlogSettings::get('show_all_posts', true);
    }
}
