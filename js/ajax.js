function smplct() {

	jQuery.ajax({
		url: SimpleAjax.ajaxurl,
		data: { 
			'action': 'simple-ct-ajax',
			'nonce': SimpleAjax.wpnonce 
		},
		dataType: 'JSON',
		success: function( data ) {
	
			if ( data.success ) {
				alert( 'Transients cleared' );
			} else {
				alert( 'Error clearing transients' );
			}		
			
		},
        error: function( errorThrown ) {
            console.log( errorThrown );
        }
	});
	
	return false;
	
}

