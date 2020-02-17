<?php

/*
Plugin Name: Interaktiv Plugin
Plugin URI: https://github.com/HenryJobst/interaktiv-plugin
Description: This plugin add a new custom post type "Interaktiv", which is editable for all registered users.
Version: 1.0.4
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

function the_column($post_id = 0, $echo = true, $column = 'name')
{
    $post = get_post($post_id);

    $id = isset($post->ID) ? $post->ID : 0;
    $value = get_post_meta($id, $column, true);

    if ($echo) {
        echo sprintf('<span class="interaktiv-detail--%s">%s</span>', $column, esc_html($value));
        return $value;
    } else
        return $value;
}

class InteraktivPlugin
{
    // post type name
    const INTERAKTIV = 'interaktiv';

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
    const TITLE = 'title';
    const LOCATION = 'location';
    const PHONE = 'phone';

    const META_BOX = '_meta_box';
    const META_BOX_NONCE = self::META_BOX . '_nonce';
    const PLUGIN_PREFIX = self::INTERAKTIV . '_' . self::POST_TYPE . '_';

    const NAME_META_BOX_NONCE = self::PLUGIN_PREFIX . self::NAME . self::META_BOX_NONCE;
    const URL_META_BOX_NONCE = self::PLUGIN_PREFIX . self::URL . self::META_BOX_NONCE;
    const PHONE_META_BOX_NONCE = self::PLUGIN_PREFIX . self::PHONE . self::META_BOX_NONCE;
    const EMAIL_META_BOX_NONCE = self::PLUGIN_PREFIX . self::EMAIL . self::META_BOX_NONCE;
    const LOCATION_META_BOX_NONCE = self::PLUGIN_PREFIX . self::LOCATION . self::META_BOX_NONCE;

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
        add_action('save_post_interaktiv', array($this, 'interaktiv_post_type_save_url_meta_boxes_data'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);
        add_action('save_post_interaktiv', array($this, 'interaktiv_post_type_save_phone_meta_boxes_data'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);
        add_action('save_post_interaktiv', array($this, 'interaktiv_post_type_save_email_meta_boxes_data'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);
        add_action('save_post_interaktiv', array($this, 'interaktiv_post_type_save_location_meta_boxes_data'),
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
            'name' => __('Interaktiv', 'interaktiv-plugin-text-domain'),
            'singular_name' => __('Interaktiv', 'interaktiv-plugin-text-domain'),
            'menu_name' => __('Interaktiv', 'interaktiv-plugin-text-domain'),
            'name_admin_bar' => __('Interaktiv', 'interaktiv-plugin-text-domain'),
            'archives' => __('Archiv', 'interaktiv-plugin-text-domain'),
            'attributes' => __('Attribute', 'interaktiv-plugin-text-domain'),
            'parent_item_colon' => __('Eltern Eintrag:', 'interaktiv-plugin-text-domain'),
            'all_items' => __('Alle Einträge', 'interaktiv-plugin-text-domain'),
            'add_new_item' => __('Neuer Eintrag', 'interaktiv-plugin-text-domain'),
            'add_new' => __('Erstellen', 'interaktiv-plugin-text-domain'),
            'new_item' => __('Neuer Eintrag', 'interaktiv-plugin-text-domain'),
            'edit_item' => __('Bearbeite Eintrag', 'interaktiv-plugin-text-domain'),
            'update_item' => __('Aktualisiere Eintrag', 'interaktiv-plugin-text-domain'),
            'view_item' => __('Zeige Eintrag', 'interaktiv-plugin-text-domain'),
            'view_items' => __('Zeige Einträge', 'interaktiv-plugin-text-domain'),
            'search_items' => __('Suche Einträge', 'interaktiv-plugin-text-domain'),
            'not_found' => __('Nicht gefunden', 'interaktiv-plugin-text-domain'),
            'not_found_in_trash' => __('Nicht im Papierkorb gefunden', 'interaktiv-plugin-text-domain'),
            'featured_image' => __('Bild', 'interaktiv-plugin-text-domain'),
            'set_featured_image' => __('Setze Bild', 'interaktiv-plugin-text-domain'),
            'remove_featured_image' => __('Entferne Bild', 'interaktiv-plugin-text-domain'),
            'use_featured_image' => __('Nutze als Bild', 'interaktiv-plugin-text-domain'),
            'insert_into_item' => __('Füge dem Eintrag hinzu', 'interaktiv-plugin-text-domain'),
            'uploaded_to_this_item' => __('Upload für den Eintrag', 'interaktiv-plugin-text-domain'),
            'items_list' => __('Eintragsliste', 'interaktiv-plugin-text-domain'),
            'items_list_navigation' => __('Eintragsliste Navigation', 'interaktiv-plugin-text-domain'),
            'filter_items_list' => __('Filtere Eintragsliste', 'interaktiv-plugin-text-domain'),
            'item_published' => __('Eintrag veröffentlicht.', 'interaktiv-plugin-text-domain'),
            'item_published_privately' => __('Eintrag privat veröffentlicht', 'interaktiv-plugin-text-domain'),
            'item_reverted_to_draft' => __('Eintrag zum Entwurf zurückgestuft.', 'interaktiv-plugin-text-domain'),
            'item_scheduled' => __('Eintrag eingeplant.', 'interaktiv-plugin-text-domain'),
            'item_updated' => __('Eintrag aktualisiert', 'interaktiv-plugin-text-domain'),
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
            'label' => __('Interaktiv', 'interaktiv-plugin-text-domain'),
            'description' => __('Grünes Brett etc.', 'interaktiv-plugin-text-domain'),
            'labels' => $labels,
            'supports' => array(self::AUTHOR, self::TITLE, 'editor', 'comments', 'post-formats'),
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
        $html_id_attribute = self::PLUGIN_PREFIX . $meta_box_id . self::META_BOX;
        $php_callback_function = array($this, self::PLUGIN_PREFIX . 'build_' . $meta_box_id . self::META_BOX);
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
        $this->add_custom_interaktiv_meta_box(self::NAME, __('Name', 'interaktiv-plugin-text-domain'));
        $this->add_custom_interaktiv_meta_box(self::URL, __('Homepage', 'interaktiv-plugin-text-domain'));
        $this->add_custom_interaktiv_meta_box(self::PHONE, __('Telefon', 'interaktiv-plugin-text-domain'));
        $this->add_custom_interaktiv_meta_box(self::EMAIL, __('E-Mail', 'interaktiv-plugin-text-domain'));
        $this->add_custom_interaktiv_meta_box(self::LOCATION, __('Ort', 'interaktiv-plugin-text-domain'));
    }

    function interaktiv_post_type_build_meta_box($post, $column)
    {
        $column_nonce = self::PLUGIN_PREFIX . $column . self::META_BOX_NONCE;
        wp_nonce_field(basename(__FILE__), $column_nonce);

        $current_value = get_post_meta($post->ID, $column, true);
        ?>
        <div class="inside">
            <section id="<? echo $column . "-meta-box-container"; ?>">
                <p>
                    <label>
                        <input type="text"
                               name=<? echo $column . ' id="' . $column . '" value="' . $current_value . '"'; ?>>
                    </label>
                </p>
            </section>
        </div>
        <?php
    }

    function interaktiv_post_type_build_name_meta_box($post)
    {
        $this->interaktiv_post_type_build_meta_box($post, self::NAME);
    }

    function interaktiv_post_type_build_url_meta_box($post)
    {
        $this->interaktiv_post_type_build_meta_box($post, self::URL);
    }

    function interaktiv_post_type_build_phone_meta_box($post)
    {
        $this->interaktiv_post_type_build_meta_box($post, self::PHONE);
    }

    function interaktiv_post_type_build_email_meta_box($post)
    {
        $this->interaktiv_post_type_build_meta_box($post, self::EMAIL);
    }

    function interaktiv_post_type_build_location_meta_box($post)
    {
        $this->interaktiv_post_type_build_meta_box($post, self::LOCATION);
    }

    function interaktiv_post_type_save_meta_boxes_data($post_id, $column)
    {
        $column_nonce = self::PLUGIN_PREFIX . $column . self::META_BOX_NONCE;

        if (!isset($_POST[$column_nonce]) ||
            !wp_verify_nonce($_POST[$column_nonce], basename(__FILE__))) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (defined('DOING_AJAX') && DOING_AJAX)
            return;

        if (!current_user_can('edit_post', $post_id))
            return;

        if (isset($_REQUEST[$column])) {
            update_post_meta($post_id, $column, sanitize_text_field($_POST[$column]));
        }
    }

    function interaktiv_post_type_save_name_meta_boxes_data($post_id)
    {
        $this->interaktiv_post_type_save_meta_boxes_data($post_id, self::NAME);
    }

    function interaktiv_post_type_save_url_meta_boxes_data($post_id)
    {
        $this->interaktiv_post_type_save_meta_boxes_data($post_id, self::URL);
    }

    function interaktiv_post_type_save_phone_meta_boxes_data($post_id)
    {
        $this->interaktiv_post_type_save_meta_boxes_data($post_id, self::PHONE);
    }

    function interaktiv_post_type_save_email_meta_boxes_data($post_id)
    {
        $this->interaktiv_post_type_save_meta_boxes_data($post_id, self::EMAIL);
    }

    function interaktiv_post_type_save_location_meta_boxes_data($post_id)
    {
        $this->interaktiv_post_type_save_meta_boxes_data($post_id, self::LOCATION);
    }

    function the_name($post = 0, $echo = true)
    {
        return the_column($post, $echo, self::NAME);
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
            self::TITLE => __('Titel', 'interaktiv-plugin-text-domain'),
            self::CONTENT => __('Inhalt', 'interaktiv-plugin-text-domain'),
            self::NAME => __('Name', 'interaktiv-plugin-text-domain'),
            self::URL => __('Homepage', 'interaktiv-plugin-text-domain'),
            self::PHONE => __('Telefon', 'interaktiv-plugin-text-domain'),
            self::EMAIL => __('E-Mail', 'interaktiv-plugin-text-domain'),
            self::AUTHOR => __('Autor', 'interaktiv-plugin-text-domain'),
            self::POST_TAG => __('Schlagwörter', 'interaktiv-plugin-text-domain'),
            self::COMMENT_COUNT => __('Kommentare', 'interaktiv-plugin-text-domain'),
            self::LOCATION => __('Ort', 'interaktiv-plugin-text-domain'),
            self::DATE => __('Datum', 'interaktiv-plugin-text-domain')
        );
        return $columns;
    }

    /**
     * truncateHtml can truncate a string up to a number of characters while preserving whole words and HTML tags
     * from: https://alanwhipple.com/2011/05/25/php-truncate-string-preserving-html-tags-words/
     *
     * @param string $text String to truncate.
     * @param integer $length Length of returned string, including ellipsis.
     * @param string $ending Ending to be appended to the trimmed string.
     * @param boolean $exact If false, $text will not be cut mid-word
     * @param boolean $considerHtml If true, HTML tags would be handled correctly
     *
     * @return string Trimmed string.
     */
    function truncateHtml($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
    {
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = array();
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&amp;[0-9a-z]{2,8};|&amp;#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length + $content_length > $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&amp;[0-9a-z]{2,8};|&amp;#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        return $truncate;
    }

    function manage_interaktiv_columns($column, $post_id)
    {
        global $post;
        $post = get_post($post_id);
        setup_postdata($post);

        switch ($column) {
            case self::CONTENT:
                echo apply_filters(self::THE_CONTENT, $this->truncateHtml($post->post_content));
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
            case self::NAME:
                $name = get_post_meta($post_id, self::NAME, true);
                echo apply_filters(self::NAME, $name);
                break;
            case self::URL:
                echo apply_filters(self::URL, get_post_meta($post_id, self::URL, true));
                break;
            case self::PHONE:
                echo apply_filters(self::PHONE, get_post_meta($post_id, self::PHONE, true));
                break;
            case self::EMAIL:
                echo apply_filters(self::EMAIL, get_post_meta($post_id, self::EMAIL, true));
                break;
            case self::LOCATION:
                echo apply_filters(self::LOCATION, get_post_meta($post_id, self::LOCATION, true));
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
        $columns[self::CONTENT] = self::CONTENT;
        $columns[self::NAME] = self::NAME;
        $columns[self::URL] = self::URL;
        $columns[self::PHONE] = self::PHONE;
        $columns[self::EMAIL] = self::EMAIL;
        $columns[self::COMMENT_COUNT] = self::COMMENT_COUNT;
        $columns[self::POST_TAG] = self::POST_TAG;
        $columns[self::LOCATION] = self::LOCATION;
        $columns[self::DATE] = self::DATE;
        return $columns;
    }

    function get_column($preamble, $column)
    {
        $value = the_column(get_the_ID(), false, $column);
        if (isset($value)):
            return sprintf('%s', $preamble . esc_html($value));
        endif;
        return '';
    }

    function interaktiv_the_title($title, $post_id)
    {

        $post = get_post($post_id);
        if ($post->post_type != self::INTERAKTIV) {
            return $title;
        }

        $title = the_column($post_id, false, self::TITLE);
        $name = the_column($post_id, false, self::NAME);
        $homepage = the_column($post_id, false, self::URL);
        $phone = the_column($post_id, false, self::PHONE);
        $email = the_column($post_id, false, self::EMAIL);
        $location = the_column($post_id, false, self::LOCATION);

        if (!isset($title) or $title == ''):
            $title = '<div itemscope itemtype="http://schema.org/Person">';
            if ($name != ''):
                $title .= '<span class="interactive-entry-title-name" itemprop="name"><strong>' . $name . '</strong></span>';
            endif;
            if ($homepage != ''):
                if ($name != ''):
                    $title .= '&nbsp;';
                endif;
                $title .= '<span itemprop="url">[<a class="interaktiv-entry-title-url" href="' . $homepage . '">' . __('Seite', 'interaktiv-plugin-text-domain') . '</a>]</span>';
            endif;
            if ($phone != ''):
                if ($name != '' || $homepage != ''):
                    $title .= '&nbsp;';
                endif;
                $title .= '<span itemprop="telephone">[<a class="interaktiv-entry-title-phone" href="tel:' . $phone . '">' . __('Telefon', 'interaktiv-plugin-text-domain') . '</a>]</span>';
            endif;
            if ($email != ''):
                if ($name != '' || $homepage != '' || $phone != ''):
                    $title .= '&nbsp;';
                endif;
                $title .= '<span itemprop="email">[<a class="interaktiv-entry-title-email" href="mailto:' . $email . '">' . __('E-Mail', 'interaktiv-plugin-text-domain') . '</a>]</span>';
            endif;
            $title .= '<span class="alignright">';
            if ($location != ''):
                $title .= $location . ', ';
            else:
                $title .= '&nbsp';
            endif;
            $title .= the_date('', '', '', false);
            $title .= '</span>';
            $title .= '</div>';
        endif;

        return $title;
    }
}

$plugin = new InteraktivPlugin();

register_activation_hook(__FILE__, array($plugin, 'activate'));
register_deactivation_hook(__FILE__, array($plugin, 'deactivate'));
