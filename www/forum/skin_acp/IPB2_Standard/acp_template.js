//------------------------------------------
// Invision Power Board v2.1
// ACP Specific JS File
// (c) 2005 Invision Power Services, Inc.
//
// http://www.invisionboard.com
//------------------------------------------

var div_template_sections;
var div_template_bits;
var div_template_edit;
var iframe_template_bits;
var iframe_template_edit;
var template_section_anim;
var template_column_anim;
var template_section_fwidth       = 50;
var template_section_fheight      = 475;
var template_section_mheight      = 250;
var template_edit_mheight         = 400;
var template_edit_mheight_mpl     = 360;
var template_edit_mheight_mpl_pad = 65;
var template_section_moved        = 0;
var template_columns_moved        = 0;
var main_height;
var template_group_loaded;
var template_search_input_obj	  = '';

var template_bit_loaded    = new Array();

var template_anim_done = 0;

var class_before      = 'tablerow1';
var class_after       = 'tablerow3';

var editor_contents   = new Array();
var editor_unload_set = 0;
var editor_form_id    = 'template-bits-form';

var iframes_loaded    = new Array();

var search_group_name;
var search_template_bit;

var ie_rs_count    = 0;
var cur_tid_loaded = 0;

var bits_clicked   = new Array();

/*-------------------------------------------------------------------------*/
// Template View Diff
/*-------------------------------------------------------------------------*/

function template_view_diff( tid )
{
	try
	{
		menu_action_close();
	}
	catch(e) { }
	
	pop_win( 'section=lookandfeel&act=skindiff&code=skin_diff_view_diff&diff_key=' + tid, 'DiffWindow'+tid.replace( /:/g, '_' ), 700, 500 );
	
	return false;
}

/*-------------------------------------------------------------------------*/
// Template INIT
/*-------------------------------------------------------------------------*/

function template_init()
{
	div_template_sections     = document.getElementById('template-sections');
	div_template_bits         = document.getElementById('template-bits');
	div_template_edit         = document.getElementById('template-edit');
	iframe_template_bits      = document.getElementById('tb-iframe');
	iframe_template_edit      = document.getElementById('te-iframe');
	template_search_input_obj = document.getElementById('entered_template');
	
	main_height = parseInt( div_template_sections.style.height );
	
	//------------------------------------------
	// Use quick search?
	//------------------------------------------
	
	if ( ! use_enhanced_js )
	{
		try
		{
			document.getElementById( 'quick-search-box' ).innerHTML = '&nbsp;';
		}
		catch(e){  }
	}
	
	//------------------------------------------
	// Add key listener to capture enter
	//------------------------------------------
	
	if ( use_enhanced_js )
	{
		try
		{
			template_search_input_obj.onkeypress = function(e)
			{
				e = e ? e : window.event;
				switch (e.keyCode)
				{
					case 13:
						template_find_bits(e);
						return false;
				}
			}
		}
		catch(e){  }
	}
	
	//------------------------------------------
	// IE FIX... grrr
	//------------------------------------------
	
	if ( is_ie && !is_ie7 )
	{
		//div_template_sections.style.position = 'absolute';
		document.getElementById('template-main-wrap').style.height = parseInt( div_template_sections.style.height ) + 'px';
	}
	
	//------------------------------------------
	// Searching?
	//------------------------------------------
	
	if ( template_search )
	{
		//------------------------------------------
		// Hide rows we don't have matches for
		//------------------------------------------
		
		var groups = document.getElementsByTagName('DIV');

		for ( var i = 0 ; i <= groups.length ; i++ )
		{
			var e = groups[i];
			
			if ( e && e.id )
			{
				var id    = e.id;
				var gname = id.replace( /^dv-(.+?)$/, "$1" );
				
				if ( gname != id && ! search_template_sections[ gname ] )
				{
					e.style.display = 'none';
				}
			}
		}
		
		//------------------------------------------
		// Add in matches
		//------------------------------------------
		
		try
		{
			for ( var i in search_template_matches )
			{
				document.getElementById( 'match-'+i ).innerHTML = '(' + search_template_matches[i] + ' ' + lang_matches + ')';
			}
		}
		catch(e)
		{
		}
	}
}

/*-------------------------------------------------------------------------*/
// Onload events
/*-------------------------------------------------------------------------*/

