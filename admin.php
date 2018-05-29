<?php

class WOO_YML_Admin {

    private $options;

    function __construct() {
        add_action('admin_menu', array(&$this, 'admin_menu'), 10, 1);
        add_action('admin_init', array(&$this, 'my_register_settings'));

        // $this->options = get_option(WOO_YML::$plugslug.'_options');
        $this->options = WOO_YML::get_options();
    }

    public function admin_menu() {
		add_submenu_page('tools.php', WOO_YML::$plugname.' Options', WOO_YML::$plugname, 'manage_options', WOO_YML::$plugslug, array(&$this, 'options_page'));
    }
    
    public function options_page() {
        ?>
        <div class="wrap">
        <h1><?php echo WOO_YML::$plugname; ?></h1>
        <div class="notice notice-info">
            <p>YML Location:<br><?php echo WOO_YML::yml_path('baseurl'); ?></p>
        </div>
        <?php //settings_errors(); ?>
        <form method="post" action="options.php">
        <?php settings_fields(WOO_YML::$plugslug); ?>
        <?php do_settings_sections(WOO_YML::$plugslug); ?>       
        <?php submit_button(__('Save Settings', 'wooyml')); ?>
        <?php 
    }

    public function my_register_settings() {
        // print 'debug';
        register_setting(
            WOO_YML::$plugslug,
            WOO_YML::$plugslug.'_options',
            array($this, 'validate_shop_settings_array')
        );

        add_settings_section(
            WOO_YML::$plugslug.'_section_general',
            __( 'Global settings', 'wooyml' ),
            null,
            WOO_YML::$plugslug
        );

        add_settings_field(
            WOO_YML::$plugslug.'_website_name',
            __( 'Website Name', 'wooyml' ),
            array($this, 'render_input_fields'),
            WOO_YML::$plugslug,
            WOO_YML::$plugslug.'_section_general',
            array(
                'label_for'   => 'name',
                'placeholder' => __( 'Website Name', 'wooyml' ),
                'description' => '',
                'type'        => 'text',
            )
        );

        add_settings_field(
            WOO_YML::$plugslug.'_company_name',
            __( 'Company Name', 'wooyml' ),
            array($this, 'render_input_fields'),
            WOO_YML::$plugslug,
            WOO_YML::$plugslug.'_section_general',
            array(
                'label_for'   => 'company',
                'placeholder' => __( 'Company Name', 'wooyml' ),
                'description' => '',
                'type'        => 'text',
            )
        );

        add_settings_field(
            WOO_YML::$plugslug.'_cron',
            __( 'Cron', 'wooyml' ),
            array($this, 'render_input_fields'),
            WOO_YML::$plugslug,
            WOO_YML::$plugslug.'_section_general',
            array(
                'label_for'   => 'cron',
                'placeholder' => __( 'Cron', 'wooyml' ),
                'description' => '',
                'type'        => 'select',
                'options'     => array(
					'disabled'   => __( 'Disabled', 'wooyml' ),
					'hourly'     => __( 'Every Hour', 'wooyml' ),
                    'daily'      => __( 'Every Day', 'wooyml' ),
                    'twicedaily' => __( 'Twice Daily', 'wooyml' ),
                    'weekly'     => __( 'Every Week', 'wooyml' ),
                    'monthly'    => __( 'Every Month', 'wooyml' ),
				),
            )
        );


        $attributes_array['disabled'] = __( 'Disabled', 'wooyml' );
		foreach($this->get_attributes() as $attribute) {
			$attributes_array[$attribute[0]] = $attribute[1];
		}

        add_settings_field(
			WOO_YML::$plugslug.'_vendor',
			__( 'Vendor', 'wooyml' ),
			array($this, 'render_input_fields'),
			WOO_YML::$plugslug,
			WOO_YML::$plugslug.'_section_general',
			array(
				'label_for'   => 'vendor',
				'description' => __( 'Vendor property.', 'wooyml' ),
				'type'        => 'select',
				'options'     => $attributes_array,
			)
        );
        
        add_settings_field(
			WOO_YML::$plugslug.'_generate_manually',
			__( 'Generate Manually', 'wooyml' ),
			array( $this, 'render_input_fields' ),
			WOO_YML::$plugslug,
			WOO_YML::$plugslug.'_section_general',
			array(
				'label_for'   => 'generate_manually',
				'description' => __( 'Generate Manually.', 'wooyml' ),
				'type'        => 'submit'
			)
		);

    }

    public function render_input_fields($args) {
        // print_r($args);

        switch($args['type']) {
            case 'text':
            $value = isset($this->options[$args['label_for']]) ? $this->options[$args['label_for']] : false;
            ?>
                <input id="<?php echo esc_attr($args['label_for']); ?>"
                    type="<?php echo esc_attr($args['type']); ?>"
                    name="<?php echo WOO_YML::$plugslug; ?>_options[<?php echo esc_attr($args['label_for']); ?>]"
                    value="<?php echo esc_attr($value); ?>"
                    placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                >
            <?php 
            break;

            case 'select': //print_r($args);

            if($args['label_for'] == 'vendor'):?>
            <select id="<?php echo esc_attr($args['label_for']); ?>"
					name="<?php echo WOO_YML::$plugslug; ?>_options[<?php echo esc_attr($args['label_for']); ?>]">
				<?php foreach($args['options'] as $key => $value): ?>
					<option value="<?php echo esc_attr($key); ?>" <?php selected($this->options[$args['label_for']] === $key); ?>>
						<?php echo esc_html($value); ?>
					</option>
				<?php endforeach; ?>
			</select>
            <?php endif;

            if($args['label_for'] == 'cron'):?>
            <select id="<?php echo esc_attr($args['label_for']); ?>"
                    name="<?php echo WOO_YML::$plugslug; ?>_options[<?php echo esc_attr($args['label_for']); ?>]">
                <?php foreach($args['options'] as $key => $value): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($this->options[$args['label_for']] === $key); ?>>
                        <?php echo esc_html($value); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif;
            break;

            case 'submit':
            $value = isset($this->options[$args['label_for']]) ? $this->options[$args['label_for']] : false;
            ?>
                <input id="<?php echo esc_attr($args['label_for']); ?>"
                    type="<?php echo esc_attr($args['type']); ?>"
                    name="<?php echo WOO_YML::$plugslug; ?>_options[<?php echo esc_attr($args['label_for']); ?>]"
                    value="Generate" 
                    class="button button-primary"
                >
            <?php 
            break;
        
        }
    }

    private function get_attributes() {
		global $wpdb;
		return $wpdb->get_results("SELECT attribute_name AS attr_key, attribute_label AS attr_value FROM $wpdb->prefix" . "woocommerce_attribute_taxonomies", ARRAY_N);
	}

    public function validate_shop_settings_array($input) {
        // +debug
        // ob_start(); print_r($input); $rs = ob_get_contents(); ob_end_clean();
        // $fp = fopen(dirname(__FILE__).'/debug-cron.txt','w'); fputs($fp, $rs); fclose($fp);
        // -debug

        if(!empty($input) && !empty($input['cron'])) {
            WOO_YML_Cron::wooyml_cron_update($input['cron']);
        }

        if(isset($input['generate_manually'])) {
            WOO_YML::write_yml_file();
        }

        return $input;
    }
    
}
$wooyml_admin = new WOO_YML_Admin();

?>