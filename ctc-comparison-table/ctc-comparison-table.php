<?php
/**
 * Plugin Name: CTC Comparison Table
 * Plugin URI: https://github.com/lloan/ACF-V3-Block
 * Description: A flexible WordPress block for displaying comparison tables with company information, ratings, fees, and call-to-action buttons. Built with ACF (Advanced Custom Fields) V3.
 * Version: 1.0.0
 * Author: Lloan Alas
 * Author URI: https://github.com/lloan
 * License: Apache-2.0
 * License URI: http://www.apache.org/licenses/LICENSE-2.0
 * Text Domain: ctc
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: advanced-custom-fields
 */

if(!defined('ABSPATH')) {
    die;
}

define('CTC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTC_VERSION', '1.0.0');

add_filter('acf/settings/save_json', function ($path) {
    return CTC_PLUGIN_DIR . 'acf-json';
});

add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = CTC_PLUGIN_DIR . 'acf-json';

    return $paths;
});

add_action('acf/init', 'ctc_register_block');

function ctc_register_block(): void 
{    
    if (!function_exists('acf_register_block_type')) {
        return;
    }

    acf_register_block_type([
        'name' => 'ctc-comparison-table',
        'title' => __('CTC Comparison Table', 'ctc'),
        'description' => 'Compare local brokerages with ratings, fees, and CTAs.',
        'category' => 'widgets',
        'icon' => 'table-col-after',
        'mode' => 'preview',
        'align' => 'wide',
        'supports' => [
            'align' => ['wide', 'full'], // Preview looks better
            'anchor' => true,
        ],
        'render_callback' => 'ctc_render_block',
        'enqueue_assets' => function() {
            ctc_enqueue_assets(); // This way we only load assets when block is present on page
        }
    ]);

}

function ctc_enqueue_assets(): void
{
    $css_path = CTC_PLUGIN_DIR . 'assets/styles.css';
    
    $css_ver = file_exists($css_path) ? filemtime($css_path) : CTC_VERSION;
    wp_enqueue_style('ctc-comparison-table', CTC_PLUGIN_URL . 'assets/styles.css', [], $css_ver);
}


function ctc_render_block(array $block, string $content = '', bool $is_preview = false, int $post_id = 0): void
{
    $template = CTC_PLUGIN_DIR . 'templates/ctc-block.php';

    if (!file_exists($template)) {
        echo '<div class="ctc-comparison-table"><p><strong>Comparison Table Component:</strong> template missing.</p></div>';
		return;
	}

    // Making these available for template use if I decide to use them down the line.
    $ctc_block      = $block;
	$ctc_is_preview = $is_preview;
	$ctc_post_id    = $post_id;

    require $template;
}