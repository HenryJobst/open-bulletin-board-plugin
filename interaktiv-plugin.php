<?php

/*
Plugin Name: Interaktiv Plugin
Plugin URI: https://github.com/HenryJobst/interaktiv-plugin
Description: This plugin add a new custom post type "Interaktiv", which is editable for all registered users.
Version: 1.0.3
Author: Henry Jobst
Author URI: https://github.com/HenryJobst
Text Domain: interaktiv-plugin
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

    function __construct()
    {
        add_action('init', array($this, 'register_interaktiv'));
        add_filter('map_meta_cap', array($this, 'interaktiv_map_meta_cap'), 10, 4);
        add_action('pre_get_posts', array($this, 'add_interaktiv_to_post_type'));
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
            'name' => __('Interaktiv'),
            'singular_name' => __('Interaktiv'),
            'menu_name' => __('Interaktiv'),
            'name_admin_bar' => __('Interaktiv'),
            'archives' => __('Archiv'),
            'attributes' => __('Attribute'),
            'parent_item_colon' => __('Eltern Eintrag:'),
            'all_items' => __('Alle Einträge'),
            'add_new_item' => __('Neuer Eintrag'),
            'add_new' => __('Erstellen'),
            'new_item' => __('Neuer Eintrag'),
            'edit_item' => __('Bearbeite Eintrag'),
            'update_item' => __('Aktualisiere Eintrag'),
            'view_item' => __('Zeige Eintrag'),
            'view_items' => __('Zeige Einträge'),
            'search_items' => __('Suche Einträge'),
            'not_found' => __('Nicht gefunden'),
            'not_found_in_trash' => __('Nicht im Papierkorb gefunden'),
            'featured_image' => __('Bild'),
            'set_featured_image' => __('Setze Bild'),
            'remove_featured_image' => __('Entferne Bild'),
            'use_featured_image' => __('Nutze als Bild'),
            'insert_into_item' => __('Füge dem Eintrag hinzu'),
            'uploaded_to_this_item' => __('Upload für den Eintrag'),
            'items_list' => __('Eintragsliste'),
            'items_list_navigation' => __('Eintragsliste Navigation'),
            'filter_items_list' => __('Filtere Eintragsliste'),
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
            'label' => __('Interaktiv'),
            'description' => __('Grünes Brett etc.'),
            'labels' => $labels,
            'supports' => array('title', 'author', 'editor', 'comments'),
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
            'capabilities' => $capabilities,
            'delete_with_user' => false, // keep content when user will be deleted or trashed
            'rewrite' => array('slug' => __(self::INTERAKTIV), 'with_front' => false),
        );
        register_post_type(self::INTERAKTIV, $args);

        flush_rewrite_rules();

    }

    function add_interaktiv_to_post_type($query)
    {
        if ((is_home() && $query->is_main_query()) || is_feed()) {
            $query->set('post_type', array('post', self::INTERAKTIV));
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
        return $caps;
    }

    function unregister_interaktiv()
    {
        unregister_post_type(self::INTERAKTIV);
    }

}

$plugin = new InteraktivPlugin();

register_activation_hook(__FILE__, array($plugin, 'activate'));
register_deactivation_hook(__FILE__, array($plugin, 'deactivate'));