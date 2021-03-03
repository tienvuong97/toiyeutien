<?php namespace Ncc\Blog\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Input;
use October\Rain\Database\Model;
use October\Rain\Database\Collection;
use Ncc\Blog\Models\Post as BlogPost;
use Ncc\Blog\Models\Category as BlogCategory;
use Ncc\Blog\Models\Settings as BlogSettings;
use Redirect;

class SearchResult extends ComponentBase
{
    /**
     * Parameter to use for the search
     * @var string
     */
    public $searchParam;

    /**
     * The search term
     * @var string
     */
    public $searchTerm;

    /**
     * A collection of posts to display
     * @var Collection
     */
    public $posts;

    /**
     * Parameter to use for the page number
     * @var string
     */
    public $pageParam;

    /**
     * Message to display when there are no messages.
     * @var string
     */
    public $noPostsMessage;

    /**
     * Reference to the page name for linking to posts.
     * @var string
     */
    public $postPage;

    /**
     * Reference to the page name for linking to categories.
     * @var string
     */
    public $categoryPage;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'ncc.blog::lang.settings.search_result',
            'description' => 'ncc.blog::lang.settings.search_result_description'
        ];
    }

    /**
     * @see RainLab\Blog\Components\Posts::defineProperties()
     * @return array
     */
    public function defineProperties()
    {
        // check build to add fallback to not supported inspector types if needed
        // $hasNewInspector = Parameters::get('system::core.build') >= 306;
        $categoryItems = BlogCategory::lists('name', 'id');

        return [
            'searchTerm' => [
                'title'       => 'ncc.blog::lang.settings.search_term_title',
                'description' => 'ncc.blog::lang.settings.search_term_description',
                'type'        => 'string',
                'default'     => '{{ :search }}',
            ],
            'pageNumber' => [
                'title'       => 'ncc.blog::lang.settings.posts_pagination',
                'description' => 'ncc.blog::lang.settings.posts_pagination_description',
                'type'        => 'string',
                'default'     => '{{ :page }}',
            ],
            'disableUrlMapping' => [
                'title'       => 'ncc.blog::lang.settings.disable_url_mapping_title',
                'description' => 'ncc.blog::lang.settings.disable_url_mapping_description',
                'type'        => 'checkbox',
                'default'     => false,
                'showExternalParam' => false
            ],
            'hightlight' => [
                'title'       => 'ncc.blog::lang.settings.hightlight_match_title',
                'type'        => 'checkbox',
                'default'     => false,
                'showExternalParam' => false
            ],
            'postsPerPage' => [
                'title'             => 'ncc.blog::lang.settings.posts_per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'ncc.blog::lang.settings.posts_per_page_validation',
                'default'           => '10',
            ],
            'noPostsMessage' => [
                'title'        => 'ncc.blog::lang.settings.posts_no_posts',
                'description'  => 'ncc.blog::lang.settings.posts_no_posts_description',
                'type'         => 'string',
                'default'      => 'ncc.blog::lang.settings.posts_no_posts_default',
                'showExternalParam' => false
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
                'group'       => 'Links',
            ],
            'postPage' => [
                'title'       => 'ncc.blog::lang.settings.posts_post',
                'description' => 'ncc.blog::lang.settings.posts_post_description',
                'type'        => 'dropdown',
                'default'     => 'blog/post',
                'group'       => 'Links',
            ],
        ];
    }

    /**
     * @see RainLab\Blog\Components\Posts::getCategoryPageOptions()
     * @return mixed
     */
    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * @see RainLab\Blog\Components\Posts::getPostPageOptions()
     * @return mixed
     */
    public function getPostPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * @see ncc\Blog\Components\Posts::getSortOrderOptions()
     * @return mixed
     */
    public function getSortOrderOptions()
    {
        return BlogPost::$allowedSortingOptions;
    }

    /**
     * @see ncc\Blog\Components\Posts::onRun()
     * @return mixed
     */
    public function onRun()
    {
        $this->prepareVars();

        // map get request to :search param
        $searchTerm = Input::get('search');
        if (!$this->property('disableUrlMapping') && \Request::isMethod('get') && $searchTerm) {
            // add ?cats[] query string
            $cats = Input::get('cat');
            $query = http_build_query(['cat' => $cats]);
            $query = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $query);
            $query = !empty($query) ? '?' . $query : '';

            return Redirect::to(
                $this->currentPageUrl([
                    $this->searchParam => urlencode($searchTerm)
                ])
                . $query
            );
        }

        // load posts
        $this->posts = $this->page[ 'posts' ] = $this->listPosts();

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

    /**
     * @see RainLab\Blog\Components\Posts::prepareVars()
     */
    protected function prepareVars()
    {
        $this->pageParam = $this->page[ 'pageParam' ] = $this->paramName('pageNumber');
        $this->searchParam = $this->page[ 'searchParam' ] = $this->paramName('searchTerm');
        $this->searchTerm = $this->page[ 'searchTerm' ] = urldecode($this->property('searchTerm'));
        $this->noPostsMessage = $this->page[ 'noPostsMessage' ] = $this->property('noPostsMessage');

        if ($this->property('disableUrlMapping')) {
            $this->searchTerm = $this->page[ 'searchTerm' ] = urldecode(Input::get('search'));
        }

        /*
         * Page links
         */
        $this->postPage = $this->page[ 'postPage' ] = $this->property('postPage');
        $this->categoryPage = $this->page[ 'categoryPage' ] = $this->property('categoryPage');
    }

    /**
     * @see RainLab\Blog\Components\Posts::prepareVars()
     * @return mixed
     */
    protected function listPosts()
    {
        // Filter posts
        $posts = BlogPost::where(function ($q) {
            $q->where('title', 'LIKE', "%{$this->searchTerm}%")
                ->orWhere('content', 'LIKE', "%{$this->searchTerm}%")
                ->orWhere('excerpt', 'LIKE', "%{$this->searchTerm}%");
        });
        
        // filter categories
        $cat = Input::get('cat');
        if ($cat) {
            $cat = is_array($cat) ? $cat : [$cat];
            $posts->filterCategories($cat);
        }

        // List all the posts that match search terms
        $posts = $posts->listFrontEnd([
            'page'    => $this->property('pageNumber'),
            'sort'    => $this->property('sortOrder'),
            'perPage' => $this->property('postsPerPage'),
        ]);

        /*
         * Add a "url" helper attribute for linking to each post and category
         */
        $posts->each(function ($post) {
            $post->setUrl($this->postPage, $this->controller);

            $post->categories->each(function ($category) {
                $category->setUrl($this->categoryPage, $this->controller);
            });

            // apply highlight of search result
            $this->highlight($post);
        });

        return $posts;
    }

    /**
     * @param \RainLab\Blog\Models\Post $post
     */
    protected function highlight(BlogPost $post)
    {
        if ($this->property('hightlight')) {
            $searchTerm = preg_quote($this->searchTerm, '|');

            // apply highlight
            $post->title = preg_replace('|(' . $searchTerm . ')|iu', '<mark style="padding: 0">$1</mark>', $post->title);

        }
    }
}
