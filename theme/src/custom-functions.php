<?php

/**
 * Keep ACF blocks on Block API v3 and enable automatic inline editing.
 *
 * Applying this at metadata level means every ACF block registered from
 * block.json inherits the modern editor behaviour without repeating the
 * settings in each individual block definition.
 */
add_filter( 'block_type_metadata', function( $metadata ) {
	$name = $metadata['name'] ?? '';

	if ( 0 !== strpos( $name, 'acf/' ) ) {
		return $metadata;
	}

	if ( empty( $metadata['acf'] ) || ! is_array( $metadata['acf'] ) ) {
		$metadata['acf'] = array();
	}

	$metadata['acf']['blockVersion']      = 3;
	$metadata['acf']['autoInlineEditing'] = true;

	return $metadata;
} );
