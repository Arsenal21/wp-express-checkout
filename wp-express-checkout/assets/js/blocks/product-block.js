var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender = wp.serverSideRender,
	PanelBody = wp.components.PanelBody,
	SelectControl = wp.components.SelectControl,
	ToggleControl = wp.components.ToggleControl,
	InspectorControls = wp.blockEditor.InspectorControls;

registerBlockType( 'wp-express-checkout/product-block', {
	title: wpec_block_prod_str.title,
	icon: 'products',
	category: 'common',

	edit: function( props ) {
		return [
			el( ServerSideRender, {
				block: 'wp-express-checkout/product-block',
				key: 'wpec-serverSideRenderer-key',
				attributes: props.attributes,
			} ),
			el( InspectorControls, {
				key: 'wpec-inspectorControls-key',
			},
				el( PanelBody, {
						title: wpec_block_prod_str.panel,
						key: 'wpec-panelBody-key',
						initialOpen: true
					},
					el( SelectControl, {
						label: wpec_block_prod_str.product,
						value: props.attributes.prod_id,
						options: wpec_prod_opts,
						key: 'wpec-selectControl1-key',
						onChange: ( value ) => {
							props.setAttributes( {
								prod_id: value
							} );
						},
					} ),
					el( SelectControl, {
						label: wpec_block_prod_str.template,
						value: props.attributes.template,
						options: wpec_prod_template_opts,
						key: 'wpec-selectControl2-key',
						onChange: ( value ) => {
							props.setAttributes( {
								template: value
							} );
						},
					} ),
					el( ToggleControl, {
						label: wpec_block_prod_str.modal,
						value: props.attributes.modal,
						key: 'wpec-toggleControl-key',
						onChange: ( value ) => {
							props.setAttributes( {
								modal: value
							} );
						},
						checked: props.attributes.modal,
					} )

				)
			)
		];
	},

	save: function() {
		return null;
	},
} );
