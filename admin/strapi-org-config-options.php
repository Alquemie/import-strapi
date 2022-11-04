<?php

if ( !defined('ABSPATH') ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}


if ( ! class_exists( 'Strapi_Config_Settings_Page' ) ) :
/* Best Practices Settings Page */
class Strapi_Config_Settings_Page {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'create_settings' ) );
		add_action( 'admin_init', array( $this, 'setup_sections' ) );
		add_action( 'admin_init', array( $this, 'setup_fields' ) );
	}
	public function create_settings() {
		$page_title = 'Strapi Import';
		$menu_title = 'Strapi Import';
		$capability = 'manage_options';
		$slug = 'strapi-import';
		$callback = array($this, 'settings_content');
		add_options_page($page_title, $menu_title, $capability, $slug, $callback);
	}
	public function settings_content() { ?>
		<div class="wrap">
			<h1>Strapi Import Settings</h1>
			<?php settings_errors(); ?>
			<form method="POST" action="options.php">
				<?php
					settings_fields( 'strapiport' );
					do_settings_sections( 'strapiport' );
					submit_button();
				?>
			</form>
		</div> <?php
	}
	public function setup_sections() {
		add_settings_section( 'strapiport_section', 'Import content from Strapi Headless CMS into Wordpress posts', array(), 'strapiport' );
	}
	public function setup_fields() {
		$fields = array(
            array(
				'label' => 'Strapi Site URL',
				'id' => 'strapiport-url',
				'type' => 'text',
				'section' => 'strapiport_section',
				'placeholder' => 'https://localhost:1337',
			),
			array(
				'label' => 'Strapi Content Type',
				'id' => 'strapiport-content-type',
				'type' => 'text',
				'section' => 'strapiport_section',
				'placeholder' => 'posts',
			),
		);
		foreach( $fields as $field ){
			add_settings_field( $field['id'], $field['label'], array( $this, 'field_callback' ), 'strapiport', $field['section'], $field );
			register_setting( 'strapiport', $field['id'] );
		}
	}
	public function field_callback( $field ) {
		$value = get_option( $field['id'] );
		switch ( $field['type'] ) {
				case 'radio':
				case 'checkbox':
					if( ! empty ( $field['options'] ) && is_array( $field['options'] ) ) {
						$options_markup = '';
						$iterator = 0;
						foreach( $field['options'] as $key => $label ) {
							$iterator++;
							$options_markup.= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>',
							$field['id'],
							$field['type'],
							$key,
							checked($value[array_search($key, $value, true)], $key, false),
							$label,
							$iterator
							);
							}
							printf( '<fieldset>%s</fieldset>',
							$options_markup
							);
					}
					break;
			default:
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />',
					$field['id'],
					$field['type'],
					$field['placeholder'],
					$value
				);
		}
		if( $desc = $field['desc'] ) {
			printf( '<p class="description">%s </p>', $desc );
		}
	}
}
new Strapi_Config_Settings_Page();

endif;
