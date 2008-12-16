//------------------------------------------
// Invision Power Board v2.1
// ACP Specific JS File
// (c) 2005 Invision Power Services, Inc.
//
// http://www.invisionboard.com
//------------------------------------------

var _this;

/*-------------------------------------------------------------------------*/
// Initiate object
/*-------------------------------------------------------------------------*/

function acpperms()
{
	this.div_wrapper    = document.getElementById('perms-wrapper');
	this.div_content    = document.getElementById('perms-content');
	this.div_drag       = document.getElementById('perms-drag');
	this.div_status     = document.getElementById('perms-status');
	this.div_status_msg = document.getElementById('perms-status-msg');
	this.obj_center     = new center_div();
	this.form_code      = 'section=admin&act=acpperms';
	this.initialized    = 0;
	this.member_id      = 0;
	this.xmlobj         = null;
	this.loading_fired  = 0;
	this.save_cache     = new Array();
	this.perm_main      = '';
	this.perm_child     = '';
	this.reg_tabs       = new Array( 'content', 'lookandfeel', 'tools', 'components', 'admin', 'help' );
}

/*-------------------------------------------------------------------------*/
// Save perm bits
/*-------------------------------------------------------------------------*/

function bbedit_acpperms_save_group( perm_main, perm_child, doreturn ) {}

acpperms.prototype.save_group = function( perm_main, perm_child, member_id, result )
{
	//----------------------------------
	// INIT
	//----------------------------------
	
	this.member_id = member_id;
	
	//----------------------------------
	// Sync...
	//----------------------------------
	
	this.perm_main  = perm_main;
	this.perm_child = perm_child;
	tab             = this.perm_main;
	
	/*--------------------------------------------*/
	// Main function to do on request
	// Must be defined first!! Chill
	/*--------------------------------------------*/
	
	_this = this;
	
	this.do_request_function = function()
	{
		//----------------------------------
		// Ignore unless we're ready to go
		//----------------------------------
		
		if ( ! _this.xmlobj.readystate_ready_and_ok() )
		{
			_this.show_loading( 'Saving...' );
			return;
		}
		
		_this.hide_loading();
		
		//----------------------------------
		// INIT
		//----------------------------------
		
		var html = _this.xmlobj.xmlhandler.responseText;
		
		_this.div_content.innerHTML = html;
		
		//----------------------------------
		// INIT tabs
		//----------------------------------
		
		_this.init_tabs();
	}
	
	this.xmlobj = new ajax_request();
	this.xmlobj.onreadystatechange( this.do_request_function );
	
	//----------------------------------
	// LOAD XML
	//----------------------------------
	
	this.xmlobj.process( ipb_var_base_url + '&' + this.form_code + '&code=acpperms-xml-save-group&member_id=' + this.member_id  + '&perm_child=' + this.perm_child + '&perm_main=' + this.perm_main + '&result=' + result );
}


/*-------------------------------------------------------------------------*/
// Loads and init's display
/*-------------------------------------------------------------------------*/

function bbedit_acpperms_undo_bits() {}

acpperms.prototype.undo_bits = function()
{
	//----------------------------------
	// Loop through save bits cache....
	//----------------------------------
	
	for( var i in this.save_cache )
	{
		var perm_bit   = i;
		var switcheroo = document.getElementById( 'pb_' + perm_bit ).value ? 0 : 1;
		var classname  = switcheroo ? 'perms-red' : 'perms-green';
		
		//----------------------------------
		// Reset....
		//----------------------------------
		
		document.getElementById( 'pb_' + perm_bit ).value = switcheroo;
		
		document.getElementById( 'td_' + perm_bit + '_a' ).className = classname;
		document.getElementById( 'td_' + perm_bit + '_b' ).className = classname;
		document.getElementById( 'td_' + perm_bit + '_c' ).className = classname;
		document.getElementById( 'td_' + perm_bit + '_d' ).className = classname;
		
		//----------------------------------
		// Change img classname
		//----------------------------------
		
		try
		{
			if ( ! switcheroo )
			{
				document.getElementById( 'img-' + perm_bit + '-cross' ).className = 'img-boxed-off';
				document.getElementById( 'img-' + perm_bit + '-tick' ).className  = 'img-boxed';
			}
			else
			{
				document.getElementById( 'img-' + perm_bit + '-cross' ).className = 'img-boxed';
				document.getElementById( 'img-' + perm_bit + '-tick' ).className  = 'img-boxed-off';
			}
		}
		catch(e)
		{
			//alert(e);
		}
	}
	
	//----------------------------------
	// Reset the array....
	//----------------------------------
	
	this.save_cache = new Array();
	
	//----------------------------------
	// Reset save / undo buttons....
	//----------------------------------
	
	document.getElementById( 'perms-save-box' ).className = 'input-ok-content';
	document.getElementById( 'perms-undo-box' ).className = 'input-ok-content';
	
	return false;
}

