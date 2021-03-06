<?php

/**
 * TODO: Do this only when loading the Gutenberg page
 *
 * Register the gutenberg custom templates
 */
function register_gutenberg_custom_templates() {
	$templates = get_posts( array( 'post_type' => 'gcf-template') );

	$collection = array();
	foreach ( $templates as $template ) {
		$post_type = get_post_type_object( get_post_meta( $template->ID, 'post_type', true ) );
		if ( $post_type ) {
			// Computing the template.
			$gutenberg_template = array();
			$fields_config = json_decode( get_post_meta( $template->ID, 'fields', true ) );
			foreach( $fields_config as $field_config ) {
				$gutenberg_template[] = array(
					sprintf( 'gcf/gcf-%s', $field_config->id )
				);
				register_meta( 'post', $field_config->name, array(
					'show_in_rest' => true,
					'single' => true,
					'type' => 'string',
				) );
			}
			$post_type->template = $gutenberg_template;

			// Computing the lock config.
			$lock = get_post_meta( $template->ID, 'lock', true );
			if ( $lock && $lock !== 'none' ) {
				$post_type->template_lock = $lock;
			}
		}
	}
}
add_action( 'init', 'register_gutenberg_custom_templates' );

/**
 * TODO: Limit to the currently used template
 *
 * Register the private blocks used in gutenberg custom templates
 */
function register_gutenberg_custom_templates_blocks() {
	$templates = get_posts( array( 'post_type' => 'gcf-template') );
	$fields = array();
	foreach( $templates as $template ) {
		$fields_config = json_decode( get_post_meta( $template->ID, 'fields', true ) );
		$fields = array_merge($fields, $fields_config);
	}

	wp_register_script(
		'gcf-blocks',
		gutenberg_custom_fields_url( 'scripts/blocks/build/index.js' ),
		array( 'wp-element', 'wp-blocks', 'wp-components', 'wp-utils', 'wp-date' ),
		filemtime( gutenberg_custom_fields_dir_path() . 'scripts/blocks/build/index.js' ),
		true
	);
	wp_register_style(
		'gcf-blocks',
		gutenberg_custom_fields_url( 'scripts/blocks/build/style.css' ),
		array( 'wp-components' ),
		filemtime( gutenberg_custom_fields_dir_path() . 'scripts/blocks/build/style.css' )
	);

	wp_enqueue_script( 'gcf-blocks' );
	wp_add_inline_script( 'gcf-blocks', sprintf(
		'gcf.blocks.registerBlocksForFields(%s)',
		json_encode($fields)
	) );
	wp_enqueue_style( 'gcf-blocks' );

	// Ensures the `wp-editor` loads after the template blocks are registered
	global $wp_scripts;
	$script = $wp_scripts->query( 'wp-editor', 'registered' );
	$script->deps[] = 'gcf-blocks';
}

add_action( 'enqueue_block_editor_assets', 'register_gutenberg_custom_templates_blocks' );