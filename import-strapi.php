<?php
/*
Plugin Name: Import Strapi
Description: Pull content from Strapi API to WP Post
Version: 0.5.00
Author: Chris Carrel
Author URI: https://www.linkedin.com/in/chriscarrel
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 6.0
Tested up to: 6.1
Requires PHP:  7.4

------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

if ( !defined('ABSPATH') ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}

define ('STRAPI_IMPORT_PLUGIN_PATH', dirname( __FILE__ ));

require __DIR__ . '/lib/autoload.php';

if ( ! class_exists( 'Import_Strapi' ) ) :
	/**
	 *
	 */
	class Import_Strapi {
        private $_settings = array();
        
		public function __construct() {
			self::includes();
			self::hooks();
		}

		private static function includes() {
            // $action_scheduler = require_once dirname( __FILE__ ) . '/lib/woocommerce/action-scheduler/action-scheduler.php';
            require_once dirname( __FILE__ ) . '/admin/strapi-config-options.php';
		}

		private static function hooks() {
            add_filter( 'wp_kses_allowed_html', array($this, 'esw_author_cap_filter') ,1,1 );
		}
        
        public function esw_author_cap_filter( $allowedposttags ) {

            //Here put your conditions, depending your context
            if ( !current_user_can( 'publish_posts' ) )
            return $allowedposttags;
            
            // Here add tags and attributes you want to allow
            
            $allowedposttags['iframe']=array(
                'align' => true,
                'width' => true,
                'height' => true,
                'frameborder' => true,
                'name' => true,
                'src' => true,
                'id' => true,
                'class' => true,
                'style' => true,
                'scrolling' => true,
                'loading' => true,
                'marginwidth' => true,
                'marginheight' => true,
                'allowfullscreen' => true, 
                'mozallowfullscreen' => true, 
                'webkitallowfullscreen' => true,
            );

            $allowedposttags['script']=array(
                'async' => true,
                'crossorigin' => true,
                'defer' => true,
                'integrity' => true,
                'nomodule' => true,
                'src' => true,
                'type' => true,
                'referrerpolicy' => true,
            );

            return $allowedposttags;
            
        } 
	}

   new Import_Strapi();

endif;