function template_iframe_loaded( framekey )
{
	if ( framekey == 'tb' )
	{
		if( is_ie )
		{
			var frame_loaded = document.getElementById('template-bits').style.display;
			
			if( frame_loaded == 'none' )
			{
				return true;
			}
		}
		
		//------------------------------------------
		// Add onclick events to revert images
		//------------------------------------------

		var images = window.frames['tb-iframe'].document.getElementsByTagName('img');
		
		//----------------------------------
		// Sort through and grab topic links
		//----------------------------------
	
		for ( var i = 0 ; i <= images.length ; i++ )
		{
			try
			{
				if ( ! images[i].id )
				{
					continue;
				}
			}
			catch(e)
			{
				continue;
			}
			
			var imgid   = images[i].id;
			var imgname = imgid.replace( /^(.*)-(\d+)$/, "$1" );
			
			if ( imgname == 'img-remove' )
			{
				images[i].onclick = template_revert_clicked;
			}
		}
	}
}

/*-------------------------------------------------------------------------*/
// Find template bits
/*-------------------------------------------------------------------------*/

function template_find_bits(event)
{
	//------------------------------------------
	// INIT
	//------------------------------------------
	
	var search_box = document.getElementById( 'entered_template' );
	
	if ( ! search_box.value )
	{
		return false;
	}
	
	//------------------------------------------
	// Get template bit name and stuff
	//------------------------------------------
	
	search_group_name   = search_box.value.replace( /.*\((.+?)\)/, "$1" );
	search_template_bit = search_box.value.replace( /(.*)\(.+?\)/, "$1" );
	
	//------------------------------------------
	// Strip whitespace
	//------------------------------------------
	
	search_group_name   = search_group_name.replace( /^\s*|\s*$/g  , "" );
	search_template_bit = search_template_bit.replace( /^\s*|\s*$/g, "" );
	
	//------------------------------------------
	// Load group name
	//------------------------------------------
	
	try
	{
		if ( search_group_name )
		{
			if ( search_template_bit )
			{
				if ( is_ie )
				{
					iframe_template_bits.onreadystatechange = _template_load_editor_loader;
				}
				else
				{
					iframe_template_bits.onload = _template_load_editor_loader;
				}
			}
			
			template_load_bits( search_group_name, event, false );
			
		}
	}
	catch(e)
	{
		alert( "Could not locate a template bit called: " + search_box.value + "\nPlease search in the format:\ntemplate_name (template_group)" );
	}
}

/*-------------------------------------------------------------------------*/
// Restore template bit
/*-------------------------------------------------------------------------*/

function template_bit_restore( tid )
{
	//------------------------------------------
	// INIT
	//------------------------------------------
	
	var edit_box_obj    = window.frames['te-iframe'].document.getElementById( 't'+tid );
	var edit_box_button = window.frames['te-iframe'].document.getElementById( 'sb-t' + tid );
	
	edit_box_button.className = 'realdarkbutton';
	
	if ( editor_contents[ 't'+tid ] )
	{
		if ( confirm("Are you sure you want to restore the template?\nALL UNSAVED CHANGES WILL BE LOST!") )
		{
			edit_box_obj.value     = editor_contents[ 't'+tid ];
			editor_contents[ 't'+tid ] = '';
		}
	}
}

/*-------------------------------------------------------------------------*/
// Close down template edit screen
/*-------------------------------------------------------------------------*/

function template_edit_close( tid )
{
	//------------------------------------------
	// Check
	//------------------------------------------
	
	if ( editor_contents[ 't'+tid ] )
	{
		if ( ! confirm('WARNING: CLICK "OK" TO LOSE ANY UNSAVED CHANGES' ) )
		{
			return false;
		}
	}
	
	//------------------------------------------
	// Close
	//------------------------------------------
	
	editor_contents[ 't'+tid ]      = '';
	div_template_edit.style.display = 'none';
	iframe_template_edit.src        = ipb_skin_url + '/iframe.html';
}
		
/*-------------------------------------------------------------------------*/
// Editor has changed
/*-------------------------------------------------------------------------*/

function template_bit_changed( tid )
{
	//------------------------------------------
	// Already changed?
	//------------------------------------------
	
	var edit_box_obj    = window.frames['te-iframe'].document.getElementById( tid );
	var edit_box_button = window.frames['te-iframe'].document.getElementById( 'sb-' + tid );
	
	if ( edit_box_button.className == 'realredbutton' )
	{
		return;
	}
	
	//------------------------------------------
	// Store current tid loaded
	//------------------------------------------
	
	cur_tid_loaded = tid.replace( /t(\d+)/, "$1" );
	
	//------------------------------------------
	// Change flag
	//------------------------------------------
	
	window.frames['te-iframe'].document.getElementById( 'edited-' + cur_tid_loaded ).value = 1;
	
	//------------------------------------------
	// No?
	//------------------------------------------
	
	editor_contents[ tid ] = edit_box_obj.value;
	
	edit_box_button.className = 'realredbutton';
	editor_unload_set         = 1;
	
	//------------------------------------------
	// Set onsubmit event
	//------------------------------------------
	
	window.frames['te-iframe'].document.getElementById( editor_form_id ).onsubmit = template_edit_onsubmit;
}

