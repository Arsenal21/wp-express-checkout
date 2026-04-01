var wpec_element = wp.element.createElement,
	wpec_registerBlockType = wp.blocks.registerBlockType,
	wpec_serverSideRender = wp.serverSideRender,
	wpec_panelBody = wp.components.PanelBody,
	wpec_selectControl = wp.components.SelectControl,
	wpec_toggleControl = wp.components.ToggleControl,
	wpec_inspectorControls = wp.blockEditor.InspectorControls,
	wpec_useBlockProps = wp.blockEditor.useBlockProps
	;

wpec_registerBlockType( 'wp-express-checkout/product-block', {
	apiVersion: 3,
	title: wpec_block_prod_str.title,
	icon: 'products',
	category: 'common',

	edit: function( props ) {
		const blockProps = wpec_useBlockProps();

		return [
			wpec_element( 'div', blockProps,
				wpec_element( wpec_serverSideRender, {
					block: 'wp-express-checkout/product-block',
					key: 'wpec-serverSideRenderer-key',
					attributes: props.attributes,
				} ),
			),

			wpec_element( wpec_inspectorControls, {
				key: 'wpec-inspectorControls-key',
			},
				wpec_element( wpec_panelBody, {
						title: wpec_block_prod_str.panel,
						key: 'wpec-panelBody-key',
						initialOpen: true
					},
					wpec_element( wpec_selectControl, {
						label: wpec_block_prod_str.product,
						value: props.attributes.prod_id,
						options: wpec_prod_opts,
						key: 'wpec-selectControl1-key',
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						onChange: ( value ) => {
							props.setAttributes( {
								prod_id: value
							} );
						},
					} ),
					wpec_element( wpec_selectControl, {
						label: wpec_block_prod_str.template,
						value: props.attributes.template,
						options: wpec_prod_template_opts,
						key: 'wpec-selectControl2-key',
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						onChange: ( value ) => {
							props.setAttributes( {
								template: value
							} );
						},
					} ),
					wpec_element( wpec_toggleControl, {
						label: wpec_block_prod_str.modal,
						value: props.attributes.modal,
						key: 'wpec-toggleControl-key',
						__nextHasNoMarginBottom: true,
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
