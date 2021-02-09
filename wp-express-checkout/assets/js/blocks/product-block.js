var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender = wp.components.ServerSideRender,
	SelectControl = wp.components.SelectControl,
	InspectorControls = wp.editor.InspectorControls;

registerBlockType( 'wp-express-checkout/product-block', {
	title: wpec_block_prod_str.title,
	icon: 'products',
	category: 'common',

	edit: function( props ) {
		return [
			el( ServerSideRender, {
				block: 'wp-express-checkout/product-block',
				attributes: props.attributes,
			} ),
			el( InspectorControls, {},
				el( SelectControl, {
					label: wpec_block_prod_str.product,
					value: props.attributes.prod_id,
					options: wpec_prod_opts,
					onChange: ( value ) => {
						props.setAttributes( {
							prod_id: value
						} );
					},
				} )
			),
			el( InspectorControls, {},
				el( SelectControl, {
					label: wpec_block_prod_str.template,
					value: props.attributes.template,
					options: wpec_prod_template_opts,
					onChange: ( value ) => {
						props.setAttributes( {
							template: value
						} );
					},
				} )
			),
		];
	},

	save: function() {
		return null;
	},
} );