/*-------------------------------------------------------------------------*/
// On submit, reload to iframe
/*-------------------------------------------------------------------------*/

function template_edit_onsubmit( event )
{
	editor_unload_set = 0;

	//------------------------------------------
	// Change row image
	//------------------------------------------
	
	if ( cur_tid_loaded > 0 )
	{
		_template_change_state_image(  cur_tid_loaded, 'altered' );
		_template_change_revert_image( cur_tid_loaded, img_revert_real );
	}
	
	return true;
}
	
/*-------------------------------------------------------------------------*/
// EDITOR LOADED: On load, reload to selected bit
/*-------------------------------------------------------------------------*/

function template_bit_onload()
{
	if ( template_bit_loaded.length ) 
	{
		for ( var i in template_bit_loaded )
		{
			window.frames['tb-iframe'].document.getElementById( template_bit_loaded[i] ).className = class_after;
		}
	}

	return true;
}

/*-------------------------------------------------------------------------*/
// TEMPLATE BIT NAMES LOADED: On load chop out non search results
/*-------------------------------------------------------------------------*/

function template_bits_onload()
{
	//------------------------------------------
	// Searching?
	//------------------------------------------
	
	var maingroup = template_group_loaded.replace( /^dv-/, '' );
	
	if ( parent.template_search )
	{
		//------------------------------------------
		// Hide rows we don't have matches for
		//------------------------------------------
		
		var groups = window.frames['tb-iframe'].document.getElementsByTagName('DIV');

		for ( var i = 0 ; i <= groups.length ; i++ )
		{
			var e = groups[i];
			
			if ( e && e.id )
			{
				var id    = e.id;
				var gname = id.replace( /^dvb-(.+?)$/, "$1" );
				
				if ( gname != id && ! search_template_bits[ maingroup + '_' + gname ] )
				{
					e.style.display = 'none';
				}
			}
		}
	}
	return true;
}

/*-------------------------------------------------------------------------*/
// Onclick handler: Revert clicked
/*-------------------------------------------------------------------------*/

function template_revert_clicked(event)
{
	event = template_cancel_bubble( event, true );
		
	if ( this.id )
	{
		var clickedid = this.id.replace( /^.*-(\d+)$/, "$1" );
	}
	
	if ( clickedid )
	{
		var clicked_href = window.frames['tb-iframe'].document.getElementById('link-remove-'+clickedid).href;
		var finalhref    = clicked_href.replace( /javascript:confirm_action\('(.+?)'\)/, "$1" );
		var bitname      = clicked_href.replace( /.*bitname=(.+?)&.*/  , "$1" );
		var custombit    = clicked_href.replace( /.*custombit=(.+?)/, "$1" );
		
		if ( confirm( "WARNING: You cannot undo this action!\nPress OK to continue or CANCEL to cancel this action..." ) )
		{
			if ( use_enhanced_js && parseInt(custombit) != 1 )
			{
				/*--------------------------------------------*/
				// Main function to do on request
				// Must be defined first!!
				/*--------------------------------------------*/
				
				do_request_function = function()
				{
					//----------------------------------
					// Ignore unless we're ready to go
					//----------------------------------
					
					if ( ! xmlobj.readystate_ready_and_ok() )
					{
						return;
					}
		
					//----------------------------------
					// Change icons
					//----------------------------------
					
					_template_change_state_image(  clickedid, 'unaltered' );
					_template_change_revert_image( clickedid, img_revert_blank );
		
					//----------------------------------
					// Re-load editor frame
					//----------------------------------
					
					if ( template_bit_loaded.length ) 
					{
						for ( var i in template_bit_loaded )
						{
							if ( template_bit_loaded[i] && template_bit_loaded[i].replace('dvb-', '' ) == bitname )
							{
								 iframe_template_edit.src = iframe_template_edit.src;
							}
						}
					}
				}
				
				//----------------------------------
				// LOAD XML
				//----------------------------------
				
				xmlobj = new ajax_request();
				xmlobj.onreadystatechange( do_request_function );
	
				xmlobj.process( finalhref.replace( /type=frame/, 'type=xml' ) );
			}
			else
			{
				iframe_template_bits.src = finalhref;
			}
		}
	}
	
	return false;
}

