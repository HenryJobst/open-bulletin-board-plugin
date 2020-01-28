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
    const SUBSCRIBER_ROLE = 'subscriber';
    const CONTRIBUTOR_ROLE = 'contributor';
    const AUTHOR_ROLE = 'author';
    const EDITOR_ROLE = 'editor';
    const ADMIN_ROLE = 'administrator';

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

    const EDIT_COMMENT = 'edit_comment';
    const MODERATE_COMMENTS = 'moderate_comments';


    const ADD_PRIORITY = 10;
    const ADD_PARAMETER_COUNT2 = 2;
    const ADD_PARAMETER_COUNT4 = 4;

    const POST_FORMATS = 'post-formats';
    const POST_TYPE = 'post_type';
    const POST = 'post';

    // columns
    const CONTENT = 'content';
    const COMMENTS = 'comments';
    const COMMENT_COUNT = 'comment_count';
    const AUTHOR = 'author';
    const POST_TAG = 'post_tag';
    const ORDER_BY = 'orderby';
    const THE_CONTENT = 'the_content';
    const PRIVATE = 'private';
    const DATE = 'date';
    const CB = 'cb';
    const NAME = 'name';
    const URL = 'url';
    const EMAIL = 'email';
    const NAME_META_BOX_NONCE = self::INTERAKTIV . '_' . self::POST_TYPE . '_' . self::NAME . '_meta_box_nonce';

    function __construct()
    {
        // add custom post type
        add_action('init', array($this, 'register_interaktiv'));

        // set special mapping function for per post capabilities
        add_filter('map_meta_cap', array($this, 'interaktiv_map_meta_cap'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT4);

        // add custom post type to standard post loop
        add_action('pre_get_posts', array($this, 'add_interaktiv_to_post_type'));

        // set post format filter
        add_action('load-post.php', array($this, 'interaktiv_post_format_support_filter'));
        add_action('load-post-new.php', array($this, 'interaktiv_post_format_support_filter'));
        add_action('load-edit.php', array($this, 'interaktiv_post_format_support_filter'));

        // Filter the default post format.
        add_filter('option_default_post_format', array($this, 'interaktiv_default_post_format_filter'));

        // Set columns for custom post type
        add_filter('manage_edit-interaktiv_columns', array($this, 'interaktiv_columns'));

        // Set custom columns for custom post type
        add_action('manage_interaktiv_posts_custom_column', array($this, 'manage_interaktiv_columns'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);

        // Set custom sortable columns for custom post type
        add_filter('manage_edit-interaktiv_sortable_columns', array($this, 'interaktiv_sortable_columns'));

        // Add meta boxes
        add_action('add_meta_boxes_interaktiv', array($this, 'interaktiv_post_type_add_meta_boxes'));

        // Add save of meta boxes
        add_action('save_post_interaktiv', array($this, 'interaktiv_post_type_save_name_meta_boxes_data'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);
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
        foreach (array(self::SUBSCRIBER_ROLE, self::CONTRIBUTOR_ROLE, self::AUTHOR_ROLE, self::EDITOR_ROLE, self::ADMIN_ROLE) as $role_name) {
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
        foreach (array(self::SUBSCRIBER_ROLE, self::CONTRIBUTOR_ROLE, self::AUTHOR_ROLE, self::EDITOR_ROLE, self::ADMIN_ROLE) as $role_name) {
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
        foreach (array(self::EDITOR_ROLE, self::ADMIN_ROLE) as $role_name) {
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
        foreach (array(self::EDITOR_ROLE, self::ADMIN_ROLE) as $role_name) {
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
            'item_published' => __('Eintrag veröffentlicht.', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'item_published_privately' => __('Eintrag privat veröffentlicht', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'item_reverted_to_draft' => __('Eintrag zum Entwurf zurückgestuft.', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'item_scheduled' => __('Eintrag eingeplant.', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            'item_updated' => __('Eintrag aktualisiert', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
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
            'supports' => array(self::AUTHOR, self::TITLE, 'editor', 'comments', 'post-formats', 'custom-fields'),
            'taxonomies' => array(self::POST_TAG),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-megaphone',
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

    function add_custom_interaktiv_meta_box($meta_box_id, $meta_box_title)
    {
        $plugin_prefix = self::INTERAKTIV . '_post_type_';

        $html_id_attribute = $plugin_prefix . $meta_box_id . '_meta_box';
        $php_callback_function = $plugin_prefix . 'build_' . $meta_box_id . '_meta_box';
        $show_me_on_post_type = self::INTERAKTIV;
        $box_placement = 'side';
        $box_priority = 'low';

        add_meta_box(
            $html_id_attribute,
            $meta_box_title,
            $php_callback_function,
            $show_me_on_post_type,
            $box_placement,
            $box_priority
        );
    }

    function interaktiv_post_type_add_meta_boxes($post)
    {
        $this->add_custom_interaktiv_meta_box(self::NAME, __('Name', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN));
        $this->add_custom_interaktiv_meta_box(self::URL, __('Homepage', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN));
        $this->add_custom_interaktiv_meta_box(self::EMAIL, __('E-Mail', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN));
    }

    function interaktiv_post_type_build_name_meta_box($post)
    {
        wp_nonce_field(basename(__FILE__), self::NAME_META_BOX_NONCE);

        $current_value = get_post_meta($post->ID, self::NAME, true);
        ?>
        <div class="inside">
            <section id="name-meta-box-container">
                <p>
                    <input type="text" name="name" id="name"<?php echo ' value="' . $current_value . '"'; ?>>
                </p>
            </section>
        </div>
        <?php
    }

    function interaktiv_post_type_save_name_meta_boxes_data($post_id)
    {
        if (!isset($_POST[self::NAME_META_BOX_NONCE]) ||
            !wp_verify_nonce($_POST[self::NAME_META_BOX_NONCE], basename(__FILE__))) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (defined('DOING_AJAX') && DOING_AJAX)
            return;

        if (!current_user_can('edit_post', $post_id))
            return;

        if (isset($_REQUEST[self::NAME])) {
            update_post_meta(
                $post_id,
                self::NAME,
                sanitize_text_field($_POST[self::NAME])
            );
        }
    }

    function the_name($post = 0, $echo = true)
    {
        $post = get_post($post);

        $id = isset($post->ID) ? $post->ID : 0;
        $value = get_post_meta($id, self::NAME, true);

        if ($echo) {
            echo sprintf('<span class="interaktiv-detail--name">%s</span>', esc_html($value));
        } else
            return $value;
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
        } elseif (self::EDIT_COMMENT == $cap) {
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
            if (self::PRIVATE != $post->post_status)
                $caps[] = self::READ;
            elseif ($user_id == $post->post_author)
                $caps[] = self::READ;
            else
                $caps[] = $post_type->cap->read_private_posts;
        } elseif (self::EDIT_COMMENT == $cap) {
            if ($user_id != $comment->user_id)
                $caps[] = self::MODERATE_COMMENTS;
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
        return in_array($format, $this->get_interaktiv_allowed_project_formats()) ? $format : 'Standard';
    }

    function interaktiv_columns($columns)
    {
        $columns = array(
            self::CB => '&lt;input type="checkbox" />',
            self::TITLE => __('Titel', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            self::CONTENT => __('Inhalt', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            self::NAME => __('Name', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            self::URL => __('Homepage', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            self::EMAIL => __('E-Mail', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            self::AUTHOR => __('Autor', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            self::POST_TAG => __('Schlagwörter', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            self::COMMENT_COUNT => __('Kommentare', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN),
            self::DATE => __('Datum', self::INTERAKTIV_PLUGIN_TEXT_DOMAIN)
        );
        return $columns;
    }

    function manage_interaktiv_columns($column, $post_id)
    {
        global $post;
        $post = get_post($post_id);
        setup_postdata($post);

        switch ($column) {
            case self::CONTENT:
                echo apply_filters(self::THE_CONTENT, $post->post_content);
                break;
            case self::COMMENT_COUNT:
                echo apply_filters(self::COMMENT_COUNT, $post->comment_count);
                break;
            case  self::POST_TAG:
                $post_tags = get_the_tags($post_id);
                $content = '';
                if ($post_tags) {
                    foreach ($post_tags as $tag) {
                        $tag_link = '<a href="edit.php?tag=' . $tag->slug . '">' . $tag->name . '</a>';
                        if ($content != '') {
                            $content = $content . ', ' . $tag_link;
                        } else {
                            $content = $tag_link;
                        }
                    }
                }
                echo apply_filters(self::POST_TAG, $content);
                break;
            /* Just break out of the switch statement for everything else. */
            default :
                break;
        }
    }

    function interaktiv_sortable_columns($columns)
    {
        $columns[self::TITLE] = self::TITLE;
        $columns[self::AUTHOR] = self::AUTHOR;
        $columns[self::NAME] = self::NAME;
        $columns[self::URL] = self::URL;
        $columns[self::EMAIL] = self::EMAIL;
        $columns[self::COMMENT_COUNT] = self::COMMENT_COUNT;
        $columns[self::POST_TAG] = self::POST_TAG;
        return $columns;
    }
}

https://codex.wordpress.org/I18n_for_WordPress_Developers

$plugin = new InteraktivPlugin();

register_activation_hook(__FILE__, array($plugin, 'activate'));
register_deactivation_hook(__FILE__, array($plugin, 'deactivate'));
