//------------------------------------------------------------------------------
// IPS Cross-Browser Global Text Editor HTML
//------------------------------------------------------------------------------
// Supports all "v5" browsers (inc. Opera, Safari, etc)
// Used for non RTE applications.
// (c) 2005 Invision Power Services, Inc.
// http://www.invisionpower.com
//------------------------------------------------------------------------------


/**
* Rich Text Editor HTML Functions
*/
function rte_html_functions()
{
	/**
	* Main control bar object
	* @var	object	Main control bar object
	*/
	this.control_obj       = false;
	/**
	* Editor ID
	*/
	this.editor_id         = false;
	/**
	* Current open bar object
	*/
	this.current_bar_object    = null;
	
	/**
	* Main init function
	* Nothing much to do
	*/
	this.init = function()
	{
	}
	
	/**
	* Show new control bar
	*
	* @param	type	Type of control bar (must be same as function key)
	*/
	this.show_control_bar = function( type )
	{
		if ( ! this.control_obj )
		{
			return;
		}
				
		//-----------------------------------------
		// Already open? Kill it
		//-----------------------------------------
		
		if ( this.current_bar_object )
		{
			this.remove_control_bar();
		}
		
		var mainbar  = document.getElementById( 'rte-main-bar');
		var addtobar = document.getElementById( 'rte-hidden-items' );
		
		//-----------------------------------------
		// Spawn new DIV object
		//-----------------------------------------
		
		var newdiv = document.createElement('div');
		
		newdiv.id             = this.editor_id + '_htmlblock_' + type + '_menu';
		newdiv.style.display  = '';
		newdiv.className      = 'rte-buttonbar';
		newdiv.style.zIndex   = parseInt( this.control_obj.style.zIndex ) + 1;
		newdiv.style.position = 'absolute';
		newdiv.style.width    = '220px';
		newdiv.style.height   = '400px';
		newdiv.style.top      = ipsclass.get_obj_toppos(  mainbar ) + 'px';
		newdiv.style.left     = ipsclass.get_obj_leftpos( mainbar ) - ( parseInt(newdiv.style.width) + 10 ) + 'px';
		
		var tmpheight 		  = parseInt(newdiv.style.height) - 15;
		newdiv.innerHTML      = this.wrap_html_panel( "<iframe id='"+ this.editor_id + '_iframeblock_' + type + '_menu' + "' src='"+global_rte_includes_url + "insert_" + type + ".php?editorid="+this.editor_id+"' style='background-color:transparent;border:0px;overflow:auto;width:99%;height:" + tmpheight + "px'></iframe>" );
		
		//-----------------------------------------
		// Add and show
		//-----------------------------------------
		
		addtobar.appendChild(newdiv);
		
		ipsclass.set_unselectable( newdiv );
		
		//-----------------------------------------
		// INIT Drag
		//-----------------------------------------
		
		var phdl  = document.getElementById('rte-pallete-handle');
		
		Drag.init( phdl, newdiv );
		
		this.current_bar_object = newdiv;
	}
	
	/**
	* Remove control bar
	* Removes the control bar.
	*/
	this.remove_control_bar = function()
	{
		if ( ! this.current_bar_object )
		{
			return;
		}
		
		var addtobar = document.getElementById( 'rte-hidden-items' );
		
		//-----------------------------------------
		// Kill old bar
		//-----------------------------------------
		
		addtobar.removeChild( this.current_bar_object );
		
		//-----------------------------------------
		// Reset bar object
		//-----------------------------------------
		
		this.current_bar_object = null;
	}
	
	/**
	* Wrap HTML block with basic control panel stuff
	*/
	this.wrap_html_panel = function( html )
	{
		var newhtml = "";
		newhtml    += " <div id='rte-pallete-wrap'>";
		newhtml    += "   <div id='rte-pallete-main'>";
		newhtml    += "    <div class='rte-cb-bg' id='rte-pallete-handle'>";
		newhtml    += "			<div align='left'><img id='rte-cb-close-window' src='"+global_rte_images_url+"rte-cb-close.gif' alt='' class='ipd' border='0' /></div>";
		newhtml    += "	   </div>";
		newhtml    += "    <div>" + html + "</div>";
		newhtml    += "  </div>";
		newhtml    += " </div>";
		
		return newhtml;
	}
}