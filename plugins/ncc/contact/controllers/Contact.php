<?php namespace Ncc\Contact\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Contact Back-end Controller
 */
class Contact extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Ncc.Contact', 'contact', 'contact');
    }
}
