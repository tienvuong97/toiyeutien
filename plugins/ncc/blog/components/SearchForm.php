<?php namespace Ncc\Blog\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;

/**
 * Search Form Component
 * @package PKleindienst\BlogSearch\Components
 */
class SearchForm extends ComponentBase
{
    /**
     * @var string Reference to the search results page.
     */
    public $resultPage;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'ncc.blog::lang.settings.search_form',
            'description' => 'ncc.blog::lang.settings.search_form_description'
        ];
    }

    /**
     * @return array
     */
    public function defineProperties()
    {
        return [
            'resultPage' => [
                'title'   => 'ncc.blog::lang.settings.search_result_page_title',
                'type'    => 'dropdown',
                'default' => 'blog/search'
            ]
        ];
    }

    /**
     * @return mixed
     */
    public function getResultPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * Prepare vars
     */
    public function onRun()
    {
        $this->resultPage = $this->page[ 'resultPage' ] = $this->property('resultPage');
    }
}
