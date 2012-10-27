jQuery(document).ready(function($) {
	
	jQuery.room_list_standard = function( action, myRowId, idContent, contentIndex ){
		var postList = $(myRowId).find('.portlet-content'), posts = 3;
		
		switch( action ) {
		case 'create' :
			postList.html( 'Please wait, loading..' );
	
			$( myRowId+' .portlet-header .ui-icon-gear' ).click( function() {
				posts = prompt( 'How Many Posts?' );
				posts = parseInt( posts );
				console.log( posts );
				if( typeof posts == 'number' ) {
					
					print_posts( postList, posts );
				}
			});
			$( myRowId+' .portlet-header .ui-icon-minusthick' ).click( function() {
				$(this).toggleClass('ui-icon-minusthick').toggleClass('ui-icon-plusthick');
				$(this).parents('.portlet:first').find('.portlet-content').toggle();
			});
			$( myRowId+' .portlet-header .ui-icon-close').click(function() {
				var isOK = confirm(jfcow_cms_js_var.msgConfirm);
				if(isOK)
					$(this).parents(myRowId).remove();
			});
			
			print_posts( postList, 2 );
			break;
		
		case 'save':
			return '[room-list-standard /]';
			break;
		}
		
	};
	
	function print_posts( postList, posts ) {
		var xmlRequest = $.ajax({
			  url: cptRooms.ajaxurl,
			  type: 'POST',
			  data: {
				  action: 'room_list',
				  posts: posts
				  },
			  dataType: 'xml'
			});
		
		xmlRequest.done(function( xmlData) {
			postList.html( '' );
			postList.html( '<span class="post_number" style="display: none;">'+posts+'</span>')
			jqData = $( $( xmlData ).find( 'response_data') );
			jqObj = eval( jqData[0]['textContent'] );
			$.each( jqObj, function( idx, post ) {
				postList.append( '<p><b>'+post.post_title+'</b><br />'+post.post_excerpt+'</p>' );
				console.log( post.post_title );			
			});
			
		});
	}
	
});