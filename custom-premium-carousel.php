<?php
/**
 * Plugin Name: Custom Premium Carousel for MetaSlider
 * Description: Integrates a custom premium HTML/CSS/JS active-scaling carousel with the MetaSlider backend data dynamically. Includes GitHub Auto-Updater.
 * Version: 1.1.0
 * Author: GitHub Copilot
 * License: GPL-2.0+
 * GitHub Plugin URI: https://github.com/Hunter28-lucky/Carousel-plugin-Mix-Custom-Krish-AE
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// -------------------------------------------------------------
// GITHUB AUTO-UPDATER
// -------------------------------------------------------------
require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/Hunter28-lucky/Carousel-plugin-Mix-Custom-Krish-AE/',
    __FILE__,
    'custom-premium-carousel'
);
// Set the branch to check against
$myUpdateChecker->setBranch('main');

// Force automatic background updates so you don't even have to click "Update"
add_filter('auto_update_plugin', function ($update, $item) {
    if (isset($item->slug) && $item->slug === 'custom-premium-carousel') {
        return true; 
    }
    return $update;
}, 10, 2);

class Custom_Premium_Carousel {

    public function __construct() {
        // Register shortcode
        add_shortcode( 'magazine_carousel', array( $this, 'render_shortcode' ) );
        
        // Enqueue scripts & styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue the isolated CSS and JS assets for the custom carousel.
     */
    public function enqueue_assets() {
        wp_register_style(
            'custom-premium-carousel-css',
            plugin_dir_url( __FILE__ ) . 'assets/carousel.css',
            array(),
            '1.0.0'
        );

        wp_register_script(
            'custom-premium-carousel-js',
            plugin_dir_url( __FILE__ ) . 'assets/carousel.js',
            array(),
            '1.0.0',
            true // Load in footer
        );
    }

    /**
     * Shortcode: [magazine_carousel slider_id="2629"]
     */
    public function render_shortcode( $atts ) {
        $args = shortcode_atts( array(
            'slider_id' => '',
        ), $atts );

        if ( empty( $args['slider_id'] ) ) {
            return '<p>Please provide a valid MetaSlider ID: [magazine_carousel slider_id="123"]</p>';
        }

        $slider_id = intval( $args['slider_id'] );

        // Version 4: Using MetaSlider's EXACT internal query structure
        // MetaSlider natively queries the taxonomy by 'slug' (even though it's an ID number)
        // and includes 'lang' => '' to bypass translation plugin bugs.
        
        $slides_data = array();

        $query_args = array(
            'force_no_custom_order' => true,
            'orderby'               => 'menu_order',
            'order'                 => 'ASC',
            'post_type'             => array( 'attachment', 'ml-slide' ),
            'post_status'           => array( 'inherit', 'publish' ),
            'lang'                  => '',
            'posts_per_page'        => -1,
            'tax_query'             => array(
                array(
                    'taxonomy' => 'ml-slider',
                    'field'    => 'slug',
                    'terms'    => $slider_id
                )
            )
        );

        $slides_query = get_posts( $query_args );

        // Fallback: If 'slug' fails, try 'term_id' just in case.
        if ( empty( $slides_query ) ) {
            $query_args['tax_query'][0]['field'] = 'term_id';
            $slides_query = get_posts( $query_args );
        }

        if ( empty( $slides_query ) ) {
            return '<p>No slides found for MetaSlider ID: ' . esc_html($slider_id) . '. (System checked internal slugs and term IDs).</p>';
        }

        foreach ( $slides_query as $slide ) {
            // Get Image URL
            $img_src = wp_get_attachment_image_url( $slide->ID, 'full' );
            if ( ! $img_src && $slide->post_type === 'ml-slide' ) {
                // Handle image slides if attached directly
                $attachment_id = get_post_thumbnail_id( $slide->ID );
                $img_src       = wp_get_attachment_image_url( $attachment_id, 'full' );
            }

            // Get URL and Target
            $url    = get_post_meta( $slide->ID, 'ml-slider_url', true ) ?: '#';
            $target = get_post_meta( $slide->ID, 'ml-slider_new_window', true ) ? '_blank' : '_self';
            
            // Handle Hidden Slides
            $is_hidden = get_post_meta( $slide->ID, '_meta_slider_slide_is_hidden', true );
            if ( $is_hidden == 'true' || $is_hidden == 1 || $is_hidden === true ) {
                continue; // Skip this slide completely because the user hid it in MetaSlider
            }

            // Get Caption
            $caption = get_post_meta( $slide->ID, 'ml-slider_caption', true );
            if ( empty( $caption ) ) {
                $caption = $slide->post_excerpt; // Fallback to standard attachment excerpt
            }

            // Skip empty slides
            if ( ! $img_src ) continue;

            $slides_data[] = array(
                'img_src' => $img_src,
                'url'     => $url,
                'caption' => $caption,
                'target'  => $target
            );
        }

        $total_originals = count( $slides_data );
        
        // Prevent layout breakage on less than 3 slides
        if ( $total_originals < 3 ) {
            return '<p>Please add at least 3 slides to MetaSlider ID ' . esc_html($slider_id) . ' for the carousel layout to function.</p>';
        }

        // We have slides to show, ensure scripts & styles are loaded on this page
        wp_enqueue_style( 'custom-premium-carousel-css' );
        wp_enqueue_script( 'custom-premium-carousel-js' );

        // Build HTML
        ob_start();
        ?>
        <div class="mag-wrap custom-premium-carousel" id="mag-wrap-<?php echo esc_attr( $slider_id ); ?>" data-total-originals="<?php echo esc_attr( $total_originals ); ?>">
            <div class="carousel">
                <button class="arrow left prev-btn">&#8249;</button>
                <div class="carousel-track track-container">
                    
                    <!-- START CLONES (Last 2 items injected dynamically for backward loop) -->
                    <?php 
                    $start_clones = array_slice( $slides_data, -2 );
                    foreach ( $start_clones as $slide ) : ?>
                    <div class="slide clone">
                        <a href="<?php echo esc_url( $slide['url'] ); ?>" target="<?php echo esc_attr( $slide['target'] ); ?>">
                            <img src="<?php echo esc_url( $slide['img_src'] ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( $slide['caption'] ) ); ?>">
                            <?php if ( ! empty( $slide['caption'] ) ) : ?>
                                <p><?php echo wp_kses_post( $slide['caption'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php endforeach; ?>

                    <!-- ORIGINALS -->
                    <?php foreach ( $slides_data as $slide ) : ?>
                    <div class="slide original">
                        <a href="<?php echo esc_url( $slide['url'] ); ?>" target="<?php echo esc_attr( $slide['target'] ); ?>">
                            <img src="<?php echo esc_url( $slide['img_src'] ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( $slide['caption'] ) ); ?>">
                            <?php if ( ! empty( $slide['caption'] ) ) : ?>
                                <p><?php echo wp_kses_post( $slide['caption'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php endforeach; ?>

                    <!-- END CLONES (First 3 items injected dynamically for forward loop) -->
                    <?php 
                    $end_clones = array_slice( $slides_data, 0, 3 );
                    foreach ( $end_clones as $slide ) : ?>
                    <div class="slide clone">
                        <a href="<?php echo esc_url( $slide['url'] ); ?>" target="<?php echo esc_attr( $slide['target'] ); ?>">
                            <img src="<?php echo esc_url( $slide['img_src'] ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( $slide['caption'] ) ); ?>">
                            <?php if ( ! empty( $slide['caption'] ) ) : ?>
                                <p><?php echo wp_kses_post( $slide['caption'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php endforeach; ?>

                </div>
                <button class="arrow right next-btn">&#8250;</button>
            </div>
            
            <div class="dots dots-container"></div>
        </div>
        <?php
        $output = ob_get_clean();
        
        // FIX: WordPress core 'wpautop' turns physical line breaks from this PHP file into empty HTML `<br>` and `<p>` tags. 
        // This causes the mysterious empty tiny grey boxes. Removing line breaks fixes it instantly.
        $output = str_replace( array( "\r", "\n" ), '', $output );
        
        return $output;
    }
}

// Initialize the plugin
new Custom_Premium_Carousel();