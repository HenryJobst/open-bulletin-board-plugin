<?php
/**
 * Widget for open bulletin board plugin
 *
 * @package    open-bulletin-board-plugin
 */

class Open_Bulletin_Board_Plugin_Widget extends WP_Widget {

    /**
     * Constructor
     *
     * @return void
     **/
    function __construct() {
        $widget_ops = array( 'classname' => 'widget_posts', 'description' => 'A short list of bulletin board posts.' );
        parent::__construct( 'opbbrd-posts-widget', __( 'Open Bulletin Board Posts', 'open-bulletin-board-plugin-text-domain' ), $widget_ops );
    }

    /**
     * Outputs the HTML for this widget.
     *
     * @param array  An array of standard parameters for widgets in this theme
     * @param array  An array of settings for this widget instance
     *
     * @return void Echoes it's output
     **/
    function widget( $args, $instance ) {
        extract( $args, EXTR_SKIP );

        $count = esc_attr( $instance['count'] );
        $count = 0 < $count && $count < 11 ? $count : 5;
        $date_format = esc_attr( $instance['date_format']);
        if (!$date_format) {
            $date_format = 'j. F Y';
        }

        $loop  = new WP_Query( array(
            'post_type'      => OpenBulletinBoardPlugin::OPEN_BULLETIN_BOARD_POST_TYPE,
            'posts_per_page' => $count,
            'orderby' => array('date' =>'DESC'),
        ) );

        if ( $loop->have_posts() ):

            echo $before_widget;

            if ($title = $instance['title'] ) {
                echo $before_title . apply_filters( 'widget_title', $title ) . $after_title;
            }

            echo '<ul class="display-post-listing opbbrd-posts-widget">';

            while ( $loop->have_posts() ): $loop->the_post();
                global $post;

                $output = '';

                $length = 20;
				if ( has_excerpt() ) {
                    $excerpt = wp_trim_words( strip_shortcodes( get_the_excerpt() ), $length );
                } else {
                    $excerpt = wp_trim_words( strip_shortcodes( get_the_content() ), $length );
                }

                $output = $output . '<a class="title" href="' . get_permalink() . '">' . get_the_title() . '</a> <span class="date nobr">' . get_the_date($date_format) . '</span> ';
                $output = $output . '<span class="author nobr">' . esc_html__('by', 'open-bulletin-board-plugin-text-domain') . '&nbsp;' . get_the_author() . '</span>';
                $output = $output . '<div class="excerpt">' . $excerpt . '</div>';
                echo '<li class="listing-item">' . apply_filters( 'opbbrd_posts_widget_output', $output, $post ) . '</li>';

            endwhile;

            if ( $instance['more_text'] && $instance['more_link']) {
                echo '<li class="listing-item"><a href="' . esc_attr( $instance['more_link'] ) . '">' . esc_attr( $instance['more_text'] ) . '</a></li>';
            }
            echo '</ul>';

            echo $after_widget;

        endif;
        wp_reset_postdata();
    }

    /**
     * Deals with the settings when they are saved by the admin.
     * Here is where any validation should be dealt with.
     *
     * @param array  An array of new settings as submitted by the admin
     * @param array  An array of the previous settings
     *
     * @return array The validated and (if necessary) amended settings
     **/
    function update( $new_instance, $old_instance ): array
    {
        $instance = $old_instance;

        $instance['title']     = wp_kses_post( $new_instance['title'] );
        $instance['count']     = (int) esc_attr( $new_instance['count'] );
        $instance['more_text'] = esc_attr( $new_instance['more_text'] );
        $instance['more_link'] = esc_attr( $new_instance['more_link'] );
        $instance['date_format'] = esc_attr( $new_instance['date_format'] );

        return $instance;
    }

    /**
     * Displays the form for this widget on the Widgets page of the WP Admin area.
     *
     * @param array  An array of the current settings for this widget
     *
     * @return void Echoes it's output
     **/
    function form( $instance ) {

        $defaults = array(
            'title'     => __( 'Upcoming Events', 'open-bulletin-board-plugin-text-domain'),
            'count'     => 3,
            'more_text' => __( 'Show All', 'open-bulletin-board-plugin-text-domain'),
            'more_link' => __( 'Permalink to the page that show all events', 'open-bulletin-board-plugin-text-domain'),
            'date_format' => __( 'd. F Y', 'open-bulletin-board-plugin-text-domain'),
        );
        $instance = wp_parse_args( (array) $instance, $defaults );

        echo '<p><label for="' . $this->get_field_id( 'title' ) . '">' . esc_html__( 'Title:', 'open-bulletin-board-plugin-text-domain' ) . ' <input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" value="' . esc_attr( $instance['title'] ) . '" /></label></p>';
        echo '<p><label for="' . $this->get_field_id( 'count' ) . '">' . esc_html__( 'How Many:', 'open-bulletin-board-plugin-text-domain' ) . ' <input class="widefat" id="' . $this->get_field_id( 'count' ) . '" name="' . $this->get_field_name( 'count' ) . '" value="' . esc_attr( $instance['count'] ) . '" /></label></p>';
        echo '<p><label for="' . $this->get_field_id( 'more_text' ) . '">' . esc_html__( 'More Text:', 'open-bulletin-board-plugin-text-domain' ) . ' <input class="widefat" id="' . $this->get_field_id( 'more_text' ) . '" name="' . $this->get_field_name( 'more_text' ) . '" value="' . esc_attr( $instance['more_text'] ) . '" /></label></p>';
        echo '<p><label for="' . $this->get_field_id( 'more_link' ) . '">' . esc_html__( 'More Link:', 'open-bulletin-board-plugin-text-domain' ) . ' <input class="widefat" id="' . $this->get_field_id( 'more_link' ) . '" name="' . $this->get_field_name( 'more_link' ) . '" value="' . esc_attr( $instance['more_link'] ) . '" /></label></p>';
        echo '<p><label for="' . $this->get_field_id( 'date_format' ) . '">' . esc_html__( 'Date Format:', 'open-bulletin-board-plugin-text-domain' ) . ' <input class="widefat" id="' . $this->get_field_id( 'date_format' ) . '" name="' . $this->get_field_name( 'date_format' ) . '" value="' . esc_attr( $instance['date_format'] ) . '" /></label></p>';
    }
}

function open_bulletin_board_register_posts_widget() {
    register_widget('Open_Bulletin_Board_Plugin_Widget');
}

add_action( 'widgets_init', 'open_bulletin_board_register_posts_widget' );