<?php

/*
Plugin Name: Interaktiv Plugin
Plugin URI: https://github.com/HenryJobst/interaktiv-plugin
Description: This plugin add a new custom post type "Interaktiv", which is editable for all registered users.
Version: 1.0.3
Author: Henry Jobst
Author URI: https://github.com/HenryJobst
Text Domain: interaktiv-plugin-text-domain
License: MIT License

Copyright (c) 2020 {Author}

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 */

defined('ABSPATH') or die;

class InteraktivPlugin
{
    // post type name
    const INTERAKTIV = 'interaktiv';

    // text domain
    const INTERAKTIV_PLUGIN_TEXT_DOMAIN = 'interaktiv-plugin-text-domain';

    // roles
    const SUBSCRIBER = 'subscriber';
    const CONTRIBUTOR = 'contributor';
    const AUTHOR = 'author';
    const EDITOR = 'editor';
    const ADMIN = 'administrator';

    // Meta capabilities
    const EDIT_INTERAKTIV = 'edit_interaktiv';
    const READ_INTERAKTIV = 'read_interaktiv';
    const DELETE_INTERAKTIV = 'delete_interaktiv';

    // Primitive capabilities used outside of map_meta_cap():
    const EDIT_INTERAKTIVS = "edit_interaktivs";
    const EDIT_OTHERS_INTERAKTIVS = 'edit_others_interaktivs';
    const PUBLISH_INTERAKTIVS = 'publish_interaktivs';
    const READ_PRIVATE_INTERAKTIVS = 'read_private_interaktivs';

    // Primitive capabilities used within map_meta_cap():
    const READ = 'read';
    const DELETE_INTERAKTIVS = 'delete_interaktivs';
    const DELETE_PRIVATE_INTERAKTIVS = 'delete_private_interaktivs';
    const DELETE_PUBLISHED_INTERAKTIVS = 'delete_published_interaktivs';
    const DELETE_OTHERS_INTERAKTIVS = 'delete_others_interaktivs';
    const EDIT_PRIVATE_INTERAKTIVS = 'edit_private_interaktivs';
    const EDIT_PUBLISHED_INTERAKTIVS = 'edit_published_interaktivs';
    const CREATE_INTERAKTIVS = 'create_interaktivs';


    const ADD_FILTER_PRIORITY = 10;
    const ADD_FILTER_PARAMETER_COUNT = 4;

    const POST_FORMATS = 'post-formats';
    const POST_TYPE = 'post_type';
    const POST = 'post';
    const EDIT_COMMENT = 'edit_comment';

    function __construct()
    {
        // add custom post type
        add_action('init', array($this, 'register_interaktiv'));

        // set special mapping function for per post capabilities
        add_filter('map_meta_cap', array($this, 'interaktiv_map_meta_cap'),
            self::ADD_FILTER_PRIORITY, self::ADD_FILTER_PARAMETER_COUNT);

        // add custom post type to standard post loop
        add_action('pre_get_posts', array($this, 'add_interaktiv_to_post_type'));

        // set post format filter
        add_action('load-post.php', array($this, 'interaktiv_post_format_support_filter'));
        add_action('load-post-new.php', array($this, 'interaktiv_post_format_support_filter'));
        add_action('load-edit.php', array($this, 'interaktiv_post_format_support_filter'));

        // Filter the default post format.
        add_filter('option_default_post_format', array($this, 'interaktiv_default_post_format_filter'));
    }

    function activate()
    {
        $this->set_rights();
        flush_rewrite_rules();
    }

    function deactivate()
    {
        $this->unset_rights();
        $this->unregister_interaktiv();
        flush_rewrite_rules();
    }

    function enable_edit_interaktiv_for_all()
    {
        foreach (array(self::SUBSCRIBER, self::CONTRIBUTOR, self::AUTHOR, self::EDITOR, self::ADMIN) as $role_name) {
            $role = get_role($role_name);
            $role->add_cap(self::CREATE_INTERAKTIVS);
            $role->add_cap(self::EDIT_INTERAKTIVS);
            $role->add_cap(self::DELETE_INTERAKTIVS);
            $role->add_cap(self::PUBLISH_INTERAKTIVS);
            $role->add_cap(self::EDIT_PUBLISHED_INTERAKTIVS);
        }
    }

