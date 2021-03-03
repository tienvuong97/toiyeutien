<?php namespace Ncc\Blog\Models;

use Config;
use Model;
use Url;
use Ncc\Blog\Models\Post;
use October\Rain\Router\Helper as RouterHelper;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
/**
 * Tag Model
 */
class Tag extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'blogtags_tags';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    public $fillable = [
        'name',
        'slug',
    ];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'posts' => [
            'Ncc\Blog\Models\Post',
            'table' => 'blogtags_post_tag',
            'order' => 'published_at desc',
            'scope' => 'isPublished'
        ],

        'posts_count' => [
            'Ncc\Blog\Models\Post',
            'table' => 'blogtags_post_tag',
            'scope' => 'isPublished',
            'count' => true
        ]
    ];


    /**
     * @var array Validation rules
     */
    public $rules = [
        'name' => 'required|unique:blogtags_tags'
    ];

    public $customMessages = [
        'name.required' => 'ncc.blog::lang.tags.required_field',
        'name.unique'   => 'ncc.blog::lang.tags.unique_name',
        'name.regex'    => 'ncc.blog::lang.tags.invalid_input',
    ];

    /**
     * Before create. Generate a URL slug
     *
     * @return void
     */
    public function beforeCreate()
    {
        $this->slug = str_slug($this->name);
    }

    public function afterDelete()
    {
        $this->posts()->detach();
    }

    public function getPostCountAttribute()
    {
        return optional($this->posts_count->first())->count ?? 0;
    }


     /**
     * Sets the "url" attribute with a URL to this object
     *
     * @param string                    $pageName
     * @param Cms\Classes\Controller    $controller
     */
    public function setUrl($pageName, $controller)
    {
        $params = [
            'id' => $this->id,
            'slug' => $this->slug,
        ];

        return $this->url = $controller->pageUrl($pageName, $params);
    }


}
