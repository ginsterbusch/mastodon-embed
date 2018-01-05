/**
 * A few small helpers for Mastodon Embed Improved
 */
 
jQuery( function() {
	//.parents('.detailed-status').find('.e-content')
	var strCWElement = '.status__content__spoiler-link',
		iShowDelay = 300; // ms
	
	if( jQuery( strCWElement ).length > 0 ) {
		jQuery( strCWElement ).on('click', function(e) {
			e.preventDefault();
			
			var hiddenContent = jQuery(this).parents('.detailed-status').find('.e-content');
			
			//console.log( hiddenContent );
			
			// toggle
			if( hiddenContent.length > 0 ) {
				/**
				 * NOTE: Uses JS animation to be able to change it via (future) settings
				 */
				( jQuery( hiddenContent ).css( 'display' ) == 'none' || jQuery( hiddenContent ).css('height' ) == 0 ? jQuery( hiddenContent ).slideDown( iShowDelay ) : jQuery( hiddenContent ).slideUp( iShowDelay ) );
				
				//jQuery( hiddenContent ).hasClass('show-nsfw') ? jQuery( hiddenContent ).removeClass('show-nsfw') : jQuery( hiddenContent ).addClass('show-nsfw');
			}
			
			/*
			if( jQuery( hiddenContent ).css('display') == 'none' || jQuery( hiddenContent ).css('height') == 0 ) {
				jQuery( hiddenContent ).slideDown( iShowDelay );
			} else {
				jQuery( hiddenContent ).slideUp( iShowDelay );
			}*/
			
		});
	}
	
	
} );
