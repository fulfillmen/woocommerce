<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              www.dhairyasharma.com
 * @since             1.0.0
 * @package           Fulfillmen
 *
 * @wordpress-plugin
 * Plugin Name:       Fulfillmen
 * Plugin URI:        http://www.fulfillmen.com
 * Description:       WooCommerce Order Fulfilment by Fulfillmen. Update notes: Added support for the latest Woocommerce hooks, requires PHP version 8.2 or higher to run, Woocommerce version 8.6.0 or Higher, Wordpress version 6.3 or Higher. 
 * Version:           1.1.10
 * GitHub Plugin URI: https://github.com/fulfillmen/fm-woocommerce
 * Author:            Dhairya Sharma
 * Author URI:        www.dhairyasharma.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fulfillmen
 * Domain Path:       /languages
 * Requires at least: 6.3
 * Requires PHP: 8.2
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
$pluginversion = '1.1.10';
define('FULFILLMEN_VERSION', $pluginversion);
define( "FULFILLMEN_PLUGIN_FILE", __FILE__ );
define( 'FULFILLMEN_NAME', 'fulfillmen' );


if (!function_exists('is_plugin_active')) {
    include_once ABSPATH . '/wp-admin/includes/plugin.php';
}
 
function check_plugin_updates() {
    $plugin_slug = 'fulfillmen'; 
    $github_repo = 'fulfillmen/fulfillmen'; 

    // Check if cached data exists
    $cache_key = 'fulfillmen_update_info';
    $cached_data = get_transient($cache_key);

    if ($cached_data === false) {
        // Cached data doesn't exist or has expired, fetch fresh data from GitHub API
        $remote = wp_remote_get( 
            'https://fulfillmen.github.io/fulfillmen/manifest.json', 
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json'
                ) 
            )
        );    

        if( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) )) {
            return false;	
        }
        $release_data = json_decode( wp_remote_retrieve_body( $remote ) ,true);
        $latest_version = $release_data['version'];
        // Cache the fetched data for 1 day
        set_transient($cache_key, $release_data, 2 * HOUR_IN_SECONDS);
    } else {
        // Use cached data
        $release_data = $cached_data;
    }

    if (isset($release_data) && is_array($release_data)) {
        $latest_version = $release_data['version'];
        $installed_version =  get_option("{$plugin_slug}_version");
        if (version_compare($installed_version, $latest_version, '<')) {
            add_action('admin_notices', function () use ($latest_version,$installed_version ) {
                echo '<div class="notice notice-warning"><p>You are running version '.$installed_version . ' of the Fulfillmen plugin and the new version of Fulfillmen Woocommerce Plugin (' . $latest_version . ') is available. <a href="plugins.php">Update now</a></p></div>';
            });
        }
    }
}

function update_fulfillmen_plugin($transient) {
     $github_cache_key = 'fulfillmen_update_info_pre_update';
     $github_data = get_option($github_cache_key);

     if ($github_data !== false && isset($github_data->name, $github_data->download_url)) {
        $new_version = $github_data->name;
        $package_url = $github_data->download_url;
        $plugin_slug = 'fulfillmen/fulfillmen.php';
        $plugin_data = array(
            'new_version' => $new_version,
            'url' => 'https://github.com/fulfillmen/fulfillmen',
            'package' => $package_url,
            'slug'=> "fulfillmen",
            'plugin' => plugin_basename( __FILE__ ),
            'tested' => $github_data->tested,
        );
        $transient->response[$plugin_slug] = (object) $plugin_data;
        return $transient;
    }

     $url = "https://fulfillmen.github.io/fulfillmen/manifest.json";
     $response = wp_remote_get($url,array(
        'timeout' => 10,
        'headers' => array(
            'Accept' => 'application/json'
        )
    ));
 
     // Check for successful response
     if (!is_wp_error($response) && $response['response']['code'] === 200) {
         $release_data = json_decode($response['body']);
         // Check if release data is valid
         if (is_object($release_data) && isset($release_data->name, $release_data->download_url)) {
             $new_version = $release_data->name;
             $package_url = $release_data->download_url;
             $plugin_slug = 'fulfillmen/fulfillmen.php';
             $plugin_data = array(
                 'new_version' => $new_version,
                 'url' => 'https://github.com/fulfillmen/fulfillmen',
                 'package' => $package_url,
                 'slug'=> "fulfillmen", 
                 'plugin' => plugin_basename( __FILE__ ),
                 'tested' => $release_data->tested,

             );
             // Add plugin update information to transient
             $transient->response[$plugin_slug] = (object) $plugin_data;
             // Cache the data
             update_option($github_cache_key, $release_data);
         }
     }
 
     return $transient;
}


