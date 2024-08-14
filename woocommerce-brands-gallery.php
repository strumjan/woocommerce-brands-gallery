<?php
/*
 * Plugin Name: WooCommerce Brands Gallery
 * Description: Plugin to display a gallery of brand images linked to products.
 * Version: 1.0
 * Author: Ilija Iliev Strumjan
 * Text Domain: woocommerce-brands-gallery
 * Domain Path: /languages
 * Requires Plugins: woocommerce
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register activation hook
register_activation_hook(__FILE__, 'wbg_activate');
function wbg_activate() {
    wbg_create_table();
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'wbg_deactivate');
function wbg_deactivate() {
    // Add deactivation logic if needed
}

// Load plugin text domain for translations
add_action('plugins_loaded', 'wbg_load_textdomain');
function wbg_load_textdomain() {
    load_plugin_textdomain('woocommerce-brands-gallery', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

function wbg_enqueue_styles() {
    wp_enqueue_style('wbg_styles', plugins_url('assets/wbg-styles.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'wbg_enqueue_styles');

function wbg_enqueue_admin_scripts($hook) {
    // Вчитување на WordPress медиа скриптата
    wp_enqueue_media();

    // Вчитување на прилагодената скрипта за управување со прикачувањето на слики
    wp_enqueue_script('wbg-admin-script', plugin_dir_url(__FILE__) . 'assets/wbg-admin.js', array('jquery'), null, true);
}

add_action('admin_enqueue_scripts', 'wbg_enqueue_admin_scripts');


function wbg_register_menu() {
    add_menu_page('WooCommerce Brands Gallery', 'Brands Gallery', 'manage_options', 'wbg_brands', 'wbg_list_brands_page');
    add_submenu_page('wbg_brands', 'Add New Brand', 'Add New', 'manage_options', 'wbg_add_brand', 'wbg_add_brand_page');
}
add_action('admin_menu', 'wbg_register_menu');



// Function to create the database table on plugin activation
function wbg_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'wbg_list';
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        wbg_name varchar(255) NOT NULL,
        wbg_imgs text NOT NULL,
        wbg_link varchar(255) NOT NULL,
        wbg_prod text NOT NULL,
        wbg_acti boolean NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook the function to plugin activation
register_activation_hook(__FILE__, 'wbg_create_table');

// Function to add menu pages for managing brands
function wbg_add_admin_menu() {
    add_menu_page(
        'Brands Gallery',
        'Brands Gallery',
        'manage_options',
        'wbg_brands_gallery',
        'wbg_brands_gallery_page',
        'dashicons-format-gallery',
        20
    );

    add_submenu_page(
        'wbg_brands_gallery',
        'Add Brand',
        'Add Brand',
        'manage_options',
        'wbg_add_brand',
        'wbg_add_brand_page'
    );

    add_submenu_page(
        'wbg_brands_gallery',
        'Edit Brand',
        'Edit Brand',
        'manage_options',
        'wbg_edit_brand',
        'wbg_edit_brand_page'
    );
}
add_action('admin_menu', 'wbg_add_admin_menu');

// Function to render the form for adding a new brand
function wbg_add_brand_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wbg_list';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $wbg_name = sanitize_text_field($_POST['wbg_name']);
        $wbg_imgs = esc_url_raw($_POST['wbg_imgs']);
        $wbg_link = esc_url_raw($_POST['wbg_link']);
        $wbg_prod = implode(',', array_map('intval', $_POST['wbg_prod']));
        $wbg_acti = isset($_POST['wbg_acti']) ? 1 : 0;

        $wpdb->insert($table_name, array(
            'wbg_name' => $wbg_name,
            'wbg_imgs' => $wbg_imgs,
            'wbg_link' => $wbg_link,
            'wbg_prod' => $wbg_prod,
            'wbg_acti' => $wbg_acti
        ));

        echo '<div class="notice notice-success is-dismissible"><p>Brand added successfully!</p></div>';
    }

    $products = wc_get_products(array('limit' => -1));
    ?>
    <div class="wrap">
        <h1>Add New Brand</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wbg_name">Brand Name</label>
                    </th>
                    <td>
                        <input name="wbg_name" type="text" id="wbg_name" value="" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wbg_imgs">Brand Image</label>
                    </th>
                    <td>
                        <input name="wbg_imgs" type="text" id="wbg_imgs" value="" class="regular-text" required>
                        <input type="button" id="upload_image_button" class="button wbg_upload_image_button" value="Upload Image" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wbg_link">Brand Link</label>
                    </th>
                    <td>
                        <input name="wbg_link" type="url" id="wbg_link" value="" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wbg_acti">Active</label>
                    </th>
                    <td>
                        <input name="wbg_acti" type="checkbox" id="wbg_acti">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wbg_prod">Linked Products</label>
                    </th>
                    <td>
                        <div class="wbg-product-list">
                            <?php
                            $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                            foreach ($categories as $category) {
                                echo '<div class="wbg-category">';
                                echo '<h4>' . esc_html($category->name) . ' <button type="button" class="wbg-toggle-category">+</button></h4>';
                                echo '<div class="wbg-products" style="display:none;">';
                                foreach ($products as $product) {
                                    if (has_term($category->term_id, 'product_cat', $product->get_id())) {
                                        echo '<label><input type="checkbox" name="wbg_prod[]" value="' . esc_attr($product->get_id()) . '"> ' . esc_html($product->get_name()) . '</label><br>';
                                    }
                                }
                                echo '</div></div>';
                            }
                            ?>
                        </div>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Brand">
            </p>
        </form>
    </div>
    <?php
}



// Function to render the form for editing a brand
function wbg_edit_brand_page() {
    global $wpdb;

    if (isset($_GET['id'])) {
        $brand_id = intval($_GET['id']);
        $brand = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wbg_list WHERE id = %d", $brand_id));

        if (!$brand) {
            echo '<div class="notice notice-error is-dismissible"><p>Brand not found!</p></div>';
            return;
        }

        $products = wc_get_products(array('limit' => -1)); // Fetch all products

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process the form data
            $wbg_name = sanitize_text_field($_POST['wbg_name']);
            $wbg_imgs = sanitize_text_field($_POST['wbg_imgs']);
            $wbg_link = esc_url_raw($_POST['wbg_link']);
            $wbg_prod = isset($_POST['wbg_prod']) ? implode(',', array_map('sanitize_text_field', $_POST['wbg_prod'])) : '';
            $wbg_acti = isset($_POST['wbg_acti']) ? 1 : 0;

            // Update the data in the database
            $wpdb->update(
                $wpdb->prefix . 'wbg_list',
                array(
                    'wbg_name' => $wbg_name,
                    'wbg_imgs' => $wbg_imgs,
                    'wbg_link' => $wbg_link,
                    'wbg_prod' => $wbg_prod,
                    'wbg_acti' => $wbg_acti,
                ),
                array('id' => $brand_id)
            );

            wp_redirect(add_query_arg(array('page' => 'wbg_edit_brand', 'id' => $brand_id, 'updated' => 'true'), admin_url('admin.php')));
            echo '<div class="notice notice-success is-dismissible"><p>Brand updated successfully!</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Edit Brand</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wbg_name">Brand Name</label>
                        </th>
                        <td>
                            <input name="wbg_name" type="text" id="wbg_name" value="<?php echo esc_attr($brand->wbg_name); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wbg_imgs">Brand Image</label>
                        </th>
                        <td>
                            <input name="wbg_imgs" type="text" id="wbg_imgs" value="<?php echo esc_attr($brand->wbg_imgs); ?>" class="regular-text">
                            <button class="button wbg_upload_image_button">Upload Image</button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wbg_link">Brand Link</label>
                        </th>
                        <td>
                            <input name="wbg_link" type="url" id="wbg_link" value="<?php echo esc_url($brand->wbg_link); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wbg_acti">Active</label>
                        </th>
                        <td>
                            <input name="wbg_acti" type="checkbox" id="wbg_acti" <?php checked($brand->wbg_acti, 1); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wbg_prod">Linked Products</label>
                        </th>
                        <td>
                            <div class="wbg-product-list">
                                <?php
                                $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                                $selected_products = explode(',', $brand->wbg_prod);
                                foreach ($categories as $category) {
                                    echo '<div class="wbg-category">';
                                    echo '<h4>' . esc_html($category->name) . ' <button type="button" class="wbg-toggle-category">+</button></h4>';
                                    echo '<div class="wbg-products" style="display:none;">';
                                    foreach ($products as $product) {
                                        if (has_term($category->term_id, 'product_cat', $product->get_id())) {
                                            echo '<label><input type="checkbox" name="wbg_prod[]" value="' . esc_attr($product->get_id()) . '" ' . checked(in_array($product->get_id(), $selected_products), true, false) . '> ' . esc_html($product->get_name()) . '</label><br>';
                                        }
                                    }
                                    echo '</div></div>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Brand">
                </p>
            </form>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('.wbg_upload_image_button').click(function (e) {
                    e.preventDefault();
                    var custom_uploader = wp.media({
                        title: 'Upload Image',
                        button: {
                            text: 'Use this image'
                        },
                        multiple: false
                    }).on('select', function () {
                        var attachment = custom_uploader.state().get('selection').first().toJSON();
                        $('#wbg_imgs').val(attachment.url);
                    }).open();
                });

                $('.wbg-toggle-category').click(function () {
                    $(this).siblings('.wbg-products').toggle();
                    $(this).text($(this).text() === '+' ? '-' : '+');
                });
            });
        </script>
        <?php
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>No brand selected for editing!</p></div>';
    }
}

function wbg_brands_gallery_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wbg_list';

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['wbg_update'])) {
        $id = intval($_POST['wbg_update']);
        $wbg_name = sanitize_text_field($_POST['wbg_name']);
        $wbg_imgs = esc_url_raw($_POST['wbg_imgs']);
        $wbg_link = esc_url_raw($_POST['wbg_link']);
        $wbg_prod = implode(',', array_map('intval', $_POST['wbg_prod']));
        $wbg_acti = isset($_POST['wbg_acti']) ? 1 : 0;

        $wpdb->update($table_name, array(
            'wbg_name' => $wbg_name,
            'wbg_imgs' => $wbg_imgs,
            'wbg_link' => $wbg_link,
            'wbg_prod' => $wbg_prod,
            'wbg_acti' => $wbg_acti
        ), array('id' => $id));

        echo '<div class="notice notice-success is-dismissible"><p>Brand updated successfully!</p></div>';
    }

    $brands = $wpdb->get_results("SELECT * FROM $table_name ORDER BY wbg_name");

    ?>
    <div class="wrap">
        <h1>Brands List</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Link</th>
                    <th>Active</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brands as $brand): ?>
                    <tr>
                        <td><?php echo esc_html($brand->wbg_name); ?></td>
                        <td><a href="<?php echo esc_url($brand->wbg_link); ?>" target="_blank"><?php echo esc_html($brand->wbg_link); ?></a></td>
                        <td><input type="checkbox" class="wbg-toggle-activity" data-id="<?php echo esc_attr($brand->id); ?>" <?php checked($brand->wbg_acti, 1); ?>></td>
                        <td><a href="?page=wbg_edit_brand&id=<?php echo esc_attr($brand->id); ?>" class="button">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('.wbg-toggle-activity').change(function () {
                var brand_id = $(this).data('id');
                var active = $(this).is(':checked') ? 1 : 0;
                
                $.post(ajaxurl, {
                    action: 'wbg_toggle_activity',
                    id: brand_id,
                    active: active
                }, function (response) {
                    console.log(response);
                });
            });
        });
    </script>
    <?php
}

function wbg_toggle_activity() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wbg_list';
    $id = intval($_POST['id']);
    $active = intval($_POST['active']);

    $wpdb->update($table_name, array('wbg_acti' => $active), array('id' => $id));

    wp_die();
}
add_action('wp_ajax_wbg_toggle_activity', 'wbg_toggle_activity');

function wbg_display_brands() {
    global $wpdb;
    $product_id = get_the_ID();
    $table_name = $wpdb->prefix . 'wbg_list';

    $brands = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name 
        WHERE FIND_IN_SET(%d, wbg_prod) AND wbg_acti = 1
    ", $product_id));

    if ($brands) {
        echo '<div class="wbg-brands-gallery">';
        foreach ($brands as $brand) {
            echo '<div class="wbg-brand">';
            echo '<a href="' . esc_url($brand->wbg_link) . '" target="_blank" rel="nofollow noopener noreferrer">';
            echo '<img src="' . esc_url($brand->wbg_imgs) . '" alt="' . esc_attr($brand->wbg_name) . '">';
            echo '</a>';
            echo '</div>';
        }
        echo '</div>';
    }
}
add_action('woocommerce_after_single_product_summary', 'wbg_display_brands', 15);


?>
