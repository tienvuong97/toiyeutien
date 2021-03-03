<?php namespace Ncc\Contact\Models;

use October\Rain\Database\Model;

class Settings extends Model
{

    use \October\Rain\Database\Traits\Validation;
    
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'ncc_contact_settings';

    public $settingsFields = 'fields.yaml';
 
    public $rules = [
        'allow_receive_mail' => 'boolean',
        'email_receive' =>  'required|email'
    ];
}