/*-------------------------------------------------------------------------*/
// Close template bit window
/*-------------------------------------------------------------------------*/

function template_close_bits()
{
	//------------------------------------------
	// Resize other div and iframe
	//------------------------------------------
	
	div_template_bits.style.width      = '0%';
	div_template_bits.style.display    = 'none';
	
	iframe_template_bits.style.width   = '0%';
	iframe_template_bits.style.display = 'none';
	
	//------------------------------------------
	// Change row color
	//------------------------------------------
	
	if ( template_group_loaded )
	{
		document.getElementById( template_group_loaded ).className = class_before;
	}
	
	//------------------------------------------
	// Close editor?
	//------------------------------------------
	
	if ( template_bit_loaded.length )
	{
		div_template_edit.style.display = 'none';
		iframe_template_edit.src        = ipb_skin_url + '/iframe.html';
	}
	
	//------------------------------------------
	// Do anim
	//------------------------------------------
	
	template_section_moved = 0;
	template_sections_expand();	
}

/*-------------------------------------------------------------------------*/
// Load template bits iframe
/*-------------------------------------------------------------------------*/

function template_add_bit( add_url, event )
{
	//------------------------------------------
	// INIT
	//------------------------------------------
	
	var url_to_load = add_url;
	var newheight   = 220;
	var newpadding  = 0;
	
	//------------------------------------------
	// Got an event? From a link, then
	//------------------------------------------
	
	if ( event )
	{
		template_cancel_bubble( event, true, window.frames['tb-iframe'] );
	}
	
	//------------------------------------------
	// Unsaved changes?
	//------------------------------------------
	
	if ( editor_unload_set )
	{
		if ( ! confirm('WARNING: CLICK "OK" TO LOSE ANY UNSAVED CHANGES' ) )
		{
			return false;
		}
	}
	
	editor_contents   = new Array();
	editor_unload_set = 0;
	
	//------------------------------------------
	// Resize other div and iframe
	//------------------------------------------
	
	div_template_edit.style.width   = '100%';
	div_template_edit.style.height  = newheight + newpadding + 'px';
	div_template_edit.style.display = 'block';
	
	iframe_template_edit.style.width   = '100%';
	iframe_template_edit.style.height  = newheight + newpadding + 'px';
	iframe_template_edit.scrolling     = 'auto';
	iframe_template_edit.frameborder   = 'no';
	iframe_template_edit.style.display = 'block';
	
	//------------------------------------------
	// Load bits
	//------------------------------------------
	
	iframe_template_edit.src = url_to_load;
}

/*-------------------------------------------------------------------------*/
// Load template bits iframe
/*-------------------------------------------------------------------------*/

