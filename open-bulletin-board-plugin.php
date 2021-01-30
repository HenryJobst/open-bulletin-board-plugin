<?php

/*
Plugin Name: Open Bulletin Board Plugin
Plugin URI: https://github.com/HenryJobst/open-bulletin-board-plugin
Description: This plugin add a new custom post type "Bulletin board" (post_type name "interaktiv"), which is editable for all registered users.
Version: 1.0.6
Author: Henry Jobst
Author URI: https://github.com/HenryJobst
Text Domain: open-bulletin-board-plugin-text-domain
License: MIT License

Copyright (c) 2020-2021 {Author}

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

class OpenBulletinBoardPlugin
{
    // post type name
    const OPEN_BULLETIN_BOARD_POST_TYPE = 'interaktiv';

    // roles
    const SUBSCRIBER_ROLE = 'subscriber';
    const CONTRIBUTOR_ROLE = 'contributor';
    const AUTHOR_ROLE = 'author';
    const EDITOR_ROLE = 'editor';
    const ADMIN_ROLE = 'administrator';

    // Meta capabilities
    const EDIT_OPBBRD = 'edit_opbbrd';
    const READ_OPBBRD = 'read_opbbrd';
    const DELETE_OPBBRD = 'delete_opbbrd';

    // Primitive capabilities used outside of map_meta_cap():
    const EDIT_OPBBRDS = "edit_opbbrds";
    const EDIT_OTHERS_OPBBRDS = 'edit_others_opbbrds';
    const PUBLISH_OPBBRDS = 'publish_opbbrds';
    const READ_PRIVATE_OPBBRDS = 'read_private_opbbrds';

    // Primitive capabilities used within map_meta_cap():
    const READ = 'read';
    const DELETE_OPBBRDS = 'delete_opbbrds';
    const DELETE_PRIVATE_OPBBRDS = 'delete_private_opbbrds';
    const DELETE_PUBLISHED_OPBBRDS = 'delete_published_opbbrds';
    const DELETE_OTHERS_OPBBRDS = 'delete_others_opbbrds';
    const EDIT_PRIVATE_OPBBRDS = 'edit_private_opbbrds';
    const EDIT_PUBLISHED_OPBBRDS = 'edit_published_opbbrds';
    const CREATE_OPBBRDS = 'create_opbbrds';

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
    const PLUGIN_PREFIX = self::OPEN_BULLETIN_BOARD_POST_TYPE . '_' . self::POST_TYPE . '_';

    const NAME_META_BOX_NONCE = self::PLUGIN_PREFIX . self::NAME . self::META_BOX_NONCE;
    const URL_META_BOX_NONCE = self::PLUGIN_PREFIX . self::URL . self::META_BOX_NONCE;
    const PHONE_META_BOX_NONCE = self::PLUGIN_PREFIX . self::PHONE . self::META_BOX_NONCE;
    const EMAIL_META_BOX_NONCE = self::PLUGIN_PREFIX . self::EMAIL . self::META_BOX_NONCE;
    const LOCATION_META_BOX_NONCE = self::PLUGIN_PREFIX . self::LOCATION . self::META_BOX_NONCE;

    function __construct()
    {
        // add custom post type
        add_action('init', array($this, 'register_opbbrd'));

        // set special mapping function for per post capabilities
        add_filter('map_meta_cap', array($this, 'opbbrd_map_meta_cap'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT4);

        // add custom post type to standard post loop
        add_action('pre_get_posts', array($this, 'add_opbbrd_to_post_type'));

        // set post format filter
        add_action('load-post.php', array($this, 'opbbrd_post_format_support_filter'));
        add_action('load-post-new.php', array($this, 'opbbrd_post_format_support_filter'));
        add_action('load-edit.php', array($this, 'opbbrd_post_format_support_filter'));

        // Filter the default post format.
        add_filter('option_default_post_format', array($this, 'opbbrd_default_post_format_filter'));

        // Set columns for custom post type
        add_filter('manage_edit-opbbrd_columns', array($this, 'opbbrd_columns'));

        // Set custom columns for custom post type
        add_action('manage_opbbrd_posts_custom_column', array($this, 'manage_opbbrd_columns'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);

        // Set custom sortable columns for custom post type
        add_filter('manage_edit-opbbrd_sortable_columns', array($this, 'opbbrd_sortable_columns'));

        // Add meta boxes
        add_action('add_meta_boxes_opbbrd', array($this, 'opbbrd_post_type_add_meta_boxes'));

        // Add save of meta boxes
        add_action('save_post_opbbrd', array($this, 'opbbrd_post_type_save_name_meta_boxes_data'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);
        add_action('save_post_opbbrd', array($this, 'opbbrd_post_type_save_url_meta_boxes_data'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);
        add_action('save_post_opbbrd', array($this, 'opbbrd_post_type_save_phone_meta_boxes_data'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);
        add_action('save_post_opbbrd', array($this, 'opbbrd_post_type_save_email_meta_boxes_data'),
            self::ADD_PRIORITY, self::ADD_PARAMETER_COUNT2);
        add_action('save_post_opbbrd', array($this, 'opbbrd_post_type_save_location_meta_boxes_data'),
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
        $this->unregister_opbbrd();
        flush_rewrite_rules();
    }

    function enable_edit_opbbrd_for_all()
    {
        foreach (array(self::SUBSCRIBER_ROLE, self::CONTRIBUTOR_ROLE, self::AUTHOR_ROLE, self::EDITOR_ROLE, self::ADMIN_ROLE) as $role_name) {
            $role = get_role($role_name);
            $role->add_cap(self::CREATE_OPBBRDS);
            $role->add_cap(self::EDIT_OPBBRDS);
            $role->add_cap(self::DELETE_OPBBRDS);
            $role->add_cap(self::PUBLISH_OPBBRDS);
            $role->add_cap(self::EDIT_PUBLISHED_OPBBRDS);
        }
    }

    function disable_edit_opbbrd_for_all()
    {
        foreach (array(self::SUBSCRIBER_ROLE, self::CONTRIBUTOR_ROLE, self::AUTHOR_ROLE, self::EDITOR_ROLE, self::ADMIN_ROLE) as $role_name) {
            $role = get_role($role_name);
            $role->remove_cap(self::CREATE_OPBBRDS);
            $role->remove_cap(self::EDIT_OPBBRDS);
            $role->remove_cap(self::DELETE_OPBBRDS);
            $role->remove_cap(self::PUBLISH_OPBBRDS);
            $role->remove_cap(self::EDIT_PUBLISHED_OPBBRDS);
        }
    }

    function enable_others_opbbrd_for_editor()
    {
        foreach (array(self::EDITOR_ROLE, self::ADMIN_ROLE) as $role_name) {
            $role = get_role($role_name);
            $role->add_cap(self::EDIT_OTHERS_OPBBRDS);
            $role->add_cap(self::DELETE_OTHERS_OPBBRDS);
            $role->add_cap(self::READ_PRIVATE_OPBBRDS);
            $role->add_cap(self::EDIT_PUBLISHED_OPBBRDS);
            $role->add_cap(self::EDIT_PRIVATE_OPBBRDS);
            $role->add_cap(self::DELETE_PUBLISHED_OPBBRDS);
            $role->add_cap(self::DELETE_PRIVATE_OPBBRDS);
        }
    }

    function disable_others_opbbrd_for_editor()
    {
        foreach (array(self::EDITOR_ROLE, self::ADMIN_ROLE) as $role_name) {
            $role = get_role($role_name);
            $role->remove_cap(self::EDIT_OTHERS_OPBBRDS);
            $role->remove_cap(self::DELETE_OTHERS_OPBBRDS);
            $role->remove_cap(self::READ_PRIVATE_OPBBRDS);
            $role->remove_cap(self::EDIT_PUBLISHED_OPBBRDS);
            $role->remove_cap(self::EDIT_PRIVATE_OPBBRDS);
            $role->remove_cap(self::DELETE_PUBLISHED_OPBBRDS);
            $role->remove_cap(self::DELETE_PRIVATE_OPBBRDS);
        }
    }

    function set_rights()
    {
        $this->enable_edit_opbbrd_for_all();
        $this->enable_others_opbbrd_for_editor();
    }

    function unset_rights()
    {
        $this->disable_edit_opbbrd_for_all();
        $this->disable_others_opbbrd_for_editor();
    }

    function register_opbbrd()
    {
        $labels = array(
            'name' => __('Bulletin board items', 'open-bulletin-board-plugin-text-domain'),
            'singular_name' => __('Bulletin board item', 'open-bulletin-board-plugin-text-domain'),
            'menu_name' => __('Bulletin board', 'open-bulletin-board-plugin-text-domain'),
            'name_admin_bar' => __('Bulletin board', 'open-bulletin-board-plugin-text-domain'),
            'archives' => __('Archives', 'open-bulletin-board-plugin-text-domain'),
            'attributes' => __('Attributes', 'open-bulletin-board-plugin-text-domain'),
            'parent_item_colon' => __('Parent item:', 'open-bulletin-board-plugin-text-domain'),
            'all_items' => __('All items', 'open-bulletin-board-plugin-text-domain'),
            'add_new_item' => __('Add new item', 'open-bulletin-board-plugin-text-domain'),
            'add_new' => __('Add new', 'open-bulletin-board-plugin-text-domain'),
            'new_item' => __('New item', 'open-bulletin-board-plugin-text-domain'),
            'edit_item' => __('Edit item', 'open-bulletin-board-plugin-text-domain'),
            'update_item' => __('Update item', 'open-bulletin-board-plugin-text-domain'),
            'view_item' => __('View item', 'open-bulletin-board-plugin-text-domain'),
            'view_items' => __('View items', 'open-bulletin-board-plugin-text-domain'),
            'search_items' => __('Search items', 'open-bulletin-board-plugin-text-domain'),
            'not_found' => __('Not found', 'open-bulletin-board-plugin-text-domain'),
            'not_found_in_trash' => __('Not found in trash bin', 'open-bulletin-board-plugin-text-domain'),
            'featured_image' => __('Image', 'open-bulletin-board-plugin-text-domain'),
            'set_featured_image' => __('Set image', 'open-bulletin-board-plugin-text-domain'),
            'remove_featured_image' => __('Remove image', 'open-bulletin-board-plugin-text-domain'),
            'use_featured_image' => __('Use image', 'open-bulletin-board-plugin-text-domain'),
            'insert_into_item' => __('Insert into item', 'open-bulletin-board-plugin-text-domain'),
            'uploaded_to_this_item' => __('Upload to this item', 'open-bulletin-board-plugin-text-domain'),
            'items_list' => __('Items list', 'open-bulletin-board-plugin-text-domain'),
            'items_list_navigation' => __('Items list navigation', 'open-bulletin-board-plugin-text-domain'),
            'filter_items_list' => __('Filter items list', 'open-bulletin-board-plugin-text-domain'),
            'item_published' => __('Item published', 'open-bulletin-board-plugin-text-domain'),
            'item_published_privately' => __('Item published privately', 'open-bulletin-board-plugin-text-domain'),
            'item_reverted_to_draft' => __('Item reverted to draft', 'open-bulletin-board-plugin-text-domain'),
            'item_scheduled' => __('Item scheduled', 'open-bulletin-board-plugin-text-domain'),
            'item_updated' => __('Item updated', 'open-bulletin-board-plugin-text-domain'),
        );

        $capabilities = array(
            // Meta capabilities
            'edit_post' => self::EDIT_OPBBRD,
            'read_post' => self::READ_OPBBRD,
            'delete_post' => self::DELETE_OPBBRD,

            // Primitive capabilities used outside of map_meta_cap():
            'edit_posts' => self::EDIT_OPBBRDS,
            'edit_others_posts' => self::EDIT_OTHERS_OPBBRDS,
            'publish_posts' => self::PUBLISH_OPBBRDS,
            'read_private_posts' => self::READ_PRIVATE_OPBBRDS,

            // Primitive capabilities used within map_meta_cap():
            'read' => self::READ,
            'delete_posts' => self::DELETE_OPBBRDS,
            'delete_private_posts' => self::DELETE_PRIVATE_OPBBRDS,
            'delete_published_posts' => self::DELETE_PUBLISHED_OPBBRDS,
            'delete_others_posts' => self::DELETE_OTHERS_OPBBRDS,
            'edit_private_posts' => self::DELETE_PRIVATE_OPBBRDS,
            'edit_published_posts' => self::EDIT_PUBLISHED_OPBBRDS,
            'create_posts' => self::CREATE_OPBBRDS,
        );

        $args = array(
            'label' => __('Bulletin board', 'open-bulletin-board-plugin-text-domain'),
            'description' => __('Digital bulletin board', 'open-bulletin-board-plugin-text-domain'),
            'labels' => $labels,
            'supports' => array(self::AUTHOR, self::TITLE, 'editor', 'comments'),
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
            'capability_type' => self::OPEN_BULLETIN_BOARD_POST_TYPE,
            'capabilities' => $capabilities,
            'meta_map_cap' => false,
            'delete_with_user' => false, // keep content when user will be deleted or trashed
            'rewrite' => array('slug' => self::OPEN_BULLETIN_BOARD_POST_TYPE, 'with_front' => false),
        );
        register_post_type(self::OPEN_BULLETIN_BOARD_POST_TYPE, $args);

        flush_rewrite_rules();

    }

    function add_custom_opbbrd_meta_box($meta_box_id, $meta_box_title)
    {
        $html_id_attribute = self::PLUGIN_PREFIX . $meta_box_id . self::META_BOX;
        $php_callback_function = array($this, self::PLUGIN_PREFIX . 'build_' . $meta_box_id . self::META_BOX);
        $show_me_on_post_type = self::OPEN_BULLETIN_BOARD_POST_TYPE;
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

    function opbbrd_post_type_add_meta_boxes($post)
    {
        $this->add_custom_opbbrd_meta_box(self::NAME, __('Name', 'open-bulletin-board-plugin-text-domain'));
        $this->add_custom_opbbrd_meta_box(self::URL, __('URL', 'open-bulletin-board-plugin-text-domain'));
        $this->add_custom_opbbrd_meta_box(self::PHONE, __('Phone', 'open-bulletin-board-plugin-text-domain'));
        $this->add_custom_opbbrd_meta_box(self::EMAIL, __('Mail', 'open-bulletin-board-plugin-text-domain'));
        $this->add_custom_opbbrd_meta_box(self::LOCATION, __('Location', 'open-bulletin-board-plugin-text-domain'));
    }

    function opbbrd_post_type_build_meta_box($post, $column)
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

    function opbbrd_post_type_build_name_meta_box($post)
    {
        $this->opbbrd_post_type_build_meta_box($post, self::NAME);
    }

    function opbbrd_post_type_build_url_meta_box($post)
    {
        $this->opbbrd_post_type_build_meta_box($post, self::URL);
    }

    function opbbrd_post_type_build_phone_meta_box($post)
    {
        $this->opbbrd_post_type_build_meta_box($post, self::PHONE);
    }

    function opbbrd_post_type_build_email_meta_box($post)
    {
        $this->opbbrd_post_type_build_meta_box($post, self::EMAIL);
    }

    function opbbrd_post_type_build_location_meta_box($post)
    {
        $this->opbbrd_post_type_build_meta_box($post, self::LOCATION);
    }

    function opbbrd_post_type_save_meta_boxes_data($post_id, $column)
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

    function opbbrd_post_type_save_name_meta_boxes_data($post_id)
    {
        $this->opbbrd_post_type_save_meta_boxes_data($post_id, self::NAME);
    }

    function opbbrd_post_type_save_url_meta_boxes_data($post_id)
    {
        $this->opbbrd_post_type_save_meta_boxes_data($post_id, self::URL);
    }

    function opbbrd_post_type_save_phone_meta_boxes_data($post_id)
    {
        $this->opbbrd_post_type_save_meta_boxes_data($post_id, self::PHONE);
    }

    function opbbrd_post_type_save_email_meta_boxes_data($post_id)
    {
        $this->opbbrd_post_type_save_meta_boxes_data($post_id, self::EMAIL);
    }

    function opbbrd_post_type_save_location_meta_boxes_data($post_id)
    {
        $this->opbbrd_post_type_save_meta_boxes_data($post_id, self::LOCATION);
    }

    function the_name($post = 0, $echo = true)
    {
        return the_column($post, $echo, self::NAME);
    }

    function add_opbbrd_to_post_type($query)
    {
        if ((is_home() && $query->is_main_query()) || is_feed()) {
            $query->set(self::POST_TYPE, array(self::POST, self::OPEN_BULLETIN_BOARD_POST_TYPE));
        }
    }

    function opbbrd_map_meta_cap($caps, $cap, $user_id, $args)
    {
        /* If editing, deleting, or reading a entry, get the post and post type object. */
        if (self::EDIT_OPBBRD == $cap || self::DELETE_OPBBRD == $cap || self::READ_OPBBRD == $cap) {
            $post = get_post($args[0]);
            $post_type = get_post_type_object($post->post_type);
            $caps = array();
        } elseif (self::EDIT_COMMENT == $cap) {
            $comment = get_comment($args[0]);
        }

        /* If editing a entry, assign the required capability. */
        if (self::EDIT_OPBBRD == $cap) {
            if ($user_id == $post->post_author)
                $caps[] = $post_type->cap->edit_posts;
            else
                $caps[] = $post_type->cap->edit_others_posts;
        } /* If deleting a entry, assign the required capability. */
        elseif (self::DELETE_OPBBRD == $cap) {
            if ($user_id == $post->post_author)
                $caps[] = $post_type->cap->delete_posts;
            else
                $caps[] = $post_type->cap->delete_others_posts;
        } /* If reading a entry, assign the required capability. */
        elseif (self::READ_OPBBRD == $cap) {
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

    function unregister_opbbrd()
    {
        unregister_post_type(self::OPEN_BULLETIN_BOARD_POST_TYPE);
    }


    function get_opbbrd_allowed_project_formats()
    {
        return array('aside', 'status');
    }

    function opbbrd_post_format_support_filter()
    {
        $screen = get_current_screen();

        // Bail if not on the projects screen.
        if (empty($screen->post_type) || $screen->post_type !== self::OPEN_BULLETIN_BOARD_POST_TYPE)
            return;

        // Check if the current theme supports formats.
        if (current_theme_supports(self::POST_FORMATS)) {

            $formats = get_theme_support(self::POST_FORMATS);

            // If we have formats, add theme support for only the allowed formats.
            if (isset($formats[0])) {
                $new_formats = array_intersect($formats[0], $this->get_opbbrd_allowed_project_formats());

                // Remove post formats support.
                remove_theme_support(self::POST_FORMATS);

                // If the theme supports the allowed formats, add support for them.
                if ($new_formats)
                    add_theme_support(self::POST_FORMATS, $new_formats);
            }
        }

    }


    function opbbrd_default_post_format_filter($format)
    {
        return in_array($format, $this->get_opbbrd_allowed_project_formats()) ? $format : 'Standard';
    }

    function opbbrd_columns($columns)
    {
        $columns = array(
            self::CB => '&lt;input type="checkbox" />',
            self::TITLE => __('Title', 'open-bulletin-board-plugin-text-domain'),
            self::CONTENT => __('Content', 'open-bulletin-board-plugin-text-domain'),
            self::NAME => __('Name', 'open-bulletin-board-plugin-text-domain'),
            self::URL => __('URL', 'open-bulletin-board-plugin-text-domain'),
            self::PHONE => __('Phone', 'open-bulletin-board-plugin-text-domain'),
            self::EMAIL => __('Mail', 'open-bulletin-board-plugin-text-domain'),
            self::AUTHOR => __('Autor', 'open-bulletin-board-plugin-text-domain'),
            self::POST_TAG => __('Tags', 'open-bulletin-board-plugin-text-domain'),
            self::COMMENT_COUNT => __('Comment', 'open-bulletin-board-plugin-text-domain'),
            self::LOCATION => __('Location', 'open-bulletin-board-plugin-text-domain'),
            self::DATE => __('Date', 'open-bulletin-board-plugin-text-domain')
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

    function manage_opbbrd_columns($column, $post_id)
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

    function opbbrd_sortable_columns($columns)
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

}

$plugin = new OpenBulletinBoardPlugin();

register_activation_hook(__FILE__, array($plugin, 'activate'));
register_deactivation_hook(__FILE__, array($plugin, 'deactivate'));
