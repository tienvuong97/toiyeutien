<?php
namespace Ncc\Finance;

use Backend\Facades\Backend;

class Plugin extends \System\Classes\PluginBase
{
    public function pluginDetails()
    {
        return [
            'name' => 'Finance Plugin',
            'description' => 'Provides some tool for personal finance.',
            'author' => 'NCC Plus',
            'icon' => 'icon-finance'
        ];
    }

    public function registerComponents()//register one component
    {
        return [
            'Ncc\Finance\Components\Finance'       => 'finance',
        ];
    }

    public function registerPermissions()
    {
        return [
            'ncc.finance.manage_settings' => [
                'tab'   => 'finance tab',
                'label' => 'finance label'
            ]
        ];
    }

    public function registerNavigation()
    {
        return [
            'finance' => [
                'label'       => 'Financial Tool',
                'url'         => Backend::url('ncc/finance/finance'),
                'icon'        => 'icon-finance',
                'iconSvg'     => 'plugins/ncc/finance/assets/images/finance-icon.svg',
                'permissions' => ['ncc.finance.*'],
                'order'       => 301,
                'sideMenu' => [
                    'finance' => [
                        'label'       => 'Finance Tool',
                        'icon'        => 'icon-finance',
                        'url'         => Backend::url('ncc/finance/finance'),
                        'permissions' => ['ncc.finance.*'],
                    ],
                    'report' => [
                        'label'       => 'Report',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('ncc/finance/report'),
                        'permissions' => ['ncc.finance.*'],
                    ],
                ]
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'finance' => [
                'label' => 'Financial Tool',
                'description' => 'description',
                'category' => 'Financial Tool',
                'icon' => 'icon-pencil',
                'class' => 'Ncc\Finance\Models\Settings',
                'order' => 500,
                'keywords' => 'finance tool',
                'permissions' => ['ncc.finance.manage_settings']
            ]
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register()
    {

    }

    public function boot()
    {

    }
}