<?php namespace Ncc\Blog\Models;

use October\Rain\Database\Model;

class Settings extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'ncc_blog_settings';

    public $settingsFields = 'fields.yaml';

    public $rules = [
        'show_all_posts' => ['boolean'],
    ];
}
