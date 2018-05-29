<?php
/*
Plugin Name: Woocommerce YML
Description: Woocommerce YML provides a way to export products from WooCommerce installations into a YML stream.
Version: 1.0
Author: myster
Author URI: http://www.limeirastudio.com/
License: GPL2
Copyright: Limeira Studio
WC tested up to: 3.4.x
*/
?>
<?php

class WOO_YML {

	public static $plugslug;
	public static $plugname;
	public static $defaults;
	
	function __construct() {

		self::$plugname = 'Export to Rozetka.ua';
		self::$plugslug = 'wooyml';

		self::$defaults = array(
			'name' 		=> get_option('blogname'),
			'company'	=> get_option('blogname'),
			'file' 		=> 'rozetka.xml',
			//Accepts: hourly, daily, twicedaily, weekly, monthly.
			'cron'			=> 'daily',
			'vendor' 		=> 'disabled'
		);

		add_action('template_redirect', array(&$this, 'yml_redirect'), 10, 1);

		require plugin_dir_path(__FILE__).'admin.php';
		require plugin_dir_path(__FILE__).'cron.php';

		if(!self::check_dependencies()) {
			add_action('admin_notices', array(&$this, 'activation_message'), 10, 1);
			return;
		}
	
	}

	public static function get_options() {
		return get_option(self::$plugslug.'_options');
	}

	// public function yml_redirect() {
	// 	if($this->export_url()) {
	// 		$this->write_yml_file();	
	// 		wp_redirect($this->yml_path('baseurl'));
	// 		die();
	// 	}
	// }

	public static function write_yml_file() {
		file_put_contents(self::yml_path('basedir'), self::generate_yml());
	}

