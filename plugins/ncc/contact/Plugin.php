<?php namespace Ncc\Contact;

use Backend;
use System\Classes\PluginBase;

/**
 * contact Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'ncc.contact::lang.plugin.name',
            'description' => 'ncc.contact::lang.plugin.description',
            'author'      => 'Ncc',
            'icon'        => 'icon-envelope'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {

        return [
            'Ncc\Contact\Components\contact' => 'contact',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {

    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {

        return [
            'contact' => [
                'label'       => 'ncc.contact::lang.contact.menu_label',
                'url'         => Backend::url('ncc/contact/contact'),
                'icon'        => 'icon-envelope',
                'permissions' => ['ncc.contact.*'],
                'order'       => 500,
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'contact' => [
                'label'       => 'ncc.contact::lang.contact.menu_label',
                'description' => 'ncc.contact::lang.contact.config_description',
                'category'    => 'ncc.contact::lang.contact.config_category',
                'icon'        => 'icon-envelope-open',
                'class'       => 'Ncc\Contact\Models\Settings',
                'order'       => 500,
                'keywords'    => 'ncc.contact::lang.contact.config_keyword',
                'permissions' => []
            ]
        ];
    }
}
