<?php namespace Ncc\Blog;

use Backend;
use Controller;
use Ncc\Blog\Models\Post;
use System\Classes\PluginBase;
use Ncc\Blog\Classes\TagProcessor;
use Ncc\Blog\Models\Category;
use Event;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'ncc.blog::lang.plugin.name',
            'description' => 'ncc.blog::lang.plugin.description',
            'author'      => 'Alexey Bobkov, Samuel Georges',
            'icon'        => 'icon-pencil',
            'homepage'    => 'https://github.com/ncc/blog-plugin'
        ];
    }

    public function registerComponents()
    {
        return [
            'Ncc\Blog\Components\Post'       => 'blogPost',
            'Ncc\Blog\Components\Posts'      => 'blogPosts',
            'Ncc\Blog\Components\Categories' => 'blogCategories',
            'Ncc\Blog\Components\RssFeed'    => 'blogRssFeed',
            'Ncc\Blog\Components\Comments'   => 'blogCommentForm',
            'Ncc\Blog\Components\Tags'       => 'blogTags',
            'Ncc\Blog\Components\PostsByTag' => 'blogPostsByTag',
            'Ncc\Blog\Components\SearchForm' => 'blogsearchForm',
            'Ncc\Blog\Components\SearchResult'=> 'blogsearchResult',
            'Ncc\Blog\Components\Share'       => 'blogShare',
        ];
    }

    public function registerPermissions()
    {
        return [
            'ncc.blog.manage_settings' => [
                'tab'   => 'ncc.blog::lang.blog.tab',
                'label' => 'ncc.blog::lang.blog.manage_settings'
            ],
            'ncc.blog.access_posts' => [
                'tab'   => 'ncc.blog::lang.blog.tab',
                'label' => 'ncc.blog::lang.blog.access_posts'
            ],
            'ncc.blog.access_categories' => [
                'tab'   => 'ncc.blog::lang.blog.tab',
                'label' => 'ncc.blog::lang.blog.access_categories'
            ],
            'ncc.blog.access_other_posts' => [
                'tab'   => 'ncc.blog::lang.blog.tab',
                'label' => 'ncc.blog::lang.blog.access_other_posts'
            ],
            'ncc.blog.access_import_export' => [
                'tab'   => 'ncc.blog::lang.blog.tab',
                'label' => 'ncc.blog::lang.blog.access_import_export'
            ],
            'ncc.blog.access_publish' => [
                'tab'   => 'ncc.blog::lang.blog.tab',
                'label' => 'ncc.blog::lang.blog.access_publish'
            ],
            'ncc.blog.access_tags' => [
                'tab'   => 'ncc.blog::lang.tag.name_tag',
                'label' => 'ncc.blog::lang.blog.access_tags'
            ],
            'ncc.blog.access_comments' => [
                'tab'   => 'ncc.blog::lang.comment.name_comment',
                'label' => 'ncc.blog::lang.blog.access_comments'
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'blog' => [
                'label'       => 'ncc.blog::lang.blog.menu_label',
                'url'         => Backend::url('ncc/blog/posts'),
                'icon'        => 'icon-pencil',
                'iconSvg'     => 'plugins/ncc/blog/assets/images/blog-icon.svg',
                'permissions' => ['ncc.blog.*'],
                'order'       => 300,

                'sideMenu' => [
                    'new_post' => [
                        'label'       => 'ncc.blog::lang.posts.new_post',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('ncc/blog/posts/create'),
                        'permissions' => ['ncc.blog.access_posts']
                    ],
                    'posts' => [
                        'label'       => 'ncc.blog::lang.blog.posts',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('ncc/blog/posts'),
                        'permissions' => ['ncc.blog.access_posts']
                    ],
                    'categories' => [
                        'label'       => 'ncc.blog::lang.blog.categories',
                        'icon'        => 'icon-list-ul',
                        'url'         => Backend::url('ncc/blog/categories'),
                        'permissions' => ['ncc.blog.access_categories']
                    ],
                    'tags' => [
                        'label'       => 'ncc.blog::lang.tag.name_tag',
                        'icon'        => 'icon-tags',
                        'url'         => Backend::url('ncc/blog/tags'),
                        'permissions' => ['ncc.blog.access_tags']
                    ],
                ]
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'blog' => [
                'label' => 'ncc.blog::lang.blog.menu_label',
                'description' => 'ncc.blog::lang.blog.settings_description',
                'category' => 'ncc.contact::lang.contact.config_category',
                'icon' => 'icon-pencil',
                'class' => 'Ncc\Blog\Models\Settings',
                'order' => 500,
                'keywords' => 'blog post category',
                'permissions' => ['ncc.blog.manage_settings']
            ]
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register()
    {
        /*
         * Register the image tag processing callback
         */
        TagProcessor::instance()->registerCallback(function($input, $preview) {
            if (!$preview) {
                return $input;
            }

            return preg_replace('|\<img src="image" alt="([0-9]+)"([^>]*)\/>|m',
                '<span class="image-placeholder" data-index="$1">
                    <span class="upload-dropzone">
                        <span class="label">Click or drop an image...</span>
                        <span class="indicator"></span>
                    </span>
                </span>',
            $input);
        });
    }

    public function boot()
    {
        /*
         * Register menu items for the Ncc.Pages plugin
         */
        Event::listen('pages.menuitem.listTypes', function() {
            return [
                'blog-category'       => 'ncc.blog::lang.menuitem.blog_category',
                'all-blog-categories' => 'ncc.blog::lang.menuitem.all_blog_categories',
                'blog-post'           => 'ncc.blog::lang.menuitem.blog_post',
                'all-blog-posts'      => 'ncc.blog::lang.menuitem.all_blog_posts',
                'category-blog-posts' => 'ncc.blog::lang.menuitem.category_blog_posts',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function($type) {
            if ($type == 'blog-category' || $type == 'all-blog-categories') {
                return Category::getMenuTypeInfo($type);
            }
            elseif ($type == 'blog-post' || $type == 'all-blog-posts' || $type == 'category-blog-posts') {
                return Post::getMenuTypeInfo($type);
            }
        });

        Event::listen('pages.menuitem.resolveItem', function($type, $item, $url, $theme) {
            if ($type == 'blog-category' || $type == 'all-blog-categories') {
                return Category::resolveMenuItem($item, $url, $theme);
            }
            elseif ($type == 'blog-post' || $type == 'all-blog-posts' || $type == 'category-blog-posts') {
                return Post::resolveMenuItem($item, $url, $theme);
            }
        });
    }
}