	public function generate_yml() {
		$options = self::get_options();
		$yml = '';
		$yml .= '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
		$yml .= '<!DOCTYPE yml_catalog SYSTEM "shops.dtd">'.PHP_EOL;
		$yml .= '<yml_catalog date="'.current_time('Y-m-d H:i').'">'.PHP_EOL;
		$yml .= '<shop>'.PHP_EOL;
		$yml .= '<name>'.esc_html($options['name']).'</name>'.PHP_EOL;
		$yml .= '<company>'.esc_html($options['company']).'</company>'.PHP_EOL;
		$yml .= '<url>'.home_url().'</url>'.PHP_EOL;
		$yml .= '<currencies>'.PHP_EOL;
		$yml .= '<currency id="'.get_option('woocommerce_currency').'" rate="1"/>'.PHP_EOL;
		$yml .= '</currencies>'.PHP_EOL;
		$yml .= '<categories>'.PHP_EOL;
		$args = array(
			'taxonomy' => 'product_cat',
			'orderby'  => 'term_id',
		);
		$terms = get_terms($args);
		foreach($terms as $term) {
			$yml .= '<category id="'.$term->term_id.'">'.$term->name.'</category>'.PHP_EOL;
		}
		$yml .= '</categories>'.PHP_EOL;
		$yml .= '<offers>'.PHP_EOL;
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
			),
			'orderby'   => 'date',
			'order'     => 'DESC',
		);
		$ymlo = new WP_Query($args);
		while($ymlo->have_posts()) : $ymlo->the_post();
			$product = wc_get_product($ymlo->post->ID);
			$yml .= '<offer id="'.$product->get_id().'" available="'.(($product->is_in_stock()) ? 'true' : 'false').'">'.PHP_EOL;
			$yml .= '<url>'.htmlspecialchars(get_permalink($product->get_id())).'</url>'.PHP_EOL;
			if($product->get_sale_price() && ($product->get_sale_price() < $product->get_regular_price())) {
				$yml .= '<price>'.$product->get_sale_price().'</price>'.PHP_EOL;
				$yml .= '<oldprice>'.$product->get_regular_price().'</oldprice>'.PHP_EOL;
			} else {
				$yml .= '<price>'.$product->get_regular_price().'</price>'.PHP_EOL;
			}
			$yml .= '<currencyId>'.get_option('woocommerce_currency').'</currencyId>'.PHP_EOL;
			$categories = get_the_terms($product->get_id(), 'product_cat');
			if($categories) {
				$category = array_shift($categories);
				$yml .= '<categoryId>'.$category->term_id.'</categoryId>'.PHP_EOL;
				// TODO: Vendor Name
				if($category->parent !=0) {
					$vendor = $category->name;
				}
			}
			$main_image = get_the_post_thumbnail_url($product->get_id(), 'full');
			$yml .= '<picture>'.esc_url($main_image).'</picture>'.PHP_EOL;
			$attachment_ids = $product->get_gallery_attachment_ids();
			if(!empty($attachment_ids)) {
				if(count($attachment_ids)>9)  {
					$attachment_ids = array_slice($attachment_ids, 0, 9);
				}
				foreach($attachment_ids as $att_id) {
					$yml .= '<picture>'.esc_url(wp_get_attachment_url($att_id)).'</picture>'.PHP_EOL;
				}
			}
			$yml .= '<vendor>'.wp_strip_all_tags($vendor).'</vendor>'.PHP_EOL;
		
			$stock_quantity = (!empty($product->get_stock_quantity())) ? $stock_quantity : 1;
			$yml .= '<stock_quantity>'.$stock_quantity.'</stock_quantity>'.PHP_EOL;
		
			$yml .= '<name>'.$product->get_title().'</name>'.PHP_EOL;
		
			$yml .= '<description><![CDATA['.apply_filters('the_content', get_the_content()).']]></description>'.PHP_EOL;
		
			$attributes = $product->get_attributes();
			foreach($attributes as $param) {
				$taxonomy = wc_attribute_taxonomy_name_by_id($param->get_id());
				$param_value = $product->get_attribute($taxonomy);
				if(!empty($param_value)) {
					$yml .= '<param name="'.wc_attribute_label($taxonomy).'">'.$param_value.'</param>'.PHP_EOL;
				}
			}
			
			$yml .= '</offer>'.PHP_EOL;
		endwhile;
		
		$yml .= '</offers>'.PHP_EOL;
		$yml .= '</shop>'.PHP_EOL;
		$yml .= '</yml_catalog>'.PHP_EOL;

		return $yml;

	}
	
	// public function export_url() {
	// 	global $wp;
	// 	return ($wp->request ==self::$defaults['yml_url_arg']) ? true : false;
	// }

	public function yml_path($param) {
		$updir = wp_upload_dir();
		return $updir[$param].'/'.self::$defaults['file'];
	}

	private function set_locale() {
		load_plugin_textdomain(
			self::$plugslug,
			false,
			dirname(dirname(plugin_basename(__FILE__))).'/lang/'
		);
	}

	public function activate() {
		update_option(self::$plugslug.'_options', self::$defaults);
		WOO_YML_Cron::wooyml_cron_update(self::$defaults['cron']);
		// self::message('error', 'test1');
			// array(
			// 	'cron'=>self::$defaults['cron']
			// )
		// );
    }

    public static function deactivate() {
		$timestamp = wp_next_scheduled(self::$plugslug.'_cron_hook');
		wp_unschedule_event($timestamp, self::$plugslug.'_cron_hook');
		delete_option(self::$plugslug.'_options');
		unlink(self::yml_path('basedir'));
	}

	public static function check_dependencies() {
		if(!function_exists('get_plugins')) {
			require_once ABSPATH.'wp-admin/includes/plugin.php';
		}

		if(empty(get_plugins('/woocommerce')) || !is_plugin_active('woocommerce/woocommerce.php')) {
			return false;
		}

		return true;
	}

	public function activation_message() {
		?>
		<div class="error notice">
			<p><?php _e('<a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> is not activated.', 'wooyml' ); ?></p>
		</div>
		<?php
	}

}

register_activation_hook(__FILE__, 'WOO_YML::activate');
register_deactivation_hook(__FILE__, 'WOO_YML::deactivate');

$wooyml = new WOO_YML();

?>
