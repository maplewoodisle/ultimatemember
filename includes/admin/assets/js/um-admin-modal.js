var $um_tiny_editor = {};


function um_admin_live_update_scripts( count ) {
	var metakey = jQuery('.um-admin-modal #UM_edit_field #_metakey').val();

	if ( count === 0 ) {
		jQuery('.um_add_field .um-admin-btn-toggle').hide();
	} else if ( metakey && count === 1 ) {
		jQuery('.um_add_field .um-admin-btn-toggle').hide();
	} else {
		jQuery('.um_add_field .um-admin-btn-toggle').show();
	}

	jQuery('.um-adm-conditional').each( function() {
		jQuery(this).trigger('change');
	});

	if ( jQuery('.um-admin-colorpicker').length ) {
		jQuery('.um-admin-colorpicker').wpColorPicker();
	}
}

function um_admin_new_modal( id, ajax, size ) {
	var modal = jQuery('body').find('.um-admin-overlay');

	jQuery('.tipsy').hide();

	um_admin_remove_modal();

	jQuery('body').addClass('um-admin-modal-open').append('<div class="um-admin-overlay" /><div class="um-admin-modal" />');
	jQuery('#' + id).prependTo('.um-admin-modal');
	jQuery('#' + id).show();
	jQuery('.um-admin-modal').show();
	
	jQuery('.um-admin-modal-head').append('<a href="#" data-action="UM_remove_modal" class="um-admin-modal-close"><i class="um-faicon-times"></i></a>');

	if ( ajax == true ) {
		um_admin_modal_size( size );
		um_admin_modal_preload();
		um_admin_modal_responsive();
	} else {
		um_admin_modal_responsive();
	}
}

function um_tinymce_init( id, content ) {
	var object = jQuery('#' + id);

	if ( tinyMCE.get( id ) !== null ) {
		tinyMCE.triggerSave();
		tinyMCE.EditorManager.execCommand( 'mceRemoveEditor', true, id );
		"4" === tinyMCE.majorVersion ? window.tinyMCE.execCommand( "mceRemoveEditor", !0, id ) : window.tinyMCE.execCommand( "mceRemoveControl", !0, id );
		$um_tiny_editor = jQuery('<div>').append( object.parents( '#wp-' + id + '-wrap' ).clone() );
		object.parents('#wp-' + id + '-wrap').replaceWith('<div class="um_tiny_placeholder"></div>');
		jQuery('.um-admin-editor:visible').html( jQuery( $um_tiny_editor ).html() );

		var init;
		if( typeof tinyMCEPreInit.mceInit[ id ] == 'undefined' ){
			init = tinyMCEPreInit.mceInit[ id ] = tinyMCE.extend( {}, tinyMCEPreInit.mceInit[ id ] );
		} else {
			init = tinyMCEPreInit.mceInit[ id ];
		}
		if ( typeof(QTags) == 'function' ) {
			QTags( tinyMCEPreInit.qtInit[ id ] );
			QTags._buttonsInit();
		}
		window.switchEditors.go( id );
		tinyMCE.init( init );
		tinyMCE.get( id ).setContent( content );
		object.html( content );
	} else {
		$um_tiny_editor = jQuery('<div>').append( object.parents('#wp-' + id + '-wrap').clone() );
		object.parents('#wp-' + id + '-wrap').replaceWith('<div class="um_tiny_placeholder"></div>');

		jQuery('.um-admin-editor:visible').html( jQuery( $um_tiny_editor ).html() );

		if ( typeof(QTags) == 'function' ) {
			QTags( tinyMCEPreInit.qtInit[ id ] );
			QTags._buttonsInit();
		}

		//use duplicate because it's new element
		jQuery('#' + id).html( content );
	}

	jQuery( 'body' ).on( 'click', '.wp-switch-editor', function() {
		var target = jQuery(this);

		if ( target.hasClass( 'wp-switch-editor' ) ) {
			var mode = target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
			window.switchEditors.go( id, mode );
		}
	});
}


