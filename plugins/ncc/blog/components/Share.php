<?php namespace Ncc\Blog\Components;

use Cms\Classes\ComponentBase;

class Share extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'ncc.blog::lang.share.share_name',
            'description' => 'ncc.blog::lang.share.share_description'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

}