function template_load_editor( bit_name, event, bit_url )
{
	//------------------------------------------
	// INIT
	//------------------------------------------
	
	var count           = 1;
	
	//------------------------------------------
	// Loading from a clicked link
	//------------------------------------------
		
	if ( ! bit_url )
	{
		var bn_object   = window.frames['tb-iframe'].document.getElementById( 'bn-'+bit_name );
		var url_to_load = bn_object.href;
		var newheight   = template_edit_mheight;
		var newpadding  = 0;
	}
	
	//------------------------------------------
	// Loading from a URL (multiple bits
	//------------------------------------------
		
	else
	{
		var url_to_load = bit_url;
		var newheight   = template_edit_mheight_mpl;
		var newpadding  = template_edit_mheight_mpl_pad;
	}
	
	//------------------------------------------
	// Got an event? From a link, then
	//------------------------------------------
	
	if ( event )
	{
		template_cancel_bubble( event, true, window.frames['tb-iframe'] );
	}
	
	//------------------------------------------
	// Unsaved changes?
	//------------------------------------------
	
	if ( editor_unload_set )
	{
		if ( ! confirm('WARNING: CLICK "OK" TO LOSE ANY UNSAVED CHANGES' ) )
		{
			return false;
		}
	}
	
	editor_contents   = new Array();
	editor_unload_set = 0;
	
	//------------------------------------------
	// Change row color
	//------------------------------------------
	
	if ( template_bit_loaded.length )
	{
		try
		{
			for ( var i in template_bit_loaded )
			{
				// Change any coloured rows back
				window.frames['tb-iframe'].document.getElementById( template_bit_loaded[i] ).className = class_before;
			}
		}
		catch(e)
		{
		}
	}
	
	//------------------------------------------
	// Multiple bits?
	//------------------------------------------
	
	if ( bit_url )
	{
		try
		{
			for( var i in bits_clicked )
			{
				count++;
			
				window.frames['tb-iframe'].document.getElementById( 'dvb-'+i ).className = class_after;
	
				template_bit_loaded[ template_bit_loaded.length + 1 ] = 'dvb-'+i;
			}
		}
		catch(e)
		{
		}
		
		count--;
	}
	
	//------------------------------------------
	// Single bits
	//------------------------------------------
	
	else
	{
		template_bit_loaded = new Array();
		
		window.frames['tb-iframe'].document.getElementById( 'dvb-'+bit_name ).className = class_after;
	
		template_bit_loaded[ template_bit_loaded.length + 1 ] = 'dvb-'+bit_name;
	}
	
	//------------------------------------------
	// Resize other div and iframe
	//------------------------------------------
	
	div_template_edit.style.width   = '100%';
	div_template_edit.style.height  = newheight * count + newpadding + 'px';
	div_template_edit.style.display = 'block';
	
	iframe_template_edit.style.width   = '100%';
	iframe_template_edit.style.height  = newheight * count + newpadding + 'px';
	iframe_template_edit.scrolling     = 'auto';
	iframe_template_edit.frameborder   = 'no';
	iframe_template_edit.style.display = 'block';
	
	//------------------------------------------
	// Load bits
	//------------------------------------------
	
	iframe_template_edit.src = url_to_load;
	
	//------------------------------------------
	// Scroll up..
	//------------------------------------------
	
	var _reach_the_stars  = _get_obj_toppos( div_template_edit );
	
	if ( _reach_the_stars )
	{
		scroll( 0, _reach_the_stars );
	}
}

/*-------------------------------------------------------------------------*/
// Load template bits iframe
/*-------------------------------------------------------------------------*/

function template_load_bits( group_name, event, nocancel )
{
	//------------------------------------------
	// INIT
	//------------------------------------------
	
	var gn_object   = document.getElementById( 'gn-'+group_name );
	var url_to_load = gn_object.href;
	
	//------------------------------------------
	// Don't cancel if we're reloading
	//------------------------------------------
	
	if ( nocancel != true )
	{
		template_cancel_bubble( event, true );
	}
	
	//------------------------------------------
	// Change row color
	//------------------------------------------
	
	if ( template_group_loaded )
	{
		// Change any coloured rows back
		document.getElementById( template_group_loaded ).className = class_before;
		template_cancel_bubble( event, true );
	}
	
	document.getElementById( 'dv-'+group_name ).className = class_after;
	
	template_group_loaded = 'dv-'+group_name;
	
	//------------------------------------------
	// Do anim
	//------------------------------------------
	
	if ( ! template_section_moved )
	{
		template_section_moved = 1;
		template_sections_contract();
	}
	
	//------------------------------------------
	// Resize other div and iframe
	//------------------------------------------

	div_template_bits.style.width   = 100 - template_section_fwidth + '%';
	div_template_bits.style.height  = main_height + 'px';
	div_template_bits.style.display = '';
	
	//------------------------------------------
	// Load bits
	//------------------------------------------
	
	iframe_template_bits.src           = url_to_load;
	iframe_template_bits.style.height  = main_height + 'px';
	iframe_template_bits.scrolling     = 'auto';
	iframe_template_bits.frameborder   = 'no';
	iframe_template_bits.style.display = '';
	iframe_template_bits.style.width   = '100%';
}

/*-------------------------------------------------------------------------*/
// Anim: Move section to the left
/*-------------------------------------------------------------------------*/

function template_sections_contract()
{
	var width     = parseInt( div_template_sections.style.width ) ? parseInt( div_template_sections.style.width ) : 100;
	var new_width = width - 10;
	
	clearTimeout( template_section_anim );
	
	if ( new_width < template_section_fwidth )
	{
		new_width = template_section_fwidth;
	}
	
	div_template_sections.style.width = new_width + '%';
	
	//------------------------------------------
	// Still got some more to go?
	//------------------------------------------
	
	if ( new_width > template_section_fwidth )
	{
		template_anim_done    = 0;
		template_section_anim = setTimeout( 'template_sections_contract()', 5 );
	}
	else
	{
		template_anim_done = 1;
		bits_clicked = new Array();
	}
}