    function disable_edit_interaktiv_for_all()
    {
        foreach (array(self::SUBSCRIBER, self::CONTRIBUTOR, self::AUTHOR, self::EDITOR, self::ADMIN) as $role_name) {
            $role = get_role($role_name);
            $role->remove_cap(self::CREATE_INTERAKTIVS);
            $role->remove_cap(self::EDIT_INTERAKTIVS);
            $role->remove_cap(self::DELETE_INTERAKTIVS);
            $role->remove_cap(self::PUBLISH_INTERAKTIVS);
            $role->remove_cap(self::EDIT_PUBLISHED_INTERAKTIVS);
        }
    }

    function enable_others_interaktiv_for_editor()
    {
        foreach (array(self::EDITOR, self::ADMIN) as $role_name) {
            $role = get_role($role_name);
            $role->add_cap(self::EDIT_OTHERS_INTERAKTIVS);
            $role->add_cap(self::DELETE_OTHERS_INTERAKTIVS);
            $role->add_cap(self::READ_PRIVATE_INTERAKTIVS);
            $role->add_cap(self::EDIT_PUBLISHED_INTERAKTIVS);
            $role->add_cap(self::EDIT_PRIVATE_INTERAKTIVS);
            $role->add_cap(self::DELETE_PUBLISHED_INTERAKTIVS);
            $role->add_cap(self::DELETE_PRIVATE_INTERAKTIVS);
        }
    }

    function disable_others_interaktiv_for_editor()
    {
        foreach (array(self::EDITOR, self::ADMIN) as $role_name) {
            $role = get_role($role_name);
            $role->remove_cap(self::EDIT_OTHERS_INTERAKTIVS);
            $role->remove_cap(self::DELETE_OTHERS_INTERAKTIVS);
            $role->remove_cap(self::READ_PRIVATE_INTERAKTIVS);
            $role->remove_cap(self::EDIT_PUBLISHED_INTERAKTIVS);
            $role->remove_cap(self::EDIT_PRIVATE_INTERAKTIVS);
            $role->remove_cap(self::DELETE_PUBLISHED_INTERAKTIVS);
            $role->remove_cap(self::DELETE_PRIVATE_INTERAKTIVS);
        }
    }

    function set_rights()
    {
        $this->enable_edit_interaktiv_for_all();
        $this->enable_others_interaktiv_for_editor();
    }

    function unset_rights()
    {
        $this->disable_edit_interaktiv_for_all();
        $this->disable_others_interaktiv_for_editor();
    }