function fulfillmen_plugin_info($res, $action, $args){
    if( 'plugin_information' !== $action ) {
		return $res;
	}
    if( plugin_basename( __DIR__ ) !== $args->slug ) {
		return $res;
	}
    $remote = wp_remote_get( 
		'https://fulfillmen.github.io/fulfillmen/manifest.json', 
		array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json'
			) 
		)
	);

    if( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) )) {
		return $res;	
	}
	$remote = json_decode( wp_remote_retrieve_body( $remote ) );
    $res = new stdClass();
	$res->name = $remote->name;
	$res->slug = $remote->slug;
	$res->author = $remote->author;
	$res->author_profile = $remote->author_profile;
	$res->version = $remote->version;
	$res->tested = $remote->tested;
	$res->requires = $remote->requires;
	$res->requires_php = $remote->requires_php;
	$res->download_link = $remote->download_url;
	$res->trunk = $remote->download_url;
	$res->last_updated = $remote->last_updated;
	$res->sections = array(
		'description' => $remote->sections->description,
		'installation' => $remote->sections->installation,
		'changelog' => $remote->sections->changelog
	);

    if( ! empty( $remote->sections->screenshots ) ) {
		$res->sections[ 'screenshots' ] = $remote->sections->screenshots;
	}

	$res->banners = array(
		'low' => $remote->banners->low,
		'high' => $remote->banners->high
	);
	
	return $res;

}


// function fulfillmen_displayNews(){
//     global $pagenow;
//     if (( $pagenow == 'admin.php' ) && ($_GET['page'] == 'wc-settings'))  {       
//         add_action('admin_notices', 'fulfillmen_news_wc_notice');
//     }
// }

// function fulfillmen_news_wc_notice()
// {
//     $str = file_get_contents('https://fulfillmen.com/plugin/woocom.php');
//     $json = json_decode($str, true);
//     $jversion = $json['Version'];
//     $releasedOn = $json['ReleaseDate'];

//     if($jversion !== constant('FULFILLMEN_VERSION')){
//         $mdata = "You are not using the latest version of the official Fulfillmen Plugin, Please update to the latest version $jversion released on $releasedOn , The plugin can be downloaded from the following link: <a href='https://fulfillmen.com/plugin/fulfillmen.zip'> Fulfillmen Official Build </a> ";
//     }
//     else{
//         $mdata = "You are running the latest version of the official Fulfillmen Plugin version $jversion, released on $releasedOn ";
//     }
//     $class = 'notice notice-success';
    
//     $message = __($mdata, 'fulfillmen');
//     printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
// }

function fulfillmen_wc_version_notice() {
    echo '<div class="notice notice-error"><p>Your current version of WooCommerce is outdated. Please update to version 8.6.0 or later for compatibility with Fulfillmen - Woocommerce plugin.</p></div>';
}

function fulfillmen_check_requirements()
{
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        $woo_plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php', false, false);

        // Check if WooCommerce version is at least 8.6.0
        if (version_compare($woo_plugin_data['Version'], '8.6.0', '>=')) {
            return true; // WooCommerce version is sufficient
        } else {
            // WooCommerce version is below 8.6.0, display notice
            deactivate_plugins('fulfillmen/fulfillmen.php');
            add_action('admin_notices', 'fulfillmen_wc_version_notice');
            return false;
        }
    } else {
        add_action('admin_notices', 'fulfillmen_missing_wc_notice');
        return false;
    }
}

function fulfillmen_missing_wc_notice()
{
    $class = 'notice notice-error';
    $message = __('Fulfillmen WooCommerce Integration requires WooCommerce to be installed and active.', 'fulfillmen');

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fulfillmen-activator.php
 */
function activate_fulfillmen()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-fulfillmen-activator.php';    
    Fulfillmen_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fulfillmen-deactivator.php
 */
function deactivate_fulfillmen()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-fulfillmen-deactivator.php';
    Fulfillmen_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_fulfillmen');
//register_activation_hook(__FILE__, 'createDBTables');
register_deactivation_hook(__FILE__, 'deactivate_fulfillmen');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-fulfillmen.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_fulfillmen()
{
    if (fulfillmen_check_requirements()) {
        global $pluginversion;
        $plugin = new Fulfillmen();
        $plugin->run();
        add_action('admin_init', 'check_plugin_updates');
        update_option("fulfillmen_version", $pluginversion);
        add_filter('pre_set_site_transient_update_plugins', 'update_fulfillmen_plugin', 9999999);
        add_filter( 'plugins_api', 'fulfillmen_plugin_info', 20, 3);

        //add_filter('plugins_api', 'custom_update_checker', 20, 3);
        //fulfillmen_displayNews();
    }
}
run_fulfillmen();
