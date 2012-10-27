<?php
add_action( 'wp_ajax_room_list', array( 'ajax_responses', 'room_list' ) );
add_action( 'wp_ajax_nopriv_room_list', array( 'ajax_responses', 'room_list' ) );

class ajax_responses extends sb_hotel_rooms {

	function room_list() {

		$post_num = intval( $_POST['posts'] );

		if( $post_num == 0 ) $post_num = 3;

		$posts = get_posts( array( 'post_type'=>'rooms', 'numberposts' => $post_num ) );

		$response = array(
				'what'=>'rooms_list',
				'action'=>'rooms_list',
				'posts'=>$post_num,
				'data'=>json_encode( $posts )
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();

		die(); // this is required to return a proper result

	}
}