/*-------------------------------------------------------------------------*/
// Loads and init's display
/*-------------------------------------------------------------------------*/

function bbedit_acpperms_clicked() {}

acpperms.prototype.clicked = function( perm_bit, onoff )
{
	//----------------------------------
	// Function off, but image clicked?
	//----------------------------------
	
	if ( perm_bit != 'mainbit' && ( ! document.getElementById( 'pb_mainbit' ).value || document.getElementById( 'pb_mainbit' ).value == 0 ) )
	{
		alert( "To enabled individual access permissions, please enable 'ALLOW ACCESS TO THIS FUNCTION'" );
		return false;
	}
	
	//----------------------------------
	// Just clicked mainbit?
	//----------------------------------
	
	if ( perm_bit == 'mainbit' )
	{
		this.save_mainbit( onoff );
		return false;
	}
	
	//----------------------------------
	// Add to save_cache
	//----------------------------------
	
	this.save_cache[ perm_bit ] = onoff;
	
	//----------------------------------
	// Change save / undo button img
	//----------------------------------
	
	document.getElementById( 'perms-save-box' ).className = 'input-warn-content';
	document.getElementById( 'perms-undo-box' ).className = 'input-warn-content';
	
	//----------------------------------
	// Add to save_cache
	//----------------------------------
	
	document.getElementById( 'pb_' + perm_bit ).value = onoff;
	
	//----------------------------------
	// Change row colours
	//----------------------------------
	
	var classname = onoff ? 'perms-green' : 'perms-red';
	
	try
	{
		document.getElementById( 'td_' + perm_bit + '_a' ).className = classname;
		document.getElementById( 'td_' + perm_bit + '_b' ).className = classname;
		document.getElementById( 'td_' + perm_bit + '_c' ).className = classname;
		document.getElementById( 'td_' + perm_bit + '_d' ).className = classname;
	}
	catch(e)
	{
		//alert(e);
	}
	
	//----------------------------------
	// Change img classname
	//----------------------------------
	
	try
	{
		if ( onoff )
		{
			document.getElementById( 'img-' + perm_bit + '-cross' ).className = 'img-boxed-off';
			document.getElementById( 'img-' + perm_bit + '-tick' ).className  = 'img-boxed';
		}
		else
		{
			document.getElementById( 'img-' + perm_bit + '-cross' ).className = 'img-boxed';
			document.getElementById( 'img-' + perm_bit + '-tick' ).className  = 'img-boxed-off';
		}
	}
	catch(e)
	{
		//alert(e);
	}
}

/*-------------------------------------------------------------------------*/
// Inits tabs
/*-------------------------------------------------------------------------*/

function bbedit_acpperms_init_tabs() {}

acpperms.prototype.init_tabs = function()
{
	//----------------------------------
	// Change style depending on status
	//----------------------------------
	
	for ( i = 0 ; i < this.reg_tabs.length ; i++ )
	{
		var tab = this.reg_tabs[ i ];
		
		if ( ! document.getElementById( 'tab_' + tab ).value || document.getElementById( 'tab_' + tab ).value == 0 || typeof(document.getElementById( 'tab_' + tab ).value) == 'undefined' )
		{
			// Change text colo(?:u)?r
			document.getElementById( 'href_' + tab ).style.color = '#777';
			
			// Change image too...
			document.getElementById( 'img-' + tab + '-cross' ).className = 'img-boxed';
		}
		else
		{
			// Change text colo(?:u)?r
			document.getElementById( 'href_' + tab ).style.color = '#000';
			
			// Change image too...
			document.getElementById( 'img-' + tab + '-tick' ).className = 'img-boxed';
		}
	}
}

