<?php
/*
Plugin Name: SB Hotel - Rooms
Plugin URI: http://sitebridge.net/
Description: Sitebridge product of wordpress plugin (part of SB Hotel package)
Version: 1.0.0
Author: Sitebridge Development Team


GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// variables declaration
// namespace sb-hotel\cpt\rooms;

/*
 * start from here
 */
class sb_hotel_rooms {
	public $my_options_name = '_sb_hotel_rooms';
	public $taxonomies;
	public $my_path;

	private $my_options;

	function sb_hotel_rooms() {
		$this->__construct();
	}

	function __construct() {
		$this->my_path = plugins_url( '/', __FILE__ );

		$this->init();

	}

	function init() {
		global $jf_main_options_menu_created;

		define('WP_POST_REVISIONS', false );

		if( $_POST['sb_hotel_options'] ) {
			update_option( $this->my_options_name, $_POST['sb_hotel_options']['rooms'] );
		}

		$this->my_options = get_option( $this->my_options_name );

		add_action( 'init', array( &$this, 'register_custom_post_type' ) );
		if( $this->my_options['taxonomies'] )
			add_action( 'init', array( &$this, 'register_custom_taxonomies' ) );
		add_action( 'add_meta_boxes', array( &$this, 'add_metaboxes_standard' ) );
// 		add_action( 'add_meta_boxes', array( &$this, 'add_metaboxes_custom' ) );
		add_action( 'save_post', array( &$this, 'save_postmeta' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'custom_columns' ) );
		add_action( 'admin_menu', array( &$this, 'register_sub_menu' ) );

		add_filter( 'manage_rooms_posts_columns', array( &$this, 'change_room_columns' ) );

		add_filter( 'the_content', array( &$this, 'rooms_filter_content' ) );

		if( $this->my_options['jqlightbox'] )
			add_action( 'wp_print_scripts', array( &$this, 'rooms_action_script_jqlightbox' ) );

		add_action('wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

		// default must have custom taxonomy
		$this->taxonomies[0] = array(
			'id'			=>'room-types',
			'show_tagcloud' => true,
			'args' => array(
				'hierarchical' 	=> true,
				'label' 		=> __( 'Room Types', 'sb_hotel_rooms' ),
				'search_items'	=> __( 'Search Room Types', 'sb_hotel_rooms' ),
			),
		);
		$this->taxonomies[1] = array(
			'id'			=>'room-facilities',
			'show_tagcloud' => true,
			'args' => array(
				'hierarchical' 	=> false,
				'label' 		=> __( 'Room Facilities', 'sb_hotel_rooms' ),
				'search_items'	=> __( 'Search Room Facilities', 'sb_hotel_rooms' ),
			),
		);

	}

	function enqueue_scripts() {
		wp_localize_script( 'cpt-rooms', 'cptRooms', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	/*
	 * register room custom post type
	 */
	function register_custom_post_type() {

		$args = array(
			'label'	=> 'rooms',
			'labels' => array(
				'name' => __( 'Rooms', 'sb_hotel_rooms' ),
				'singular_name' => __( 'Room', 'sb_hotel_rooms' ),
				'add_new' => __( 'New Room', 'sb_hotel_rooms' ),
				'add_new_item' => __( 'New Room', 'sb_hotel_rooms' ),
				'edit_item' => __( 'Edit Room', 'sb_hotel_rooms' ),
				'not_found' => __( 'Room Not Found', 'sb_hotel_rooms' ),
			),
			'public' => true,
			'has_archive' => true,
			'menu_position'=> 57,
			'menu_icon' => plugins_url( 'images', __FILE__ ).'/room_icon.png',
			'supports' => array( 'title', 'editor', 'page-attributes', 'thumbnail' ),
		);

		register_post_type( 'rooms', $args  );
	}

	/*
	 * meta box content for room
	 */
	function add_metaboxes_standard() {
		add_meta_box( 'room-details', __('Room Details', 'sb_hotel_rooms'), array( &$this, 'room_details'), 'rooms', 'side', 'default');
		add_meta_box( 'room-details-picture', __('Pictures', 'mall'), array( &$this, 'room_pictures' ), 'rooms', 'normal', 'default' );

	}
	function room_details( $post ) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'room-details' );
		$metas = get_post_meta( $post->ID, '_room_details', true );

		echo '<p><label for="room-price">'.__("Price", 'sb_hotel_rooms' ).'</label><br />
			<input type="text" id="room-price" name="Room[Price]" class="number" value="'.$metas['Price'].'" size="15" /></p>';
		echo '<p><label for="room-amenities">'.__('Amenities', 'sb_hotel_rooms' ).'</label><br />
			<textarea id="room-amenities" name="Room[Amenities]" rows="5">'.$metas['Amenities'].'</textarea></p>';
	}
	/*
	 * pictures metabox
	 */
	public function room_pictures( $post ) {
		$metas = get_post_meta( $post->ID, 'sb_room_pictures' );

		require_once( plugin_dir_path( __FILE__ ).'forms/room_pictures.php' );

	}

	/*
	 * custom taxonomies for rooms
	 */
	function register_custom_taxonomies() {
		for( $i = 0; $i < count( $this->taxonomies ); $i++ ) {
			$taxonomy = $this->taxonomies[$i];
			register_taxonomy( $taxonomy['id'], 'rooms', $taxonomy['args'] );
		}

	}

	/*
	 * Save process for all custom post types
	 */
	function save_postmeta( $post_id ) {

		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( wp_verify_nonce( $_POST['room-details'], plugin_basename( __FILE__ ) ) ) {

		} else {
	      return;
		}

		// Check permissions
		if ( !current_user_can( 'edit_posts' ) )
			return;

		if($_POST['Room']) {
			$metadata = $_POST['Room'];
			update_post_meta($post_id, '_room_details', $metadata);
		}
		if( $_POST['rooms']['pictures'] ) {
			update_post_meta( $post_id, 'sb_room_pictures' , $_POST['rooms']['pictures'] );
		}
	}

	/*
	 * Custom columns handler
	 */
	function custom_columns( $column ) {
		global $post;

		$metas = get_post_meta( $post->ID, '_room_details', true);

		if ("ID" == $column) echo $post->ID;
		elseif ("title" == $column) echo $post->post_title;
		elseif ("amenities" == $column) echo nl2br( $metas['Amenities'] );
		elseif ("price" == $column) echo $metas['Price'];
	}
	/*
	 * room columns layout
	 */
	function change_room_columns( $cols ) {
		$cols = array(
			'cb'			=> '<input type="checkbox" />',
			'title'			=> __( 'Title', 'sb_hotel_rooms' ),
			'price'			=> __( 'Price', 'sb_hotel_rooms' ),
			'amenities'		=> __( 'Ammenities', 'sb_hotel_rooms' ),
		);
		return $cols;
	}

	function register_sub_menu() {
		add_submenu_page( 'options-general.php', 'Rooms Options', 'Rooms Options', 'manage_options', 'jfcow-options-rooms', array( &$this, 'submenu_page_callback' ) );
	}

	function mainmenu_page_callback() {
		echo '<h3>My Custom Mainmenu Page</h3>';
	}

	function submenu_page_callback() {
	    if ( $_REQUEST['saved'] ) echo '<div id="message" class="updated fade"><p><strong>'.$themename.' settings saved.</strong></p></div>';
	    if ( $_REQUEST['reset'] ) echo '<div id="message" class="updated fade"><p><strong>'.$themename.' settings reset.</strong></p></div>';

	    ?>

	    <div class="wrap">

	    <?php screen_icon( 'options-general' ); echo '<h2>' . get_current_theme() .' '. __( 'Rooms Options' ) . '</h2><br clear="all">';
	    // This shows the page's name and an icon if one has been provided ?>

	    <form method="post">

	    <?php $options = get_option( $this->my_options_name ); ?>

	    <?php settings_fields( 'sb_hotel_options' );
	    /* This function outputs some hidden fields required by the form,
	    including a nonce, a unique number used to ensure the form has been submitted from the admin page
	    and not somewhere else, very important for security */ ?>

		<table class="form-table">
		<tbody>
		<tr valign="top" class="odd">
			<th scope="row"><label for="sb_hotel_options_rooms_taxonomies"><?php _e( 'With Room Types & Facilities', 'sb_hotel_rooms' ); ?></label></th>
			<td><input type="checkbox" id="sb_hotel_options_rooms_taxonomies" name="sb_hotel_options[rooms][taxonomies]" value="taxonomies" <?php echo ( $options['taxonomies'] ? 'checked' : '' ); ?> ></td>
		</tr>
		<tr valign="top" class="even">
			<th scope="row"><label for="blogname"><?php _e( 'Use jQuery Lightbox for Room\'s Pictures', 'sb_hotel_rooms' ); ?></label></th>
			<td><input type="checkbox" id="sb_hotel_options_rooms_taxonomies" name="sb_hotel_options[rooms][jqlightbox]" value="jqlightbox" <?php echo ( $options['jqlightbox'] ? 'checked' : '' ); ?> ></td>
		</tr>
		</tbody>
		</table>
	 	<input type="hidden" name="sb_hotel_options[action]" value="save" />
		<p class="submit"><input type="submit" value="Save Changes" class="button-primary" id="submit" name="submit"></p>
	    </form>

	    </div>
		<?php
	}

	/*
	 * Filters
	 *
	 */

	function rooms_filter_content( $content ) {
		if( $GLOBALS['post']->post_type == 'rooms' ) {
			$metas = get_post_meta( $GLOBALS['post']->ID, '_room_details', true );
			$price		= '<div class="room-price"><label class="title">'.__( 'Price: ', 'rooms' ).'</label><span class="content">'.$metas['Price'].'</span></div>';
			$amenities	= '<div class="room-ammenities"><label class="title">'.__( 'Amenities: ', 'rooms' ).'</label><span class="content">'.$metas['Amenities'].'</span></div>';

			$metas = json_decode( get_post_meta( $GLOBALS['post']->ID, 'sb_room_pictures', true ) );
			if( is_array($metas) ) {
				$pictures = '<div class="room-picture-container">';
				foreach( $metas as $meta ) {
					$img_url = get_permalink( $meta->ID );
					$img_attributes = wp_get_attachment_image_src( $meta->ID, 'full' );
					$pictures .= '<div class="room-pictures room-picture-'.$meta->ID.'"><a href="'.$img_attributes[0].'"><img src="'.$meta->url.'" title="'.$meta->title.'" /></a></div>';
				}
				$pictures .= '</div>';
			}

			return $content.$price.$amenities.$pictures;
		}

		// otherwise returns the database content
		return $content;
	}

	function rooms_action_script_jqlightbox() {
		if( $GLOBALS['post']->post_type == 'rooms' ) {
			wp_register_style( 'jq-lightbox', plugin_dir_url( __FILE__ ).'css/jquery.lightbox-0.5.css');
			wp_enqueue_style( 'jq-lightbox' );

			wp_register_script( 'jq-lightbox', plugin_dir_url( __FILE__ ).'js/jquery-lightbox/jquery.lightbox-0.5.min.js', array( 'jquery' ) );
			wp_register_script( 'cpt-rooms', plugin_dir_url(__FILE__).'js/room-list-standard.js' );
			wp_enqueue_script( 'jq-lightbox' );
			wp_enqueue_script( 'cpt-rooms' );

			$lightbox_images = array(
				'imageLoading'	=> plugin_dir_url(__FILE__) . '/images/lightbox-ico-loading.gif',
				'imageBtnPrev'	=> plugin_dir_url(__FILE__) . '/images/lightbox-btn-prev.gif',
				'imageBtnNext'	=> plugin_dir_url(__FILE__) . '/images/lightbox-btn-next.gif',
				'imageBtnClose'	=> plugin_dir_url(__FILE__) . '/images/lightbox-btn-close.gif',
				'imageBlank'	=> plugin_dir_url(__FILE__) . '/images/lightbox-blank.gif',
			);
			wp_localize_script( 'cpt-rooms', 'lightbox_images', $lightbox_images );
		}
	}
}

$sb_hotel_rooms = new sb_hotel_rooms();

require_once( 'ajax_responses.php' );