/*-------------------------------------------------------------------------*/
// Anim: Move section to the left
/*-------------------------------------------------------------------------*/

function template_sections_expand()
{
	var width     = parseInt( div_template_sections.style.width ) ? parseInt( div_template_sections.style.width ) : template_section_fwidth;
	var new_width = width + 10;
	
	clearTimeout( template_section_anim );
	
	if ( new_width > 100 )
	{
		new_width = 100;
	}
	
	div_template_sections.style.width = new_width + '%';
	
	//------------------------------------------
	// Still got some more to go?
	//------------------------------------------
	
	if ( new_width < 100 )
	{
		template_section_anim = setTimeout( 'template_sections_expand()', 5 );
	}
}

/*-------------------------------------------------------------------------*/
// Prevent browser bubblin'
/*-------------------------------------------------------------------------*/

function template_cancel_bubble(obj, extra, wobj)
{
	if ( ! obj || is_ie)
	{
		if ( ! wobj )
		{
			wobj = window;
		}
		
		try
		{
			if ( extra )
			{
				wobj.event.returnValue = false;
			}
			
			wobj.event.cancelBubble = true;
			
			return wobj.event;
		}
		catch(e) { }
	}
	else
	{
		obj.stopPropagation();
		
		if ( extra )
		{
			obj.preventDefault();
		}
		
		return obj;
	}
}

/*-------------------------------------------------------------------------*/
// Load template bits for editing
/*-------------------------------------------------------------------------*/

function template_load_bits_to_edit( baseurl )
{
	var bits = '';
	
	//------------------------------------------
	// Build template bits to edit
	//------------------------------------------
	
	for ( var i in bits_clicked )
	{
		if ( (bits_clicked[i] != "") && (bits_clicked[i] != null) && (bits_clicked != 'undefined') )
		{
			bits += i + '|';
		}
	}
	
	//------------------------------------------
	// Got owt?
	//------------------------------------------
	
	if ( ! bits || bits == '' )
	{ 
		return false;
	}
	
	template_load_editor( null, null, baseurl+'&bitname=' + bits );
}

/*-------------------------------------------------------------------------*/
// Jump handler
/*-------------------------------------------------------------------------*/

function _template_load_editor_loader( event )
{
	if ( is_ie )
	{
		// OMG. Could not get readyState == 4 to work o_O
		// "complete" and COMPLETE didn't work either
		// So I resorted to this hack. Too tired to see
		// what's really up
		
		ie_rs_count++;
		
		if ( ie_rs_count < 3 )
		{
			return;
		}
		
		ie_rs_count = 0;
	}
	
	if ( search_template_bit )
	{
		template_load_editor( search_template_bit, event );
	}
}

/*-------------------------------------------------------------------------*/
// Toggle bit row
/*-------------------------------------------------------------------------*/

function template_toggle_bit_row( bit_name )
{
	//------------------------------------------
	// Already selected?: YES
	//------------------------------------------
	
	if ( bits_clicked[ bit_name ] )
	{
		window.frames['tb-iframe'].document.getElementById( 'dvb-'+bit_name ).className = class_before;
		delete( bits_clicked[ bit_name ] );
	}
	
	//------------------------------------------
	// Already selected?: NO
	//------------------------------------------
	
	else
	{
		window.frames['tb-iframe'].document.getElementById( 'dvb-'+bit_name ).className = class_after;
		bits_clicked[ bit_name ] = 1;
	}
}

/*-------------------------------------------------------------------------*/
// Change state image
/*-------------------------------------------------------------------------*/

function _template_change_state_image( tid, img )
{
	try
	{
		window.frames['tb-iframe'].document.getElementById( 'img-item-' + tid ).src = document.getElementById( 'img-' + img ).src;
	}
	catch( e ) { }
}

/*-------------------------------------------------------------------------*/
// Change revert image
/*-------------------------------------------------------------------------*/

function _template_change_revert_image( tid, img )
{
	try
	{
		window.frames['tb-iframe'].document.getElementById( 'img-remove-' + tid ).src          = img;
		window.frames['tb-iframe'].document.getElementById( 'img-remove-' + tid ).style.width  = img == img_revert_blank ? 1 : img_revert_width  + 'px';
		window.frames['tb-iframe'].document.getElementById( 'img-remove-' + tid ).style.height = img == img_revert_blank ? 1 : img_revert_height + 'px';
	}
	catch( e ) { }
}