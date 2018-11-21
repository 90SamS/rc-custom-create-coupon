<?php
/**
 * Plugin Name: RC Trade Boost Plugin
 * Plugin URI:
 * Description: Configure coupon
 * Version: 1.06b
 * Author: Alex
 */

function coupon_app()
{
    wp_enqueue_script( 'app-js', plugins_url( '/js/app.js', __FILE__ ), array('jquery'));
    wp_localize_script( 'ajax-script', 'my_ajax_object',
        array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    wp_register_style( 'red-carlos-style-css', plugins_url( '/css/red-carlos-style.css', __FILE__ ) );
    wp_enqueue_style( 'red-carlos-style-css' );
}
add_action( 'wp_enqueue_scripts', 'coupon_app' );

add_action( 'wp_ajax_nopriv_create_coupon', 'create_coupon' );
add_action( 'wp_ajax_create_coupon', 'create_coupon' );
function create_coupon(){
    $userIpDot = get_the_user_ip();
    $coupon_options = get_option('coupon_option');
    $coupon_time_life = $coupon_options['coupon_time_life'];

    $coupon_expires = current_time('timestamp', 'true') + $coupon_time_life * 60;
    $userIp = str_replace('.', '', $userIpDot);
    $coupon_code =  $userIp;

    global $wpdb;
    $wpdb->delete( $wpdb->posts, array('post_title' => $coupon_code));

    if($coupon_code){

        $amount = $coupon_options['coupon_price']; // Amount
        $discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product

        $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type'		=> 'shop_coupon'
        );

        $new_coupon_id = wp_insert_post( $coupon );

        // Add meta
        update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
        update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
        update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
        update_post_meta( $new_coupon_id, 'product_ids', '' );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
        update_post_meta( $new_coupon_id, 'usage_limit', 1 );
        update_post_meta( $new_coupon_id, 'usage_limit_per_user', 1 );
        update_post_meta( $new_coupon_id, 'expiry_date', $coupon_expires );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
        update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
    }

    WC()->cart->add_discount( $coupon_code );

    echo get_permalink( wc_get_page_id( 'cart' ) );

    wp_die();
}

class MySettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'Coupon settings',
            'manage_options',
            'my-setting-admin',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option('coupon_option');
        ?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('my_option_group');
                do_settings_sections('my-setting-admin');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'my_option_group', // Option group
            'coupon_option', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Coupon settings', // Title
            array($this, 'print_section_info'), // Callback
            'my-setting-admin' // Page
        );

        add_settings_field(
            'coupon_time_life', // ID
            'Coupon time life', // Title
            array($this, 'coupon_time_life_callback'), // Callback
            'my-setting-admin', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            'coupon_price',
            'Coupon price in %',
            array($this, 'coupon_price_callback'),
            'my-setting-admin',
            'setting_section_id'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['coupon_time_life']))
            $new_input['coupon_time_life'] = sanitize_text_field($input['coupon_time_life']);

        if (isset($input['coupon_price']))
            $new_input['coupon_price'] = absint($input['coupon_price']);

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function coupon_time_life_callback()
    {
        printf(
            '<input type="text" id="coupon_time_life" name="coupon_option[coupon_time_life]" value="%s" />',
            isset($this->options['coupon_time_life']) ? esc_attr($this->options['coupon_time_life']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function coupon_price_callback()
    {
        printf(
            '<input type="text" id="coupon_price" name="coupon_option[coupon_price]" value="%s" />',
            isset($this->options['coupon_price']) ? esc_attr($this->options['coupon_price']) : ''
        );
    }
}

if (is_admin())
    $my_settings_page = new MySettingsPage();

// Display User IP in WordPress

function get_the_user_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
//check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
//to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return apply_filters('wpb_get_ip', $ip);
}

