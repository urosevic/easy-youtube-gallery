jQuery(document).ready(function($){
	function eytg_init_MPAU() {
		$('.eytg-lightbox-items .eytg-item').magnificPopupAU({
			disableOn:320,
			type:'iframe',
			removalDelay:160,
			preloader:false,
			fixedContentPos:false,
			mainClass:'eytg-mfp-lightbox',
		});
	}
	jQuery(window).on('load',function(){
		eytg_init_MPAU();
	});
	jQuery(document).ajaxComplete(function(){
		eytg_init_MPAU();
	});

	$('.eytg-wall-items .eytg-item').on('click', function(ev){
		ev.preventDefault();
		var video_id = $(this).data('eytg_video_id');
		var controls = $(this).data('eytg_controls');
		var playsinline = $(this).data('eytg_playsinline');
		var privacy = '';
		if ( $(this).data('eytg_privacy') ) {
			privacy = '-nocookie';
		}
		$(this).addClass('active').siblings().removeClass('active');
		$(this).parent().siblings('.eytg-wall').html( '<iframe width="560" height="315" src="https://www.youtube' + privacy + '.com/embed/' + video_id + '?rel=0&modestbranding=0&autoplay=1&controls=' + controls + '&playsinline=' + playsinline + '" frameborder="0" allowfullscreen></iframe>' );
	});
});