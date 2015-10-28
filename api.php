<?php
function cpac_get_acf_field( $key ) {

	if ( function_exists( 'acf_get_field' ) ) {
		return acf_get_field( $key );
	}
	else if ( function_exists( 'get_field_object' ) ) {
		return get_field_object( $key );
	}

	return false;
}
