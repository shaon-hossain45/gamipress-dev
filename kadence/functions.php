<?php
/**
 * Kadence functions and definitions
 *
 * This file must be parseable by PHP 5.2.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package kadence
 */

define( 'KADENCE_VERSION', '1.2.22' );
define( 'KADENCE_MINIMUM_WP_VERSION', '6.0' );
define( 'KADENCE_MINIMUM_PHP_VERSION', '7.4' );

// Bail if requirements are not met.
if ( version_compare( $GLOBALS['wp_version'], KADENCE_MINIMUM_WP_VERSION, '<' ) || version_compare( phpversion(), KADENCE_MINIMUM_PHP_VERSION, '<' ) ) {
	require get_template_directory() . '/inc/back-compat.php';
	return;
}
// Include WordPress shims.
require get_template_directory() . '/inc/wordpress-shims.php';

// Load the `kadence()` entry point function.
require get_template_directory() . '/inc/class-theme.php';

// Load the `kadence()` entry point function.
require get_template_directory() . '/inc/functions.php';

// Initialize the theme.
call_user_func( 'Kadence\kadence' );



function theme_enqueue_styles() {
    // Enqueue the main style.css file from the theme root
    wp_enqueue_style('theme-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'theme_enqueue_styles');


function gamipress_user_logs_table_shortcode() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    if (!$current_user_id) return 'Please log in to view your point logs.';

    // Get all point types to dynamically build table columns
    $points_types = get_posts([
        'post_type' => 'points-type',
        'numberposts' => -1,
		'order' => 'ASC',
    ]);

    $point_type_slugs = [];
    foreach ($points_types as $type) {
        $point_type_slugs[] = $type->post_name;
		$point_type_label[] = $type->post_title;
    }

    // Initialize totals array
    $totals = array_fill_keys($point_type_slugs, 0);
    $totals['total'] = 0;

    // Fetch all logs for the current user
    $logs = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}gamipress_user_earnings WHERE user_id = %d ORDER BY date DESC", $current_user_id)
    );

    if (empty($logs)) {
        return 'No logs found.';
    }

    ob_start();
    ?>
    <table class="gamipress-points-table">
        <thead>
            <tr>
                <th>Thumbnail</th>
                <th>Description</th>
                <th>Date</th>
                <?php foreach ($point_type_label as $label): ?>
                    <th><?php echo ucfirst($label); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log):
                $thumbnail = get_the_post_thumbnail($log->post_id, [50, 50]);
                $log_date = date('F j, Y', strtotime($log->date));

                // Initialize points array
                $points_data = array_fill_keys($point_type_slugs, '');
                if (in_array($log->points_type, $point_type_slugs)) {
                    $points_data[$log->points_type] = $log->points;
                
                    // Accumulate totals
                    $totals[$log->points_type] += $log->points;
                    $totals['total'] += $log->points;
                }
                ?>
                <tr>
                    <td><?php echo $thumbnail ?: '-'; ?></td>
                    <td><strong><?php echo esc_html($log->title); ?></strong></td>
                    <td><?php echo esc_html($log_date); ?></td>
                    <?php foreach ($point_type_slugs as $slug): ?>
                        <td><?php echo esc_html($points_data[$slug]); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="gamipress-points-totals">
        <?php foreach ($point_type_slugs as $slug): ?>
            <div><strong>Total <?php echo ucfirst($slug); ?>:</strong> <?php echo esc_html($totals[$slug]); ?></div>
        <?php endforeach; ?>
        <hr>
        <div><strong>Grand Total:</strong> <?php echo esc_html($totals['total']); ?></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('user_points_table', 'gamipress_user_logs_table_shortcode');