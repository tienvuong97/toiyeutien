<?php namespace Ncc\Blog\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Ncc\Blog\Models\Tag;
use Flash;
use Lang;

/**
 * Tags Back-end Controller
 */
class Tags extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $requiredPermissions = ['ncc.blog.access_tags'];
    
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Ncc.Blog', 'blog', 'tags');
    }
    /**
     * Delete tags
     *
     * @return  $this->listRefresh()
     */
    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds))
            $delete = Tag::whereIn('id', $checkedIds)->delete();

        if (!isset($delete) && !$delete)
            return Flash::error(Lang::get('ncc.blog::lang.tags.unknown_error'));

        Flash::success(Lang::get('ncc.blog::lang.tags.delete_success'));
        return $this->listRefresh();
    }

    /**
     * Removes tags with no associated posts
     *
     * @return  $this->listRefresh()
     */
    public function index_onRemoveOrphanedTags()
    {
        if (!$delete = Tag::has('posts', 0)->delete())
            return Flash::error(Lang::get('ncc.blog::lang.tags.unknown_error'));

        Flash::success(Lang::get('ncc.blog::lang.tags.delete_orphaned_success'));

        return $this->listRefresh();
    }
}
