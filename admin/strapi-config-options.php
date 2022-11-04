<?php

if ( !defined('ABSPATH') ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}


if ( ! class_exists( 'Strapi_Config_Settings_Page' ) ) :
/* Best Practices Settings Page */
class Strapi_Config_Settings_Page {
	private $action_scheduler;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'create_settings' ) );
		add_action( 'admin_init', array( $this, 'setup_sections' ) );
		add_action( 'admin_init', array( $this, 'setup_fields' ) );
		$this->action_scheduler = require_once STRAPI_IMPORT_PLUGIN_PATH . '/lib/woocommerce/action-scheduler/action-scheduler.php';

		add_action( 'init', function() {
			add_action( 'import_entry_asap', array( $this, 'importEntry') );
			add_action( 'import_all_strapi_asap', array( $this, 'scheduleImport') );
		
			/*
			if ( isset( $_GET['one_time_action'] ) ) {
				as_enqueue_async_action(
					'one_time_action_asap',
					[ $_GET['one_time_action'] ]
				);
			}
			*/
		} );
	}

	public function create_settings() {
		$page_title = 'Strapi Import';
		$menu_title = 'Strapi Import';
		$capability = 'manage_options';
		$slug = 'strapi_import';
		$callback = array($this, 'strapi_content');
		$icon = 'dashicons-move';
		$position = 75;
		add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);
		// add_menu_page($page_title, $menu_title, $capability, $slug, $callback);

		add_submenu_page( $slug, 'Settings', 'Settings', 'manage_options', 'strapi_settings', array($this, 'settings_content') );

	}
	public function strapi_content() { ?>
		<div class="wrap">
			<h1>Strapi Import</h1>

			<?php 
			if ( ! empty( $_POST ) && check_admin_referer( 'strapi_import_action', 'strapiport_nonce' ) ) {
				// process form data, e.g. update fields
				if ($_POST['allentries'] == 1) {
					// Call Import All Job
					echo "as_enqueue_async_action( 'import_all_strapi_asap' );<p>";
				} else {
					foreach($_POST['strapient'] as $eId) {
						echo "as_enqueue_async_action( 'import_entry_asap', " . $eId . " );<p>";
						$this->importEntry($eId);
					}
						
				}

			 }
			?>
			<form method="POST"> <?php /* action="<?php echo $_SERVER['PHP_SELF']; ?>?page=strapi_import"> */ ?>
				<?php 
				$pageNum = (is_numeric($_GET['start'])) ? $_GET['start'] : 1;

				$blog = $this->getStrapi($pageNum,40);
				if (is_array($blog)) {
					echo "<p>There are " . $blog['meta']['pagination']['total'] . " entries on Strapi server.</p>";
					?>
					<p>
						<input type="radio" name="allentries" value="1" onClick="toggleEntries();">All Entries<br />
						<input type="radio" name="allentries" value="0" checked=1 onClick="toggleEntries();">Selected Entries
					</p>
					<div id="entrylist">
					<?php
					echo "<fieldset id='entryfields'><legend><h4>Available Entries</h4></legend>";
					foreach($blog['data'] as $entry) {
						echo '<input type="checkbox" name="strapient[]" id="strapient_' . $entry['id'] . '" value="' . $entry['id'] . '" />&nbsp;';
						echo  $entry['attributes']['shortName'] . '<br/>';
					}
					echo "</fieldset>";
					?>
					
						<p><h3>
						<?php
						$pgCount = $blog['meta']['pagination']['pageCount'];
						$i = 1;
						while($i <= $pgCount) {
							if ($i == $blog['meta']['pagination']['page']) { 
								echo $i;
							} else {
								echo "<a href='" . $_SERVER['PHP_SELF'] . "?page=strapi_import&start=" . $i . "'>" . $i . "</a>";
							}
							$i++;
							echo "&nbsp;";
						}
						
						?>
						</h3></p>
					</div>
					<script>
						function toggleEntries() {
							var allSet = jQuery('input[name="allentries"]:checked').val();
							if (allSet == 1) {
								jQuery('#entrylist').hide();
							} else {
								jQuery('#entrylist').show();
							}
						}
					</script>
					<?php
				} else {
					echo "<h2 class='error'>" . $blog . "</h2>";
				}
				?>
				<?php wp_nonce_field( 'strapi_import_action', 'strapiport_nonce' ); ?>
    			<input type="submit" value="Import Entries" class="button button-primary button-large">
			</form>
			<?php 
			echo "<pre>";
			print_r($_POST);
			echo "</pre>";
			?>
		</div> <?php
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
			array(
				'section' => 'strapiport_section',
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
					$field['placeholder'],
					$value
				);
		}
		if( $desc = $field['desc'] ) {
			printf( '<p class="description">%s </p>', $desc );
		}
	}

	private function getStrapi($pg = 1, $limit = 25) {
		$host = get_option( 'strapiport-url' );
		$host = ( substr($host, -1) == "/" ) ? $host : $host . "/";
		$type = get_option( 'strapiport-content-type' );
		// echo "<P>Getting Content from: " . $host . "api/" . $type . "/</p>";
		$curl = curl_init();

		// Sending GET request to reqres.in
		// server to get JSON data
		curl_setopt($curl, CURLOPT_URL,
		$host . "api/" . $type . "/?pagination[page]=" . $pg . "&pagination[pageSize]=" . $limit . "&pagination[withCount]=1");

		// Telling curl to store JSON
		// data in a variable instead
		// of dumping on screen
		curl_setopt($curl,
		CURLOPT_RETURNTRANSFER, true);

		// Executing curl
		$response = curl_exec($curl);

		// Checking if any error occurs
		// during request or not
		$result = "";
		if($e = curl_error($curl)) {
			$result = "Error: " . $e;
		} else {

			// Decoding JSON data
			$result =json_decode($response, true);
		}

		// Closing curl
		curl_close($curl);
				
		return $result;
	}

	public function scheduleImport() {
		// Loop through all Strapi Entries and schedule individual import job


	}

	public function importEntry($entryId = 0) {
		if ($entryId != 0) {
			// Lookup Entry from Strapi
			$entry = $this->lookupStrapiEntry($entryId);
			foreach($entry['attributes']['contentSections'] as $section) {
				echo "Section: " . $section['__component'];
			}

			// echo "<pre>" . print_r($entry, true) . "</pre>";
			// Loop content sections
				// if RichText
					// Convert Markdown to HTML
				// if Youtube
				// if Trustpilot

			
			// Create WP Post
			// 
		}
	}

	private function lookupStrapiEntry($entryId) {
		$host = get_option( 'strapiport-url' );
		$host = ( substr($host, -1) == "/" ) ? $host : $host . "/";
		$type = get_option( 'strapiport-content-type' );
		// echo "<P>Getting Content from: " . $host . "api/" . $type . "/</p>";
		$curl = curl_init();

		// Sending GET request to reqres.in
		// server to get JSON data
		curl_setopt($curl, CURLOPT_URL,
		$host . "api/" . $type . "/" . $entryId . "/?populate=%2A");

		// Telling curl to store JSON
		// data in a variable instead
		// of dumping on screen
		curl_setopt($curl,
		CURLOPT_RETURNTRANSFER, true);

		// Executing curl
		$response = curl_exec($curl);

		// Checking if any error occurs
		// during request or not
		$result = "";
		if($e = curl_error($curl)) {
			$result = "Error: " . $e;
		} else {

			// Decoding JSON data
			$result =json_decode($response, true);
		}

		// Closing curl
		curl_close($curl);

		return $result['data'];
	}

	private function buildRichText($section) {

	}

	private function buildYouTube($section) {

	}

	private function buildTrustPilot($section) {

	}

}
new Strapi_Config_Settings_Page();

endif;