/*-------------------------------------------------------------------------*/
// Save perm bits
/*-------------------------------------------------------------------------*/

function bbedit_acpperms_save_bits() {}

acpperms.prototype.save_bits = function( doreturn )
{
	//----------------------------------
	// If it's the first screen loaded
	// after clicking the tab, the this.perm_child
	// won't have any info.. so grab it from the HTML
	//----------------------------------
	
	this.perm_child = document.getElementById('perms-perm-child-id').value;
	
	//----------------------------------
	// INIT
	//----------------------------------
	
	var urlbits  = new Array();
	var needsave = 0;
	
	for( var i in this.save_cache )
	{
		var perm_bit = i;
		
		if ( perm_bit )
		{
			needsave = 1;
		}
		
		//----------------------------------
		// Add to array
		//----------------------------------
		
		urlbits[ 'pbfs_' + perm_bit ] = this.save_cache[i];
	}
	
	//----------------------------------
	// Need to save?
	//----------------------------------
	
	if ( needsave == 0 )
	{
		return false;
	}
	
	//----------------------------------
	// Show neat little "save.." msg
	// Not always here, so...
	//----------------------------------
	
	try
	{
		document.getElementById( 'perms-save-box' ).className = 'input-ok-content';
		document.getElementById( 'perms-save-box' ).innerHTML = "Auto-saving...";
	}
	catch(e) { }
	
	/*--------------------------------------------*/
	// Main function to do on request
	// Must be defined first!! Chill
	/*--------------------------------------------*/
	
	_this = this;
	
	this.do_request_function = function()
	{
		//----------------------------------
		// Ignore unless we're ready to go
		//----------------------------------
		
		if ( ! _this.xmlobj.readystate_ready_and_ok() )
		{
			_this.show_loading( 'Saving...' );
			return;
		}
		
		_this.hide_loading();
		
		//----------------------------------
		// INIT
		//----------------------------------
		
		var html = _this.xmlobj.xmlhandler.responseText;
	}
	
	this.xmlobj = new ajax_request();
	this.xmlobj.onreadystatechange( this.do_request_function );
	
	//----------------------------------
	// LOAD XML
	//----------------------------------
	
	this.xmlobj.process( ipb_var_base_url + '&' + this.form_code + '&code=acpperms-xml-save-bits&member_id=' + this.member_id
																 + '&perm_child=' + this.perm_child
																 + '&perm_main=' + this.perm_main, 'POST', this.xmlobj.format_for_post( urlbits )  );
	
	this.save_cache = new Array();
	
	if ( doreturn == 1 )
	{
		this.init( this.perm_main, this.member_id, this.perm_child );
	}
}

/*-------------------------------------------------------------------------*/
// Save main bit
/*-------------------------------------------------------------------------*/

function bbedit_acpperms_save_mainbit() {}

acpperms.prototype.save_mainbit = function( result )
{
	//----------------------------------
	// If it's the first screen loaded
	// after clicking the tab, the this.perm_child
	// won't have any info.. so grab it from the HTML
	//----------------------------------
	
	this.perm_child = document.getElementById('perms-perm-child-id').value;
	
	/*--------------------------------------------*/
	// Main function to do on request
	// Must be defined first!! Chill
	/*--------------------------------------------*/
	
	_this = this;
	
	this.do_request_function = function()
	{
		//----------------------------------
		// Ignore unless we're ready to go
		//----------------------------------
		
		if ( ! _this.xmlobj.readystate_ready_and_ok() )
		{
			_this.show_loading( 'Saving...' );
			return;
		}
		
		_this.hide_loading();
		
		//----------------------------------
		// INIT
		//----------------------------------
		
		var html = _this.xmlobj.xmlhandler.responseText;
		
		_this.div_content.innerHTML = html;
		
		//----------------------------------
		// INIT tabs
		//----------------------------------
		
		_this.init_tabs();
	}
	
	this.xmlobj = new ajax_request();
	this.xmlobj.onreadystatechange( this.do_request_function );
	
	//----------------------------------
	// LOAD XML
	//----------------------------------
	
	this.xmlobj.process( ipb_var_base_url + '&' + this.form_code + '&code=acpperms-xml-save-mainbit&member_id=' + this.member_id  + '&perm_child=' + this.perm_child + '&perm_main=' + this.perm_main + '&result=' + result );
}

