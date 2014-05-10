<?php
/*
  Plugin Name: Owl Carousel
  Description: A simple plugin to include an Owl Carousel in any post
  Author: Pierre JEHAN
  Version: 0.3
  Author URI: http://www.pierre-jehan.com
  Licence: GPL2
 */

add_theme_support('post-thumbnails');

add_action('init', 'owlcarousel_init');
add_action('wp_print_scripts', 'owl_register_scripts');
add_action('wp_print_styles', 'owl_register_styles');
add_action('widgets_init', 'owl_widgets_init');
add_action('manage_edit-owl-carousel_columns', 'owl_columnfilter');
add_action('manage_posts_custom_column', 'owl_column');

/**
 * Initilize the plugin
 */
function owlcarousel_init() {

    $labels = array(
        'name' => 'Owl Carousel',
        'singular_name' => 'Carousel Item',
        'add_new' => 'Add New Item',
        'add_new_item' => 'Add New Carousel Item',
        'edit_item' => 'Edit Carousel Item',
        'new_item' => 'Add New Carousel Item',
        'view_item' => 'View Item',
        'search_items' => 'Search Carousel',
        'not_found' => 'No carousel items found',
        'not_found_in_trash' => 'No carousel items found in trash',
    );

    register_post_type('owl-carousel', array(
        'public' => true,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'label' => 'Owl Carousel',
        'menu_icon' => plugins_url('/owl-carousel/images/owl-logo-16.png'),
        'labels' => $labels,
        'capability_type' => 'post',
        'supports' => array(
            'title',
            'editor',
            'thumbnail'
        ),
        'taxonomies' => array('category'),
    ));

    add_image_size('owl_widget', 180, 100, true);
    add_image_size('owl_function', 600, 280, true);

    register_taxonomy_for_object_type('category', 'owl-carousel');

    add_shortcode('owl-carousel', 'owl_function');
    add_filter("mce_external_plugins", "owl_register_tinymce_plugin");
    add_filter('mce_buttons', 'owl_add_tinymce_button');
}

/**
 * List of JavaScript files
 */
function owl_register_scripts() {
    wp_register_script('js.owl.carousel', plugins_url('/owl-carousel/js/owl.carousel.js'));
    wp_register_script('js.owl.carousel.script', plugins_url('/owl-carousel/js/script.js'));

    wp_enqueue_script('jquery');
    wp_enqueue_script('js.owl.carousel');
    wp_enqueue_script('js.owl.carousel.script');
}

/**
 * List of CSS files
 */
function owl_register_styles() {
    wp_register_style('style.owl.carousel', plugins_url('/owl-carousel/css/owl.carousel.css'));
    wp_register_style('style.owl.carousel.theme', plugins_url('/owl-carousel/css/owl.theme.css'));
    wp_register_style('style.owl.carousel.transitions', plugins_url('/owl-carousel/css/owl.transitions.css'));
    wp_register_style('style.owl.carousel.styles', plugins_url('/owl-carousel/css/styles.css'));

    wp_enqueue_style('style.owl.carousel');
    wp_enqueue_style('style.owl.carousel.theme');
    wp_enqueue_style('style.owl.carousel.transitions');
    wp_enqueue_style('style.owl.carousel.styles');
}

function owl_register_tinymce_plugin($plugin_array) {
    $plugin_array['owl_button'] = plugins_url('/owl-carousel/js/owl-tinymce-plugin.js');
    return $plugin_array;
}

function owl_add_tinymce_button($buttons) {
    $buttons[] = "owl_button";
    return $buttons;
}

/*
 * Initialize Owl Widget
 */

function owl_widgets_init() {
    register_widget("owl_Widget");
}

class owl_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct('owl_Widget', 'Owl Carousel', array('description' => __('A Owl Carousel Widget', 'text_domain')));
    }

    public function form($instance) {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('Widget Carousel', 'text_domain');
        }
        if (isset($instance['category'])) {
            $category = $instance['category'];
        } else {
            $category = 'Uncategorized';
        }
        ?>  
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>  
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />  
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Category:'); ?></label>  
            <input class="widefat" id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>" type="text" value="<?php echo esc_attr($category); ?>" />  
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['category'] = strip_tags($new_instance['category']);

        return $instance;
    }

    public function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if (!empty($title))
            echo $before_title . $title . $after_title;
        echo owl_function(array(category => $instance['category'], singleItem => "true", autoPlay => "true", pagination => "false"));
        echo $after_widget;
    }

}

/**
 * Add custom column filters in administration
 * @param array $columns
 */
function owl_columnfilter($columns) {
    $thumb = array('thumbnail' => 'Image');
    $columns = array_slice($columns, 0, 2) + $thumb + array_slice($columns, 2, null);

    return $columns;
}

/**
 * Add custom column contents in administration
 * @param type $columnName
 */
function owl_column($columnName) {
    global $post;
    if ($columnName == 'thumbnail') {
        echo edit_post_link(get_the_post_thumbnail($post->ID, 'thumbnail'), null, null, $post->ID);
    }
}

/**
 * Plugin main function
 * @param type $atts Owl parameters
 * @param type $content
 * @return string Owl HTML code
 */
function owl_function($atts, $content = null) {
    extract(shortcode_atts(array(
        'category' => 'Uncategoryzed'
                    ), $atts));

    $data_attr = "";
    foreach ($atts as $key => $value) {
        if ($key != "category") {
            $data_attr .= ' data-' . $key . '="' . $value . '" ';
        }
    }

    $args = array(
        'post_type' => 'owl-carousel',
        'category_name' => $atts['category']
    );

    $result = '<div id="owl-carousel-' . rand() . '" class="owl-carousel" ' . $data_attr . '>';

    $loop = new WP_Query($args);
    while ($loop->have_posts()) {
        $loop->the_post();

        $the_url = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), get_post_type());
        $result .= '<div class="item">';
        if ($the_url[0])
        {
            $result .= '<div>';
                if ($the_url[0])
                {
                    $result .= '<img title="' . get_the_title() . '" src="' . $the_url[0] . '" alt="' . get_the_title() . '"/>';
                }
                $result .= '<div class="owl-carousel-item-imgoverlay">';
                    $result .= '<div class="owl-carousel-item-imgtitle">' . get_the_title() . '</div>';
                    $result .= '<div class="owl-carousel-item-imgcontent">' . get_the_content() . '</div>';
                $result .= '</div>';
            $result .= '</div>';
        }
        else
        {
            $result .= '<div class="owl-carousel-item-text">' . get_the_content() . '</div>';
        }
        $result .= '</div>';
    }
    $result .= '</div>';

    return $result;
}
?>
