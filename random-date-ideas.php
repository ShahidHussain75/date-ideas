<?php
/**
 * Plugin Name: Date Ideas
 * Description: Adds a custom post type for date ideas and displays a random idea with the featured image via a shortcode [random_date_idea].
 * Version: 1.4
 * Author: Shahid Hussain
 * Plugin URI: https://github.com/ShahidHussain75/date-ideas
 * Text Domain: date-ideas
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Register Custom Post Type
function di_register_date_ideas_cpt() {
    $labels = array(
        'name'               => 'Date Ideas',
        'singular_name'      => 'Date Idea',
        'menu_name'          => 'Date Ideas',
        'name_admin_bar'     => 'Date Idea',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Date Idea',
        'new_item'           => 'New Date Idea',
        'edit_item'          => 'Edit Date Idea',
        'view_item'          => 'View Date Idea',
        'all_items'          => 'All Date Ideas',
        'search_items'       => 'Search Date Ideas',
        'not_found'          => 'No date ideas found',
        'not_found_in_trash' => 'No date ideas found in Trash',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'date-ideas'),
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-heart',
    );

    register_post_type('date_idea', $args);
}
add_action('init', 'di_register_date_ideas_cpt');

// Shortcode to display the random date idea button and output container
function di_random_date_idea_shortcode() {
    $unique_id = uniqid();
    $output = '<button class="random-date-idea-button" data-container-id="' . esc_attr($unique_id) . '">Get Random Date Idea</button>';
    $output .= '<div id="date-idea-container-' . esc_attr($unique_id) . '" class="random-date-idea-container"></div>';
    return $output;
}
add_shortcode('random_date_idea', 'di_random_date_idea_shortcode');

// Enqueue JavaScript
function di_enqueue_random_date_idea_js() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.random-date-idea-button').forEach(button => {
                button.addEventListener('click', function() {
                    const containerId = this.getAttribute('data-container-id');
                    const outputDiv = document.getElementById(`date-idea-container-${containerId}`);
                    outputDiv.innerHTML = '<p>Loading...</p>';
                    const formData = new FormData();
                    formData.append('action', 'get_random_date_idea');

                    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            outputDiv.innerHTML = data.data;
                        } else {
                            outputDiv.innerHTML = '<p>No date ideas available.</p>';
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching date idea:", error);
                        outputDiv.innerHTML = '<p>Error fetching idea. Please try again.</p>';
                    });
                });
            });

            // Share buttons
            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('share-whatsapp') || event.target.classList.contains('share-messenger')) {
                    event.preventDefault();
                    const container = event.target.closest('.random-date-idea');
                    const shareUrl = container.getAttribute('data-share-url');

                    if (event.target.classList.contains('share-whatsapp')) {
                        const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(shareUrl)}`;
                        window.open(whatsappUrl, '_blank');
                    } else if (event.target.classList.contains('share-messenger')) {
                        const messengerUrl = `https://www.facebook.com/dialog/send?app_id=YOUR_FACEBOOK_APP_ID&link=${encodeURIComponent(shareUrl)}&redirect_uri=${encodeURIComponent(window.location.href)}`;
                        window.open(messengerUrl, '_blank');
                    }
                }
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'di_enqueue_random_date_idea_js');

// AJAX handler
function di_get_random_date_idea_ajax() {
    $last_post_id = isset($_COOKIE['last_date_idea_id']) ? intval($_COOKIE['last_date_idea_id']) : 0;

    $args = array(
        'post_type'      => 'date_idea',
        'posts_per_page' => 1,
        'orderby'        => 'rand',
        'post_status'    => 'publish',
    );

    if ($last_post_id) {
        $args['post__not_in'] = array($last_post_id);
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) {
            $query->the_post();
            $current_id = get_the_ID();
            setcookie('last_date_idea_id', $current_id, time() + 3600, "/");
            ?>
            <div class="random-date-idea" data-share-url="<?php echo esc_url(get_permalink()); ?>">
                <?php if (has_post_thumbnail()) : ?>
                    <img src="<?php the_post_thumbnail_url('full'); ?>" alt="<?php the_title_attribute(); ?>" class="date-idea-image" />
                <?php endif; ?>
                <h2><?php the_title(); ?></h2>
                <div class="date-idea-description"><?php the_content(); ?></div>
                <div class="social-share-buttons">
                    <button class="share-whatsapp">Share on WhatsApp</button>
                    <button class="share-messenger">Share on Messenger</button>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
        wp_send_json_success(ob_get_clean());
    } else {
        wp_send_json_error();
    }

    wp_die();
}
add_action('wp_ajax_get_random_date_idea', 'di_get_random_date_idea_ajax');
add_action('wp_ajax_nopriv_get_random_date_idea', 'di_get_random_date_idea_ajax');