/*-------------------------------------------------------------------------*/
// Save main tab
/*-------------------------------------------------------------------------*/

function bbedit_acpperms_save_tab() {}

acpperms.prototype.save_tab = function( perm_main, member_id, result )
{
	//----------------------------------
	// INIT
	//----------------------------------
	
	this.member_id = member_id;
	
	//----------------------------------
	// Sync...
	//----------------------------------
	
	this.perm_main = perm_main;
	
	tab            = this.perm_main;
	
	/*--------------------------------------------*/
	// Main function to do on request
	// Must be defined first!! Chill
	/*--------------------------------------------*/
	
	_this = this;
	
	this.do_request_function = function()
	{
		//----------------------------------
		// Ignore unless we're ready to go
		//----------------------------------
		
		if ( ! _this.xmlobj.readystate_ready_and_ok() )
		{
			_this.show_loading( 'Saving...' );
			return;
		}
		
		_this.hide_loading();
		
		//----------------------------------
		// INIT
		//----------------------------------
		
		var html = _this.xmlobj.xmlhandler.responseText;
		
		_this.div_content.innerHTML = html;
		
		//----------------------------------
		// INIT tabs
		//----------------------------------
		
		_this.init_tabs();
	}
	
	this.xmlobj = new ajax_request();
	this.xmlobj.onreadystatechange( this.do_request_function );
	
	//----------------------------------
	// LOAD XML
	//----------------------------------
	
	this.xmlobj.process( ipb_var_base_url + '&' + this.form_code + '&code=acpperms-xml-save-tabs&member_id=' + this.member_id  + '&perm_main=' + this.perm_main + '&result=' + result );
}

/*-------------------------------------------------------------------------*/
// Loads and inits display
/*-------------------------------------------------------------------------*/

function bbedit_acpperms_init() {}

