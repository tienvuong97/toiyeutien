<?php namespace Ncc\Blog\Controllers;

use BackendMenu;
use Flash;
use Lang;
use Backend\Classes\Controller;
use Ncc\Blog\Models\Post;
use Ncc\Blog\Models\Comment;

class Posts extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.ImportExportController',
        'Backend.Behaviors.RelationController', //new add
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $importExportConfig = 'config_import_export.yaml';
    public $relationConfig = 'config_relation.yaml'; //new add

    public $requiredPermissions = ['ncc.blog.access_other_posts', 'ncc.blog.access_posts'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Ncc.Blog', 'blog', 'posts');
    }

    public function index()
    {
        $this->vars['postsTotal'] = Post::count();
        $this->vars['postsPublished'] = Post::isPublished()->count();
        $this->vars['postsDrafts'] = $this->vars['postsTotal'] - $this->vars['postsPublished'];
        $this->asExtension('ListController')->index();
    }

    public function create()
    {
        BackendMenu::setContextSideMenu('new_post');

        $this->bodyClass = 'compact-container';
        $this->addCss('/plugins/ncc/blog/assets/css/ncc.blog-preview.css');
        $this->addJs('/plugins/ncc/blog/assets/js/post-form.js');
        
        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        $this->bodyClass = 'compact-container';
        $this->addCss('/plugins/ncc/blog/assets/css/ncc.blog-preview.css');
        $this->addJs('/plugins/ncc/blog/assets/js/post-form.js');
        $this->vars['comments'] = Comment::where('posts_id',$recordId)->get();
        return $this->asExtension('FormController')->update($recordId);
    }

    public function export()
    {
        $this->addCss('/plugins/ncc/blog/assets/css/ncc.blog-export.css');

        return $this->asExtension('ImportExportController')->export();
    }

    public function listExtendQuery($query)
    {
        if (!$this->user->hasAnyAccess(['ncc.blog.access_other_posts'])) {
            $query->where('user_id', $this->user->id);
        }
    }

    public function formExtendQuery($query)
    {
        if (!$this->user->hasAnyAccess(['ncc.blog.access_other_posts'])) {
            $query->where('user_id', $this->user->id);
        }
    }

    public function formExtendFieldsBefore($widget)
    {
        if (!$model = $widget->model) {
            return;
        }

        if ($model instanceof Post && $model->isClassExtendedWith('Ncc.Translate.Behaviors.TranslatableModel')) {
            $widget->secondaryTabs['fields']['content']['type'] = 'Ncc\Blog\FormWidgets\MLBlogMarkdown';
        }
    }

    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $postId) {
                if ((!$post = Post::find($postId)) || !$post->canEdit($this->user)) {
                    continue;
                }

                $post->delete();
            }

            Flash::success(Lang::get('ncc.blog::lang.post.delete_success'));
        }

        return $this->listRefresh();
    }

    public function index_onHighlight()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $postId) {
                if ((!$post = Post::find($postId)) || !$post->canEdit($this->user)) {
                    continue;
                }

                $post
                    ->where('id',$postId )
                    ->update(['highlight' => true]);
            }

            Flash::success(Lang::get('ncc.blog::lang.post.delete_success'));
        }

        return $this->listRefresh();
    }

    /**
     * {@inheritDoc}
     */
    public function listInjectRowClass($record, $definition = null)
    {
        if (!$record->published) {
            return 'safe disabled';
        }
    }

    public function formBeforeCreate($model)
    {
        $model->user_id = $this->user->id;
    }

    public function onRefreshPreview()
    {
        $data = post('Post');

        $previewHtml = Post::formatHtml($data['content'], true);

        return [
            'preview' => $previewHtml
        ];
    }
   
}
