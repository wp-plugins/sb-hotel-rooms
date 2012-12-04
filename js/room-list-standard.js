jQuery(document).ready(function($) {
    $('.room-picture-container a').lightBox({
		imageLoading: lightbox_images.imageLoading,
		imageBtnPrev: lightbox_images.imageBtnPrev,
		imageBtnNext: lightbox_images.imageBtnNext,
		imageBtnClose: lightbox_images.imageBtnClose,
		imageBlank: lightbox_images.imageBlank,
	});    
});