function um_admin_modal_ajaxcall( act_id, arg1, arg2, arg3 ) {
	var count = jQuery('.um-admin-builder .um-admin-drag-fld').length;
	var in_row = '';
	var in_sub_row = '';
	var in_column = '';
	var in_group = '';
	
	if ( jQuery('.um-col-demon-settings').data('in_column') ) {
		in_row = jQuery('.um-col-demon-settings').data('in_row');
		in_sub_row = jQuery('.um-col-demon-settings').data('in_sub_row');
		in_column = jQuery('.um-col-demon-settings').data('in_column');
		in_group = jQuery('.um-col-demon-settings').data('in_group');
	}

	var form_mode = jQuery('input[type=hidden][id=form__um_mode]').val();
	
	jQuery.ajax({
		url: wp.ajax.settings.url,
		type: 'POST',
		data: {
			action:'um_dynamic_modal_content',
			act_id: act_id,
			arg1 : arg1,
			arg2 : arg2,
			arg3: arg3,
			in_row: in_row,
			in_sub_row: in_sub_row,
			in_column: in_column,
			in_group: in_group,
			nonce: um_admin_scripts.nonce,
			form_mode: form_mode
		},
		complete: function(){
			um_admin_modal_loaded();
			um_admin_modal_responsive();
		},
		success: function(data) {

			jQuery('.um-admin-modal').find('.um-admin-modal-body').html( data );

			um_responsive();
			um_admin_live_update_scripts( count );

			jQuery( "#_custom_dropdown_options_source" ).trigger('blur');

			if ( jQuery('.um-admin-editor:visible').length > 0 ) {

				if ( act_id == 'um_admin_edit_field_popup' ) {
					um_tinymce_init( 'um_editor_edit', jQuery('.um-admin-modal:visible .dynamic-mce-content').html() );
				} else {
					um_tinymce_init( 'um_editor_new', '' );
				}

			}

			um_init_tooltips();

			um_admin_init_datetimepicker();
		},
		error: function(data) {

		}
	});
	return false;
}

function um_admin_modal_responsive() {
	var required_margin = jQuery('.um-admin-modal:visible').innerHeight() / 2 + 'px';
	jQuery('.um-admin-modal:visible').css({'margin-top': '-' + required_margin });
}

function um_admin_remove_modal() {

	if ( jQuery('.um-admin-editor:visible').length > 0 ) {
		tinyMCE.triggerSave();

		if ( jQuery('.um-admin-modal:visible').find('form').parent().attr('id') == 'UM_edit_field' ) {
			jQuery('#wp-um_editor_edit-wrap').remove();

			/*tinyMCE.execCommand('mceRemoveEditor', true, 'um_editor_edit');
			jQuery('.um-hidden-editor-edit').html( jQuery('.um-admin-editor:visible').contents() );
			tinyMCE.execCommand('mceAddEditor', true, 'um_editor_edit');*/
		
		} else {
			jQuery('#wp-um_editor_new-wrap').remove();

			/*tinyMCE.execCommand('mceRemoveEditor', true, 'um_editor_new');
			jQuery('.um-hidden-editor-new').html( jQuery('.um-admin-editor:visible').contents() );
			tinyMCE.execCommand('mceAddEditor', true, 'um_editor_new');*/
		
		}

		jQuery('.um_tiny_placeholder').replaceWith( jQuery( $um_tiny_editor ).html() );
	}

	jQuery('body').removeClass('um-admin-modal-open');
	jQuery('.um-admin-modal div[id^="UM_"]').hide().appendTo('body');
	jQuery('.um-admin-modal,.um-admin-overlay').remove();
}

function um_admin_modal_preload() {
	jQuery('.um-admin-modal:visible').addClass('loading');
	jQuery('.um-admin-modal-body:visible').empty();
}

function um_admin_modal_loaded() {
	jQuery('.um-admin-modal:visible').removeClass('loading');
}

function um_admin_modal_size( aclass ) {
	jQuery('.um-admin-modal:visible').addClass(aclass);
}

function um_admin_modal_add_attr( id, value ) {
	jQuery('.um-admin-modal:visible').data( id, value );
}

/**
	Custom modal scripting starts
**/

