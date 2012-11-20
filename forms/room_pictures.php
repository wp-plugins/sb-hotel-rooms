<?php
?>
<style>
.button-container {
	height: 24px;
	text-align: right;
}
.wrap .add-new-h2 {
	top: 4px;
	margin-right: 3px;
}
.room-pictures-container {
	width: 220px;
	overflow: hidden;
	border: 1px solid silver;
	text-align: center;
	float: left;
}
.room-pictures-container .close {
	cursor: pointer;
}
</style>
<script>
var orig_send_to_editor = window.send_to_editor;

jQuery(document).ready(function($) {
	var metaValue = '<?php echo $metas[0]; ?>';
	var roomPictures = [];
	var i = 0;

	if( metaValue ) {
		$('#room-pictures').val( metaValue );
		var jsonValue = $.parseJSON( metaValue );
		$.each( jsonValue, function( i, item ) {
			imgSrc = '<img class="room-pictures" src="' + item.url + '" hspace="2" />';
			imgDiv  = '<div class="room-pictures-container"><span class="close"><a>[x]</a></span><br />';
			imgDiv += imgSrc+'<br />';
			imgDiv += '<span class="caption">'+item.title+'</span></div>';
			roomPictures.push({ 'url': item.url, 'title': item.title, 'ID': item.ID })
			$('#holder-image').append( imgDiv );
		});

	}

	$( '#add-image' ).live( 'click', function() {
		formfield = $('#room_upload_image').attr('name');
		tb_show('', '../wp-admin/media-upload.php?type=image&amp;TB_iframe=true');

		window.send_to_editor = function( html ) {
			console.log( 'rooms' );
			var classes = jQuery('img',html).attr('class').split( ' ' );
			var imgID;
			$.each( classes,
				function( key, val ) {
					if( val.search( 'wp-image-' ) >= 0 ) {
						imgID = +val.replace( 'wp-image-', '' );
					}
				}
			);

		    imgUrl = jQuery('img',html).attr('src');
		    imgTitle = jQuery('img',html).attr('title');
		    imgSrc = '<img class="room-pictures" src="' + imgUrl + '" hspace="2" />';
		    imgDiv  = '<div class="room-pictures-container"><span class="close"><a>[x]</a></span><br />';
		    imgDiv += imgSrc+'<br />';
		    imgDiv += '<span class="caption">'+imgTitle+'</span></div>';
		    roomPictures.push({ 'url': imgUrl, 'title': imgTitle, 'ID': imgID });
			$('#room-pictures').val( JSON.stringify( roomPictures ) );
		    $('#holder-image').append( imgDiv );
		    tb_remove();
		    i++;

		    window.send_to_editor = orig_send_to_editor;

		}

		return false;
	});

	$('.room-pictures-container .close').live('click', function() {

		for( i = 0; i < roomPictures.length; i++ ) {
			if( $(this).siblings( 'img' ).attr( 'src' ) == roomPictures[i].url ) {
				break;
			}
		}
		$(this).parent().remove();
		roomPictures.splice( i, 1 );
		$('#room-pictures').val( JSON.stringify( roomPictures ) );
	});
});
</script>
<input type="hidden" id="room-pictures" name="rooms[pictures]"  value=""/>
<div class="button-container"><a class="add-new-h2" id="add-image" style="cursor: pointer;">Add Pictures</a></div>
<div id="holder-image"></div><br clear="all" />
