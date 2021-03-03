<?php namespace Ncc\User;

use App;
use Auth;
use Event;
use Backend;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use Illuminate\Foundation\AliasLoader;
use Ncc\User\Classes\UserRedirector;
use Ncc\User\Models\MailBlocker;
use Ncc\Notify\Classes\Notifier;

class Plugin extends PluginBase
{
    /**
     * @var boolean Determine if this plugin should have elevated privileges.
     */
    public $elevated = true;

    public function pluginDetails()
    {
        return [
            'name'        => 'ncc.user::lang.plugin.name',
            'description' => 'ncc.user::lang.plugin.description',
            'author'      => 'Alexey Bobkov, Samuel Georges',
            'icon'        => 'icon-user',
            'homepage'    => 'https://github.com/ncc/user-plugin'
        ];
    }

    public function register()
    {
        $alias = AliasLoader::getInstance();
        $alias->alias('Auth', 'Ncc\User\Facades\Auth');

        App::singleton('user.auth', function() {
            return \Ncc\User\Classes\AuthManager::instance();
        });

        App::singleton('redirect', function ($app) {
            // overrides with our own extended version of Redirector to support
            // seperate url.intended session variable for frontend
            $redirector = new UserRedirector($app['url']);

            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
            if (isset($app['session.store'])) {
                $redirector->setSession($app['session.store']);
            }

            return $redirector;
        });

        /*
         * Apply user-based mail blocking
         */
        Event::listen('mailer.prepareSend', function($mailer, $view, $message) {
            return MailBlocker::filterMessage($view, $message);
        });

        /*
         * Compatability with Ncc.Notify
         */
        $this->bindNotificationEvents();
    }

    public function registerComponents()
    {
        return [
            \Ncc\User\Components\Session::class       => 'session',
            \Ncc\User\Components\Account::class       => 'account',
            \Ncc\User\Components\ResetPassword::class => 'resetPassword'
        ];
    }

    public function registerPermissions()
    {
        return [
            'ncc.users.access_users' => [
                'tab'   => 'ncc.user::lang.plugin.tab',
                'label' => 'ncc.user::lang.plugin.access_users'
            ],
            'ncc.users.access_groups' => [
                'tab'   => 'ncc.user::lang.plugin.tab',
                'label' => 'ncc.user::lang.plugin.access_groups'
            ],
            'ncc.users.access_settings' => [
                'tab'   => 'ncc.user::lang.plugin.tab',
                'label' => 'ncc.user::lang.plugin.access_settings'
            ],
            'ncc.users.impersonate_user' => [
                'tab'   => 'ncc.user::lang.plugin.tab',
                'label' => 'ncc.user::lang.plugin.impersonate_user'
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'user' => [
                'label'       => 'ncc.user::lang.users.menu_label',
                'url'         => Backend::url('ncc/user/users'),
                'icon'        => 'icon-user',
                'iconSvg'     => 'plugins/ncc/user/assets/images/user-icon.svg',
                'permissions' => ['ncc.users.*'],
                'order'       => 500,

                'sideMenu' => [
                    'users' => [
                        'label' => 'ncc.user::lang.users.menu_label',
                        'icon'        => 'icon-user',
                        'url'         => Backend::url('ncc/user/users'),
                        'permissions' => ['ncc.users.access_users']
                    ],
                    'usergroups' => [
                        'label'       => 'ncc.user::lang.groups.menu_label',
                        'icon'        => 'icon-users',
                        'url'         => Backend::url('ncc/user/usergroups'),
                        'permissions' => ['ncc.users.access_groups']
                    ]
                ]
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'ncc.user::lang.settings.menu_label',
                'description' => 'ncc.user::lang.settings.menu_description',
                'category'    => 'ncc.contact::lang.contact.config_category',
                'icon'        => 'icon-cog',
                'class'       => 'Ncc\User\Models\Settings',
                'order'       => 500,
                'permissions' => ['ncc.users.access_settings']
            ]
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'ncc.user::mail.activate',
            'ncc.user::mail.welcome',
            'ncc.user::mail.restore',
            'ncc.user::mail.new_user',
            'ncc.user::mail.reactivate',
            'ncc.user::mail.invite',
        ];
    }

    public function registerNotificationRules()
    {
        return [
            'groups' => [
                'user' => [
                    'label' => 'User',
                    'icon' => 'icon-user'
                ],
            ],
            'events' => [
                \Ncc\User\NotifyRules\UserActivatedEvent::class,
                \Ncc\User\NotifyRules\UserRegisteredEvent::class,
            ],
            'actions' => [],
            'conditions' => [
                \Ncc\User\NotifyRules\UserAttributeCondition::class
            ],
        ];
    }

    protected function bindNotificationEvents()
    {
        if (!class_exists(Notifier::class)) {
            return;
        }

        Notifier::bindEvents([
            'ncc.user.activate' => \Ncc\User\NotifyRules\UserActivatedEvent::class,
            'ncc.user.register' => \Ncc\User\NotifyRules\UserRegisteredEvent::class
        ]);

        Notifier::instance()->registerCallback(function($manager) {
            $manager->registerGlobalParams([
                'user' => Auth::getUser()
            ]);
        });
    }
}