jQuery(document).ready(function() {
	
	/**
		disable link
	**/
	jQuery(document.body).on('click', '.um-admin-builder a, .um-admin-modal a', function(e){
		e.preventDefault();
		return false;
	});
	
	/**
		toggle area
	**/
	jQuery(document.body).on('click', '.um-admin-btn-toggle a', function(e){

		var length = jQuery(this).parents('.um-admin-btn-toggle').find('.um-admin-cur-condition').length;

		if ( length === 5 ) {
			jQuery('.um-admin-new-condition').attr( 'disabled', 'disabled' );
		} else {
			jQuery('.um-admin-new-condition').removeAttr('disabled');
		}

		jQuery('.condition-wrap .um-admin-cur-condition').each( function() {
			var cond_operator = jQuery(this).find('[id^="_conditional_operator"]').val();
			var cond_value = jQuery(this).find('[id^="_conditional_value"]');
			if ( cond_operator === 'empty' || cond_operator === 'not empty' ) {
				cond_value.attr( 'disabled', 'disabled' );
			} else {
				cond_value.removeAttr('disabled');
			}
		});

		var content = jQuery(this).parent().find('.um-admin-btn-content');
		var link = jQuery(this);
		if ( content.is(':hidden') ) {
			content.show();
			link.find('i').removeClass().addClass('um-icon-minus');
			link.addClass('active');
		} else {
			content.hide();
			link.find('i').removeClass().addClass('um-icon-plus');
			link.removeClass('active');
		}

		var form = jQuery(this).closest('form');
		if( form.find('.condition-wrap').is(":visible") ){
			var first_el = form.find('.um-condition-group-wrapper:first-child');
			if( first_el.find('select').length === 0 ){
				first_el.next().find('.or-devider').remove();
				first_el.remove();
			}
		}

		um_admin_modal_responsive();
	});


	/**
		check if empty/not empty
	**/
	jQuery( document.body ).on('change', 'select[id^="_conditional_operator"]', function(){
		var cond_operator = jQuery(this).val();
		var cond_value = jQuery(this).closest('.um-admin-cur-condition').find('[id^="_conditional_value"]');
		if( cond_operator === 'empty' || cond_operator === 'not empty' ){
			cond_value.attr( 'disabled', 'disabled' );
		} else {
			cond_value.removeAttr('disabled');
		}
	});


	/**
		clone a condition
	**/
	jQuery(document.body).on('click', '.um-admin-new-condition', function() {
		var form = $(this).closest('form');
		if ( jQuery(this).hasClass('disabled') )
			return false;

		var content = jQuery(this).parents('.um-admin-btn-content'),
			length = content.find('.um-admin-cur-condition').length;

		if ( length < 5 ) {
			var template
			if ( jQuery('#tmpl-um-admin-conditional-add').length > 0 ) {
				template = wp.template('um-admin-conditional-add');
			} else {
				template = wp.template('um-admin-conditional-edit');
			}

			length = length ? length : '';

			var compare = jQuery(this).hasClass('um-admin-new-condition-compare-and') ? 'and' : 'or';

			if ( jQuery(this).hasClass('um-admin-new-condition-compare-and') ) {
				var group = jQuery(this).prev('.um-admin-cur-condition').find('[id^="_conditional_group"]').val();

				if ( jQuery(this).prev('.um-admin-cur-condition').find('[id^="_conditional_group"]').length === 0 ) {
					group = 0;
				}
			} else {
				var group = jQuery('.condition-wrap .um-admin-cur-condition').last().find('[id^="_conditional_group"]').val();
				group = parseInt( group ) + 1;
			}

			var template_content = template({
				item: length,
				compare: compare,
				group: group
			});

			jQuery(this).before( template_content );

			um_admin_live_update_scripts();
			um_admin_modal_responsive();
		}

		if ( length >= 4 ) {
			jQuery('.um-admin-new-condition').attr('disabled', 'disabled');
		} else {
			jQuery('.um-admin-new-condition').removeAttr('disabled');
		}

		//need fields refactor
		um_build_conditions(form);

	});
	
	/**
		reset conditions
	**/
	jQuery(document.body).on('click', '.um-admin-reset-conditions a', function(){
		var content = jQuery(this).parents('.um-admin-btn-content');
		content.find('.um-condition-group-wrapper').slice(1).remove();
		content.find('.um-admin-cur-condition').slice(1).remove();
		content.find('input[type=text]').val('');
		content.find('select').val('');
		jQuery('.um-admin-new-condition').removeAttr('disabled').removeClass('disabled');

		var form = $(this).closest('form');
		um_build_conditions(form);

		um_admin_live_update_scripts();
		um_admin_modal_responsive();
	});
	
	/**
		remove a condition
	**/
	jQuery(document.body).on('click', '.um-admin-remove-condition', function(){
		jQuery('.um-admin-new-condition').removeAttr('disabled').removeClass('disabled');
		jQuery('.tipsy').remove();

		if ( jQuery(this).parents( '.um-condition-group-wrapper' ).find( '.um-admin-cur-condition' ).length === 1 ) {
			//remove the last condition in group
			jQuery(this).parents( '.um-condition-group-wrapper' ).remove();
		} else {
			jQuery(this).parents( '.um-admin-cur-condition' ).remove();
		}

        //need fields refactor
		var form = $(this).closest('form');
		um_build_conditions(form);

		um_admin_live_update_scripts();
		um_admin_modal_responsive();
	});
	
	/**
		remove modal via action
	**/
	jQuery(document.body).on('click', '.um-admin-overlay, a[data-action="UM_remove_modal"]', function(){
		um_admin_remove_modal();
	});
	
	/**
		fire new modal
	**/
	jQuery(document.body).on('click', 'a[data-modal^="UM_"], span[data-modal^="UM_"]', function(e){
		e.preventDefault();

		var modal_id = jQuery(this).attr('data-modal');

		if ( jQuery(this).attr('data-back') ) {
		
			jQuery('#UM_fonticons').find('a.um-admin-modal-back').attr("data-modal", jQuery(this).attr('data-back') );
			var current_icon = jQuery( '#' + jQuery(this).attr('data-back') ).find('input#_icon').val();
			if ( current_icon == '' ) {
				jQuery('#UM_fonticons').find('.um-admin-icons span').removeClass('highlighted');
			}
		
		}
		
		if ( jQuery(this).data('dynamic-content') ) {
			um_admin_new_modal( modal_id, true, jQuery(this).data('modal-size') );
			um_admin_modal_ajaxcall( jQuery(this).data('dynamic-content'), jQuery(this).data('arg1'), jQuery(this).data('arg2'), jQuery(this).data('arg3') );
		} else {
			um_admin_new_modal( modal_id );
		}
		
		return false;

	});
	
	/**
		choose font icon
	**/
	jQuery(document.body).on('click', '.um-admin-icons span', function(){
		var icon = jQuery(this).attr('data-code');
		jQuery(this).parent().find('span').removeClass('highlighted');
		jQuery(this).addClass('highlighted');
		jQuery('#UM_fonticons').find('a.um-admin-modal-back').attr("data-code", icon);
	});
	
	/**
		submit font icon
	**/
	jQuery(document.body).on('click', '#UM_fonticons a.um-admin-modal-back:not(.um-admin-modal-cancel)', function(){
		var v_id = '';
		var icon_selected = jQuery(this).attr('data-code');
		if (icon_selected != ''){
			if ( jQuery(this).attr('data-modal') ) {
				v_id = '#' + jQuery(this).attr('data-modal');
			} else {
				v_id = '.postbox';
			}
			jQuery( v_id ).find('input#_icon,input#_um_icon,input#notice__um_icon').val( icon_selected );
			jQuery( v_id ).find('span.um-admin-icon-value').html('<i class="'+icon_selected+'"></i>');
			jQuery( v_id ).find('.um-admin-icon-clear').show();
		}
		jQuery(this).attr('data-code', '');
		if ( v_id == '.postbox' ) {
			um_admin_remove_modal();
		}
	});
	
	/**
		restore font icon
	**/
	jQuery(document.body).on('click', 'span.um-admin-icon-clear', function(){
		var element = jQuery(this).parents('p');
		jQuery('#UM_fonticons a.um-admin-modal-back').attr('data-code', '');
		element.find('input[type=hidden]').val('');
		element.find('.um-admin-icon-value').html('No Icon');

		element = jQuery(this).parents('td');
		element.find('input[type=hidden]').val('');
		element.find('.um-admin-icon-value').html('No Icon');
		jQuery(this).hide();
	});
	
	/**
		search font icons
	**/
	jQuery(document.body).on('keyup blur', '#_icon_search', function(){
		if ( jQuery(this).val().toLowerCase() != '' ) {
			jQuery('.um-admin-icons span').hide();
			jQuery('.um-admin-icons span[data-code*="'+jQuery(this).val().toLowerCase()+'"]').show();
		} else {
			jQuery('.um-admin-icons span:hidden').show();
		}
		um_admin_modal_responsive();
	});

	
	/**
	 * Retrieve options from a callback function
	 */
	jQuery(document.body).on('blur',"#_custom_dropdown_options_source", function(){
        var me = jQuery(this);
        var _options = jQuery('textarea[id=_options]');
        
        if( me.val() != '' ){
        	var um_option_callback = me.val();
          	jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				data: {
					action:'um_populate_dropdown_options',
					um_option_callback: um_option_callback,
					nonce: um_admin_scripts.nonce
				},
				complete: function(){
					
				},
				success: function( response ){
					var arr_opts = [];
					
					for (var key in response.data ){
                         arr_opts.push( response.data[ key ] );
					}

					_options.val( arr_opts.join('\n') );
					
		        }
			});
		}

	});

}); // end jQuery(document).ready

