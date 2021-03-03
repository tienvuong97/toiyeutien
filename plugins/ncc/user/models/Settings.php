<?php namespace Ncc\User\Models;

use Model;

class Settings extends Model
{
    /**
     * @var array Behaviors implemented by this model.
     */
    public $implement = [
        \System\Behaviors\SettingsModel::class
    ];

    public $settingsCode = 'user_settings';
    public $settingsFields = 'fields.yaml';


    const ACTIVATE_AUTO = 'auto';
    const ACTIVATE_USER = 'user';
    const ACTIVATE_ADMIN = 'admin';

    const LOGIN_EMAIL = 'email';
    const LOGIN_USERNAME = 'username';

    const REMEMBER_ALWAYS = 'always';
    const REMEMBER_NEVER = 'never';
    const REMEMBER_ASK = 'ask';

    public function initSettingsData()
    {
        $this->require_activation = true;
        $this->activate_mode = self::ACTIVATE_AUTO;
        $this->use_throttle = true;
        $this->block_persistence = false;
        $this->allow_registration = true;
        $this->login_attribute = self::LOGIN_EMAIL;
        $this->remember_login = self::REMEMBER_ALWAYS;
        $this->use_register_throttle = true;
    }

    public function getActivateModeOptions()
    {
        return [
            self::ACTIVATE_AUTO => [
                'ncc.user::lang.settings.activate_mode_auto',
                'ncc.user::lang.settings.activate_mode_auto_comment'
            ],
            self::ACTIVATE_USER => [
                'ncc.user::lang.settings.activate_mode_user',
                'ncc.user::lang.settings.activate_mode_user_comment'
            ],
            self::ACTIVATE_ADMIN => [
                'ncc.user::lang.settings.activate_mode_admin',
                'ncc.user::lang.settings.activate_mode_admin_comment'
            ]
        ];
    }

    public function getActivateModeAttribute($value)
    {
        if (!$value) {
            return self::ACTIVATE_AUTO;
        }

        return $value;
    }

    public function getLoginAttributeOptions()
    {
        return [
            self::LOGIN_EMAIL => ['ncc.user::lang.login.attribute_email'],
            self::LOGIN_USERNAME => ['ncc.user::lang.login.attribute_username']
        ];
    }

    public function getRememberLoginOptions()
    {
        return [
            self::REMEMBER_ALWAYS => [
                'ncc.user::lang.settings.remember_always',
            ],
            self::REMEMBER_NEVER => [
                'ncc.user::lang.settings.remember_never',
            ],
            self::REMEMBER_ASK => [
                'ncc.user::lang.settings.remember_ask',
            ]
        ];
    }

    public function getRememberLoginAttribute($value)
    {
        if (!$value) {
            return self::REMEMBER_ALWAYS;
        }

        return $value;
    }
}