acpperms.prototype.init = function( tab, member_id, perm_child )
{
	//----------------------------------
	// BEFORE WE GO, CHECK FOR SAVE
	// We need to do this now before
	// we reset this.* vars
	//----------------------------------
	
	if ( this.member_id && this.perm_main )
	{
		this.save_bits( 0 );
	}
	
	//----------------------------------
	// INIT
	//----------------------------------
	
	this.member_id = member_id;
	perm_child     = typeof(perm_child) == "undefined" ? "" : perm_child;
	
	//----------------------------------
	// Sync...
	//----------------------------------
	
	this.perm_main  = tab;
	this.perm_child = perm_child;

	//----------------------------------
	// Close menu?
	//----------------------------------
	
	try
	{
		menu_action_close();
	}
	catch(e) { }
	
	/*--------------------------------------------*/
	// Main function to do on request
	// Must be defined first!! Chill
	/*--------------------------------------------*/
	
	_this = this;
	
	this.do_request_function = function()
	{
		//----------------------------------
		// Ignore unless we're ready to go
		//----------------------------------
		
		if ( ! _this.xmlobj.readystate_ready_and_ok() )
		{
			_this.show_loading( 'Loading...' );
			return;
		}
		
		_this.hide_loading();
		
		//----------------------------------
		// INIT
		//----------------------------------
		
		var html = _this.xmlobj.xmlhandler.responseText;
		
		//----------------------------------
		// Stop IE showing select boxes over
		// floating div [ 1 ]
		//----------------------------------
		
		if ( is_ie )
		{
			 html = "<iframe id='perm-shim' src='" + ipb_skin_url + "/iframe.html' class='iframshim' scrolling='no' frameborder='0' style='position:absolute; top:0px; left:0px; display:none;'></iframe>" + html;
		}
		
		_this.div_content.innerHTML = html;
		
		//----------------------------------
		// Stop IE showing select boxes over
		// floating div [ 2 ]
		//----------------------------------
		
		if ( is_ie )
		{
			perm_shim               = document.getElementById('perm-shim');
			perm_shim.style.width   = _this.div_content.offsetWidth;
			perm_shim.style.height  = _this.div_content.offsetHeight;
			perm_shim.style.zIndex  = _this.div_content.style.zIndex - 1;
			perm_shim.style.top     = _this.div_content.style.top;
			perm_shim.style.left    = _this.div_content.style.left;
			perm_shim.style.display = "block";
		}
		
		//----------------------------------
		// INIT tabs
		//----------------------------------
		
		_this.init_tabs();
	}
	
	this.xmlobj = new ajax_request();
	this.xmlobj.onreadystatechange( this.do_request_function );
	
	//----------------------------------
	// LOAD XML
	//----------------------------------
	
	if ( ! tab )
	{
		this.xmlobj.process( ipb_var_base_url + '&' + this.form_code + '&code=acpperms-xml-display&member_id=' + this.member_id + '&perm_child=' + perm_child );
	}
	else
	{
		this.xmlobj.process( ipb_var_base_url + '&' + this.form_code + '&code=acpperms-xml-display&member_id=' + this.member_id  + '&perm_child=' + perm_child + '&tab=' + tab );
	}
  	
  	this.div_wrapper.style.position = 'absolute';
	this.div_wrapper.style.display  = 'block';
	this.div_wrapper.style.zIndex   = 99;
	
	//----------------------------------
	// Not loaded? INIT
	//----------------------------------
	
	if ( ! this.initialized )
	{
		
		this.initialized = 1;
		
		//----------------------------------
		// Figure width and height
		//----------------------------------
		
		var my_width  = 0;
		var my_height = 0;
		
		if ( typeof( window.innerWidth ) == 'number' )
		{
			//----------------------------------
			// Non IE
			//----------------------------------
		  
			my_width  = window.innerWidth;
			my_height = window.innerHeight;
		}
		else if ( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) )
		{
			//----------------------------------
			// IE 6+
			//----------------------------------
			
			my_width  = document.documentElement.clientWidth;
			my_height = document.documentElement.clientHeight;
		}
		else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) )
		{
			//----------------------------------
			// Old IE
			//----------------------------------
			
			my_width  = document.body.clientWidth;
			my_height = document.body.clientHeight;
		}
		
		//----------------------------------
		// Get div height && width
		//----------------------------------
		
		var divheight = parseInt( this.div_wrapper.style.Height );
		var divwidth  = parseInt( this.div_wrapper.style.Width );
		
		divheight = divheight ? divheight : 400;
		divwidth  = divwidth  ? divwidth  : 400;
		
		//----------------------------------
		// Got it stored in a cookie?
		//----------------------------------
		
		var divxy = my_getcookie( 'ipb-perms-div' );
		var co_ords;
		
		if ( divxy && divxy != null )
		{
			co_ords = divxy.split( ',' );
		
			//----------------------------------
			// Got co-ords?
			//----------------------------------
			
			if ( co_ords.length )
			{
				var final_width  = co_ords[0];
				var final_height = co_ords[1];
				
				if ( co_ords[0] > my_width )
				{
					//----------------------------------
					// Keep it on screen
					//----------------------------------
					
					final_width = my_width - divwidth;
				}
				
				if ( co_ords[1] > my_height )
				{
					//----------------------------------
					// Keep it on screen
					//----------------------------------
					
					final_height = my_height - divheight;
				}
				
				this.div_wrapper.style.left = final_width  + 'px';
				this.div_wrapper.style.top  = final_height + 'px';
			}
		}
		else
		{
			//----------------------------------
			// Reposition DIV roughly centered
			//----------------------------------
			
			this.div_wrapper.style.left = my_width  / 2  - (divwidth / 2)  + 'px';
			this.div_wrapper.style.top  = my_height / 2 - (divheight / 2 ) + 'px';
		}
		
		Drag.cookiename = 'ipb-perms-div';
		Drag.init( this.div_drag, this.div_wrapper );
	}
}

/*-------------------------------------------------------------------------*/
// Show message
/*-------------------------------------------------------------------------*/

acpperms.prototype.show_loading = function( status_msg )
{
	if ( ! this.loading_fired )
	{
		this.loading_fired = 1;
		this.div_status_msg.innerHTML = '<div style="width:auto" class="input-warn-content"><strong>' + status_msg + '</strong></strong>';
	}
	
	return;
}

/*--------------------------------------------*/
// Hide message
/*--------------------------------------------*/

acpperms.prototype.hide_loading = function()
{
	this.div_status_msg.innerHTML = '<div style="width:auto" class="input-ok-content">Ready</span>';
	
	this.loading_fired = 0;
	
	return;
}

