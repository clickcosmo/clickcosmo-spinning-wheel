<?php
/*
Plugin Name: ClickCOSMO Spinning Wheel
Description: Responsive WordPress spinning wheel for name selection with bulk entry, persistent lists, automatic winner removal, and optional hidden winner targeting [clickcosmo_wheel].
Version: 1.0.3
Author: ClickCOSMO
Author URI: https://clickcosmo.com
ClickCOSMO Support: yes
*/
// NOTE: When using this shortcode on Elementor Canvas pages,
// add a black background fix in Page Settings → Advanced → Custom CSS:
// html, body, .elementor, .elementor-section-wrap { background-color: #000 !important; }

if (!defined('ABSPATH')) {
    exit;
}

function clickcosmo_spinning_wheel_enqueue_assets() {
    wp_enqueue_style(
        'clickcosmo-spinning-wheel',
        plugins_url('assets/css/spinning-wheel.css', __FILE__),
        array(),
        null
    );

    wp_enqueue_script(
        'clickcosmo-spinning-wheel',
        plugins_url('assets/js/spinning-wheel.js', __FILE__),
        array(),
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'clickcosmo_spinning_wheel_enqueue_assets');

function clickcosmo_wheel_shortcode() {
    static $instance_number = 0;

    $instance_number++;

    $instance_id = 'ccsw-' . $instance_number . '-' . wp_rand(1000, 999999);
    $page_id = get_queried_object_id();
    $storage_scope = $page_id ? (string) $page_id : 'global';
    $storage_key = 'clickcosmoSpinningWheelNames:' . $storage_scope . ':' . $instance_number;
    $can_import_legacy_storage = ($instance_number === 1);
    $show_admin_tip = current_user_can('manage_options');

    ob_start();
    ?>
<div
    id="<?php echo esc_attr($instance_id); ?>"
    class="clickcosmo-spinning-wheel"
    data-storage-key="<?php echo esc_attr($storage_key); ?>"
    data-can-import-legacy-storage="<?php echo $can_import_legacy_storage ? '1' : '0'; ?>"
>
    <div class="ccsw-wrap">
        <div class="ccsw-board">
            <div class="ccsw-pointer"></div>
            <canvas class="ccsw-wheel" data-role="wheel" width="900" height="900" aria-label="Spinning wheel"></canvas>
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img
                    src="<?php echo esc_url(get_site_icon_url() ?: plugins_url('wheel-logo.png', __FILE__)); ?>"
                    alt="<?php echo esc_attr(get_bloginfo('name')); ?> Logo"
                    class="ccsw-site-logo"
                >
            </a>
        </div>

        <div class="ccsw-controls">
            <div class="ccsw-row">
                <div class="ccsw-flexcol" data-role="names-wrap"></div>
            </div>

            <div class="ccsw-row">
                <button class="ccsw-button" data-role="add-name" type="button">+ Add name</button>
                <button class="ccsw-button ccsw-secondary-button" data-role="clear-names" type="button">Clear All Names</button>
            </div>

            <div class="ccsw-row">
                <div class="ccsw-help-text">~ <strong>Paste</strong> names separated with comma to import them!<br>~ Winner is removed automatically after each spin.</div>
            </div>

            <?php if ($show_admin_tip) : ?>
                <div class="ccsw-admin-tip" data-role="admin-tip">
                    <div><strong>Admin Tip:</strong> Add <code>~</code> anywhere in a name to target that entry as the next winner. The marker disappears immediately and is never shown on the wheel.</div>
                    <button type="button" class="ccsw-admin-tip-hide" data-role="hide-admin-tip">Hide</button>
                </div>
            <?php endif; ?>

            <div class="ccsw-row">
                <button class="ccsw-button" data-role="spin" type="button">SPIN 🎯</button>
                <div class="ccsw-status" data-role="status">Ready</div>
            </div>

            <div class="ccsw-winner" data-role="winner"></div>
        </div>

        <span class="ccsw-credit">
            © <a href="<?php echo esc_url(home_url('/')); ?>"><strong><?php echo esc_html(get_bloginfo('name')); ?></strong></a>.
            Designed by <a href="https://clickcosmo.com" target="_blank" rel="noopener noreferrer"><strong>ClickCOSMO</strong></a>
        </span>
    </div>

    <div class="ccsw-modal-overlay" data-role="modal-overlay" role="dialog" aria-modal="true" aria-label="Winner">
        <div class="ccsw-modal-content">
            <div class="ccsw-modal-title">CONGRATULATIONS!</div>
            <div class="ccsw-modal-winner-name" data-role="modal-winner-name"></div>
            <button class="ccsw-button" data-role="modal-close" type="button">Spin Again</button>
        </div>
    </div>
</div>
    <?php
    return ob_get_clean();
}
add_shortcode('clickcosmo_wheel', 'clickcosmo_wheel_shortcode');

if (is_admin()) {
    $cc_support_file = plugin_dir_path(__FILE__) . 'includes/admin/cc-plugin-support-contact.php';

    if (file_exists($cc_support_file)) {
        require_once $cc_support_file;
    }
}