    function register_interaktiv()
    {
        $labels = array(
            'name' => __('Interaktiv', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'singular_name' => __('Interaktiv', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'menu_name' => __('Interaktiv', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'name_admin_bar' => __('Interaktiv', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'archives' => __('Archiv', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'attributes' => __('Attribute', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'parent_item_colon' => __('Eltern Eintrag:', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'all_items' => __('Alle Einträge', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'add_new_item' => __('Neuer Eintrag', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'add_new' => __('Erstellen', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'new_item' => __('Neuer Eintrag', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'edit_item' => __('Bearbeite Eintrag', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'update_item' => __('Aktualisiere Eintrag', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'view_item' => __('Zeige Eintrag', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'view_items' => __('Zeige Einträge', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'search_items' => __('Suche Einträge', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'not_found' => __('Nicht gefunden', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'not_found_in_trash' => __('Nicht im Papierkorb gefunden', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'featured_image' => __('Bild', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'set_featured_image' => __('Setze Bild', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'remove_featured_image' => __('Entferne Bild', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'use_featured_image' => __('Nutze als Bild', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'insert_into_item' => __('Füge dem Eintrag hinzu', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'uploaded_to_this_item' => __('Upload für den Eintrag', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'items_list' => __('Eintragsliste', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'items_list_navigation' => __('Eintragsliste Navigation', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'filter_items_list' => __('Filtere Eintragsliste', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
        );

        $capabilities = array(
            // Meta capabilities
            'edit_post' => self::EDIT_INTERAKTIV,
            'read_post' => self::READ_INTERAKTIV,
            'delete_post' => self::DELETE_INTERAKTIV,

            // Primitive capabilities used outside of map_meta_cap():
            'edit_posts' => self::EDIT_INTERAKTIVS,
            'edit_others_posts' => self::EDIT_OTHERS_INTERAKTIVS,
            'publish_posts' => self::PUBLISH_INTERAKTIVS,
            'read_private_posts' => self::READ_PRIVATE_INTERAKTIVS,

            // Primitive capabilities used within map_meta_cap():
            'read' => self::READ,
            'delete_posts' => self::DELETE_INTERAKTIVS,
            'delete_private_posts' => self::DELETE_PRIVATE_INTERAKTIVS,
            'delete_published_posts' => self::DELETE_PUBLISHED_INTERAKTIVS,
            'delete_others_posts' => self::DELETE_OTHERS_INTERAKTIVS,
            'edit_private_posts' => self::DELETE_PRIVATE_INTERAKTIVS,
            'edit_published_posts' => self::EDIT_PUBLISHED_INTERAKTIVS,
            'create_posts' => self::CREATE_INTERAKTIVS,
        );

        $args = array(
            'label' => __('Interaktiv', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'description' => __('Grünes Brett etc.', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'labels' => $labels,
            'supports' => array('author', 'title', 'editor', 'comments', 'post-formats'),
            //'taxonomies' => array(),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 3,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => self::INTERAKTIV,
            'capabilities' => $capabilities,
            'meta_map_cap' => false,
            'delete_with_user' => false, // keep content when user will be deleted or trashed
            'rewrite' => array('slug' => __(self::INTERAKTIV), 'with_front' => false),
        );
        register_post_type(self::INTERAKTIV, $args);

        flush_rewrite_rules();

    }

    function add_interaktiv_to_post_type($query)
    {
        if ((is_home() && $query->is_main_query()) || is_feed()) {
            $query->set(self::POST_TYPE, array(self::POST, self::INTERAKTIV));
        }
    }

    function interaktiv_map_meta_cap($caps, $cap, $user_id, $args)
    {
        /* If editing, deleting, or reading a entry, get the post and post type object. */
        if (self::EDIT_INTERAKTIV == $cap || self::DELETE_INTERAKTIV == $cap || self::READ_INTERAKTIV == $cap) {
            $post = get_post($args[0]);
            $post_type = get_post_type_object($post->post_type);
            $caps = array();
        }
        elseif (self::EDIT_COMMENT == $cap) {
            $comment = get_comment($args[0]);
        }

        /* If editing a entry, assign the required capability. */
        if (self::EDIT_INTERAKTIV == $cap) {
            if ($user_id == $post->post_author)
                $caps[] = $post_type->cap->edit_posts;
            else
                $caps[] = $post_type->cap->edit_others_posts;
        } /* If deleting a entry, assign the required capability. */
        elseif (self::DELETE_INTERAKTIV == $cap) {
            if ($user_id == $post->post_author)
                $caps[] = $post_type->cap->delete_posts;
            else
                $caps[] = $post_type->cap->delete_others_posts;
        } /* If reading a entry, assign the required capability. */
        elseif (self::READ_INTERAKTIV == $cap) {
            if ('private' != $post->post_status)
                $caps[] = self::READ;
            elseif ($user_id == $post->post_author)
                $caps[] = self::READ;
            else
                $caps[] = $post_type->cap->read_private_posts;
        }
        elseif (self::EDIT_COMMENT == $cap) {
            if ($user_id != $comment->user_id)
			    $caps[] = 'moderate_comments';
        }
        return $caps;
    }

    function unregister_interaktiv()
    {
        unregister_post_type(self::INTERAKTIV);
    }


    function get_interaktiv_allowed_project_formats()
    {
        return array('aside', 'status');
    }

    function interaktiv_post_format_support_filter()
    {
        $screen = get_current_screen();

        // Bail if not on the projects screen.
        if (empty($screen->post_type) || $screen->post_type !== self::INTERAKTIV)
            return;

        // Check if the current theme supports formats.
        if (current_theme_supports(self::POST_FORMATS)) {

            $formats = get_theme_support(self::POST_FORMATS);

            // If we have formats, add theme support for only the allowed formats.
            if (isset($formats[0])) {
                $new_formats = array_intersect($formats[0], $this->get_interaktiv_allowed_project_formats());

                // Remove post formats support.
                remove_theme_support(self::POST_FORMATS);

                // If the theme supports the allowed formats, add support for them.
                if ($new_formats)
                    add_theme_support(self::POST_FORMATS, $new_formats);
            }
        }

    }


    function interaktiv_default_post_format_filter($format)
    {
        return in_array($format, $this->get_interaktiv_allowed_project_formats()) ? $format : 'status';
    }
}

$plugin = new InteraktivPlugin();

register_activation_hook(__FILE__, array($plugin, 'activate'));
register_deactivation_hook(__FILE__, array($plugin, 'deactivate'));
