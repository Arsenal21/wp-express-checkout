jQuery( document ).ready( function( $ ) {
	var wpecVariationsGroups = wpecEditProdData.varGroups;
	var wpecVariationsNames = wpecEditProdData.varNames;
	var wpecVariationsPrices = wpecEditProdData.varPrices;
	var wpecVariationsUrls = wpecEditProdData.varUrls;
	var wpecVariationsOpts = wpecEditProdData.varOpts;
	var wpecVariationsGroupsId = 0;

	function wpec_create_variations_group( wpecGroupId, groupName, focus ) {
		$( 'span.wpec-variations-no-variations-msg' ).hide();
		var tpl_html = $( 'div.wpec-html-tpl-variations-group' ).html();
		tpl_html = $.parseHTML( tpl_html );
		$( tpl_html ).find( 'input.wpec-variations-group-name' ).attr( 'name', 'wpec-variations-group-names[' + wpecGroupId + ']' );
		$( tpl_html ).find( 'input.wpec-variations-group-name' ).val( groupName );
		var displayType = 0;
		if ( typeof wpecVariationsOpts[ wpecGroupId ] !== 'undefined' ) {
			displayType = wpecVariationsOpts[ wpecGroupId ];
		}
		$( tpl_html ).find( 'select.wpec-variations-display-type' ).attr( 'name', 'wpec-variations-opts[' + wpecGroupId + ']' );
		$( tpl_html ).find( 'select.wpec-variations-display-type' ).val( displayType );
		$( tpl_html ).closest( 'div.wpec-variations-group-cont' ).attr( 'data-wpec-group-id', wpecGroupId );
		$( 'div#wpec-variations-cont' ).append( tpl_html );
		if ( focus ) {
			wpec_add_variation( wpecGroupId, '', 0, '', false );
			$( tpl_html ).find( 'input.wpec-variations-group-name' ).focus();
		}
	}

	function wpec_add_variation( wpecGroupId, variationName, variationPrice, variationUrl, focus ) {
		var tpl_html = $( 'table.wpec-html-tpl-variation-row tbody' ).html();
		tpl_html = $.parseHTML( tpl_html );
		$( tpl_html ).find( 'input.wpec-variation-name' ).attr( 'name', 'wpec-variation-names[' + wpecGroupId + '][]' );
		$( tpl_html ).find( 'input.wpec-variation-name' ).val( variationName );
		$( tpl_html ).find( 'input.wpec-variation-price' ).attr( 'name', 'wpec-variation-prices[' + wpecGroupId + '][]' );
		$( tpl_html ).find( 'input.wpec-variation-price' ).val( variationPrice );
		$( tpl_html ).find( 'input.wpec-variation-url' ).attr( 'name', 'wpec-variation-urls[' + wpecGroupId + '][]' );
		$( tpl_html ).find( 'input.wpec-variation-url' ).val( variationUrl );
		$( 'div.wpec-variations-group-cont[data-wpec-group-id="' + wpecGroupId + '"]' ).find( 'table.wpec-variations-tbl' ).append( tpl_html );
		if ( focus ) {
			$( tpl_html ).find( 'input.wpec-variation-name' ).focus();
		}
	}
	$( 'button#wpec-create-variations-group-btn' ).click( function( e ) {
		e.preventDefault();
		wpec_create_variations_group( wpecVariationsGroupsId, '', true );
		wpecVariationsGroupsId++;
	} );
	$( document ).on( 'click', 'button.wpec-variations-delete-group-btn', function( e ) {
		e.preventDefault();
		if ( !confirm( wpecEditProdData.str.groupDeleteConfirm ) ) {
			return false;
		}
		$( this ).closest( 'div.wpec-variations-group-cont' ).remove();
		if ( $( 'div.wpec-variations-group-cont' ).length <= 1 ) {
			$( 'span.wpec-variations-no-variations-msg' ).show();
		}
	} );
	$( document ).on( 'click', 'button.wpec-variations-delete-variation-btn', function( e ) {
		e.preventDefault();
		if ( !confirm( wpecEditProdData.str.varDeleteConfirm ) ) {
			return false;
		}
		$( this ).closest( 'tr' ).remove();
	} );
	$( document ).on( 'click', 'button.wpec-variations-add-variation-btn', function( e ) {
		e.preventDefault();
		var wpecGroupId = $( this ).closest( 'div.wpec-variations-group-cont' ).data( 'wpec-group-id' );
		wpec_add_variation( wpecGroupId, '', 0, '', true );
	} );
	$( document ).on( 'click', 'button.wpec-variations-select-from-ml-btn', function( e ) {
		e.preventDefault();
		var wpec_selectVarFile = wp.media( {
			title: 'Select File',
			button: {
				text: 'Insert'
			},
			multiple: false
		} );
		var buttonEl = $( this );
		wpec_selectVarFile.open();
		wpec_selectVarFile.on( 'select', function() {
			var attachment_var = wpec_selectVarFile.state().get( 'selection' ).first().toJSON();
			$( buttonEl ).closest( 'tr' ).children().find( 'input.wpec-variation-url' ).val( attachment_var.url );
		} );
		return false;
	} );
	if ( wpecVariationsGroups.length !== 0 ) {
		$.each( wpecVariationsGroups, function( index, item ) {
			wpecVariationsGroupsId = index;
			wpec_create_variations_group( index, item, false );
			if ( wpecVariationsNames !== null ) {
				$.each( wpecVariationsNames[ index ], function( index, item ) {
					wpec_add_variation( wpecVariationsGroupsId, item, wpecVariationsPrices[ wpecVariationsGroupsId ][ index ], wpecVariationsUrls[ wpecVariationsGroupsId ][ index ], false );
				} );
			}
		} );
		wpecVariationsGroupsId++;
	}

	$( 'input[name="ppec_product_price"]' ).on( 'change', function( e ) {
		$( 'input[name="wpec_product_hide_amount_input"]' ).prop( 'disabled', !( $( this ).val() == 0 ) );
	} );
	$( 'input[name="ppec_product_price"]' ).trigger( 'change' );
} );
