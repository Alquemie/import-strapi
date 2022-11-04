<?php

if ( !defined('ABSPATH') ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}


// Settings Page: StrapiImport
// Retrieving values: get_option( 'your_field_id' )
class StrapiImport_Settings_Page {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'wph_create_settings' ) );
		add_action( 'admin_init', array( $this, 'wph_setup_sections' ) );
		add_action( 'admin_init', array( $this, 'wph_setup_fields' ) );
	}

	public function wph_create_settings() {
		$page_title = 'Strapi Importer';
		$menu_title = 'Strapi Import';
		$capability = 'manage_options';
		$slug = 'StrapiImport';
		$callback = array($this, 'wph_settings_content');
                $icon = 'dashicons-move';
		$position = 75;
		add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);
		
	}
    
	public function wph_settings_content() { ?>
		<div class="wrap">
			<h1>Strapi Import</h1>
			<?php settings_errors(); ?>
			<form method="POST" action="options.php">
				<?php
					settings_fields( 'StrapiImport' );
					do_settings_sections( 'StrapiImport' );
					submit_button();
				?>
			</form>
		</div> <?php
	}

	public function wph_setup_sections() {
		add_settings_section( 'StrapiImport_section', 'Import content from Strapi Headless CMS', array(), 'StrapiImport' );
	}

	public function wph_setup_fields() {
		$fields = array(
                    array(
                        'section' => 'StrapiImport_section',
                        'label' => 'Strapi URL',
                        'placeholder' => 'https://localhost:1337',
                        'id' => 'strapiport-url',
                        'type' => 'text',
                    ),
        
                    array(
                        'section' => 'StrapiImport_section',
                        'label' => 'Strapi Content Type',
                        'placeholder' => 'posts',
                        'id' => 'strapiport-content-type',
                        'type' => 'text',
                    ),
        
                    array(
                        'section' => 'StrapiImport_section',
                        'label' => 'WordPress Content Type',
                        'id' => 'strapiport-wp-type',
                        'type' => 'radio',
                        'options' => array(
                            'post',
                            'page'
                        )
                    )
		);
		foreach( $fields as $field ){
			add_settings_field( $field['id'], $field['label'], array( $this, 'wph_field_callback' ), 'StrapiImport', $field['section'], $field );
			register_setting( 'StrapiImport', $field['id'] );
		}
	}
	public function wph_field_callback( $field ) {
		$value = get_option( $field['id'] );
		$placeholder = '';
		if ( isset($field['placeholder']) ) {
			$placeholder = $field['placeholder'];
		}
		switch ( $field['type'] ) {
            
            
                        case 'radio':
                            if( ! empty ( $field['options'] ) && is_array( $field['options'] ) ) {
                                $options_markup = '';
                                $iterator = 0;
                                foreach( $field['options'] as $key => $label ) {
                                    $iterator++;
                                    $options_markup.= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>',
                                    $field['id'],
                                    $field['type'],
                                    $key,
                                    checked($value, $key, false),
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
					$placeholder,
					$value
				);
		}
		if( isset($field['desc']) ) {
			if( $desc = $field['desc'] ) {
				printf( '<p class="description">%s </p>', $desc );
			}
		}
	}
    
}
new StrapiImport_Settings_Page();
                