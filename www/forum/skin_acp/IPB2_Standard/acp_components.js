//------------------------------------------
// Invision Power Board v2.1
// ACP Specific JS File
// (c) 2005 Invision Power Services, Inc.
//
// http://www.invisionboard.com
//------------------------------------------


/*-------------------------------------------------------------------------*/
// Initiate object
/*-------------------------------------------------------------------------*/

function components()
{
	this.mainbox = null;
}

/*-------------------------------------------------------------------------*/
// Init
/*-------------------------------------------------------------------------*/

function bbedit_components_init() {}

components.prototype.init = function()
{
	this.mainbox = document.getElementById('components-menu-box');
	this.draw_menu();
}

/*-------------------------------------------------------------------------*/
// Draw main menu box
/*-------------------------------------------------------------------------*/

function bbedit_components_draw_menu() {}

components.prototype.draw_menu = function()
{
	//-------------------------------
	// INIT
	//-------------------------------
	
	var html  = '';
	var qhtml = '';
	
	//-------------------------------
	// Draw all current menu items
	//-------------------------------
	
	for ( var i in menu_text )
	{
		var menu_box;
		
		//-------------------------------
		// MENU TEXT
		//-------------------------------
		
		menu_box  = lang_build_string( html_box_text, i,  _make_form_safe( menu_text[i] ) );
		
		//-------------------------------
		// MENU URL
		//-------------------------------
		
		menu_box += lang_build_string( html_box_url, i,  _make_form_safe( menu_url[i] ) );
		
		//-------------------------------
		// MENU REDIRECT
		//-------------------------------
		
		menu_box += lang_build_string( html_box_redirect, i,  menu_redirect[i] );
		
		//-------------------------------
		// MENU PERMBIT
		//-------------------------------
		
		menu_box += lang_build_string( html_box_permbit, i,  _make_form_safe( menu_permbit[i] ) );
		
		//-------------------------------
		// MENU LANG
		//-------------------------------
		
		menu_box += lang_build_string( html_box_permlang, i,  _make_form_safe( menu_permlang[i] ) );
		
		//-------------------------------
		// Add...
		//-------------------------------
		
		html  += lang_build_string( html_menu_wrap, menu_box, i );
	}
		
	//-------------------------------
	// Add on "More..."
	//-------------------------------
	
	html += html_add_menu_row;
	
	//-------------------------------
	// Add wrapped question w/choice
	//-------------------------------
	
	this.mainbox.innerHTML = html;
	
	//-------------------------------
	// Correct check boxes
	//-------------------------------
	
	for ( var i in menu_redirect )
	{
		if ( parseInt(menu_redirect[i]) == 1 )
		{
			document.getElementById( 'menu_redirect_' + i ).checked = true;
		}
		else
		{
			document.getElementById( 'menu_redirect_' + i ).checked = false;
		}
	}
}


/*-------------------------------------------------------------------------*/
// Add menu row
/*-------------------------------------------------------------------------*/

function bbedit_components_add_menu_row() {}

components.prototype.add_menu_row = function()
{
	var maxid = 0;
	
	for ( var i in menu_text )
	{
		if ( i > maxid )
		{
			maxid = i;
		}
	}
	
	maxid = parseInt(maxid);
	maxid = parseInt( maxid + 1 );
	
	this.update_form_array();
	
	menu_text[ maxid ]     = '';
	menu_url[ maxid ]      = '';
	menu_redirect[ maxid ] = 0;
	menu_permbit[ maxid ]  = '';
	menu_permlang[ maxid ] = '';
	
	this.draw_menu();
	
	return false;
}

/*-------------------------------------------------------------------------*/
// Remove menu row
/*-------------------------------------------------------------------------*/

function bbedit_components_remove_menu_row() {}

components.prototype.remove_menu_row = function( id )
{
	if ( confirm( "Are you sure you wish to remove this menu row?" ) )
	{
		//-------------------------------
		// Delete All bits
		//-------------------------------
		
		delete( menu_text[ id ] );
		delete( menu_url[ id ] );
		delete( menu_redirect[ id ] );
		delete( menu_permbit[ id ] );
		delete( menu_permlang[ id ] );
		
		this.update_form_array();
		this.draw_menu();
	}
	
	return false;
}

/*-------------------------------------------------------------------------*/
// Update form array
/*-------------------------------------------------------------------------*/

function bbedit_components_update_form_array() {}

components.prototype.update_form_array = function()
{
	//-------------------------------
	// Update menu array with form
	//-------------------------------
	
	var tmp_menu_text     = {};
	var tmp_menu_url      = {};
	var tmp_menu_redirect = {};
	var tmp_menu_permbit  = {};
	var tmp_menu_permlang = {};
	
	for ( var i in menu_text )
	{
		//-------------------------------
		// Get form value...
		//-------------------------------
		
		try
		{
			tmp_menu_text[ i ] = document.getElementById( 'menu_text_' + i ).value;
		}
		catch(e) { }
	}
	
	for ( var i in menu_url )
	{
		//-------------------------------
		// Get form value...
		//-------------------------------
		
		try
		{
			tmp_menu_url[ i ] = document.getElementById( 'menu_url_' + i ).value;
		}
		catch(e) { }
	}
	
	for ( var i in menu_redirect )
	{
		//-------------------------------
		// Get form value...
		//-------------------------------
		
		try
		{
			tmp_menu_redirect[ i ] = document.getElementById( 'menu_redirect_' + i ).checked ? 1 : 0;
		}
		catch(e) { }
	}
	
	for ( var i in menu_permbit )
	{
		//-------------------------------
		// Get form value...
		//-------------------------------
		
		try
		{
			tmp_menu_permbit[ i ] = document.getElementById( 'menu_permbit_' + i ).value;
		}
		catch(e) { }
	}
	
	for ( var i in menu_permlang )
	{
		//-------------------------------
		// Get form value...
		//-------------------------------
		
		try
		{
			tmp_menu_permlang[ i ] = document.getElementById( 'menu_permlang_' + i ).value;
		}
		catch(e) { }
	}
	
	//-------------------------------
	// Update
	//-------------------------------
	
	menu_text      = tmp_menu_text;
	menu_url       = tmp_menu_url;
	menu_redirect  = tmp_menu_redirect;
	menu_permbit   = tmp_menu_permbit;
	menu_permlang  = tmp_menu_permlang;
}

/*--------------------------------------------*/
// FORM MAKE SAFE
/*--------------------------------------------*/

function _make_form_safe( t )
{
	if ( ! t )
	{
		return t;
	}
	
	t = t.replace( /'/g, '&#039;' );
	t = t.replace( /"/g, '&quot;' );
	
	return t;
}