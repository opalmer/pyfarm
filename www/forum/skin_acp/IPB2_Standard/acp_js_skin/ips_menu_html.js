/*-------------------------------------------------------------------------*/
// IPS BASIC MENU CLASS (EXTENSION: BUILD SIMPLE MENU)
// (c) 2005 Invision Power Services, Inc
// Assumes that "ips_menu.js" has been loaded
/*-------------------------------------------------------------------------*/

//----------------------------------
// INIT some CSS classes
//----------------------------------

var css_mainwrap     = 'popupmenu';
var css_menusep      = 'menusep';
var css_menusep_last = 'menuseplast';

//----------------------------------
// INIT some images
//----------------------------------

var img_add       = "<img src='" + ipb_skin_url + "/images/icons_menu/add.gif' border='0' alt='V' class='ipd' />&nbsp;";
var img_edit      = "<img src='" + ipb_skin_url + "/images/icons_menu/edit.gif' border='0' alt='V' class='ipd' />&nbsp;";
var img_view      = "<img src='" + ipb_skin_url + "/images/icons_menu/view.gif' border='0' alt='V' class='ipd' />&nbsp;";
var img_delete    = "<img src='" + ipb_skin_url + "/images/icons_menu/delete.gif' border='0' alt='V' class='ipd' />&nbsp;";
var img_info      = "<img src='" + ipb_skin_url + "/images/icons_menu/info.gif' border='0' alt='V' class='ipd' />&nbsp;";
var img_action    = "<img src='" + ipb_skin_url + "/images/icons_menu/manage.gif' border='0' alt='V' class='ipd' />&nbsp;";
var img_item      = "<img src='" + ipb_skin_url + "/images/icons_menu/manage.gif' border='0' alt='V' class='ipd' />&nbsp;";
var img_newfolder = "<img src='" + ipb_skin_url + "/images/icons_menu/new-folder.gif' border='0' alt='V' class='ipd' />&nbsp;";
var img_password  = "<img src='" + ipb_skin_url + "/images/icons_menu/password.gif' border='0' alt='V' class='ipd' />&nbsp;";
var img_export    = "<img src='" + ipb_skin_url + "/images/icons_menu/export.png' border='0' alt='V' class='ipd' />&nbsp;";

/*-------------------------------------------------------------------------*/
// Return formed image
/*-------------------------------------------------------------------------*/

function make_image( img )
{
	return "<img src='" + ipb_skin_url + "/" + img + "' border='0' alt='-' class='ipd' />";
}

/*-------------------------------------------------------------------------*/
// menu_build_menu
// cid: ID of opener object (img, div, etc)
// menuinput: Array of menu entries | Variable of menu HTML
// complexmenu: Treat as HTML stream if true, else treat as array of HTML
/*-------------------------------------------------------------------------*/

function menu_build_menu(cid, menuinput, complexmenu)
{
	var html = "\n<div class='" + css_mainwrap + "' id='" + cid + "_menu' style='display:none;'>\n";
	
	if ( ! complexmenu )
	{
		len = parseInt(menuinput.length);
		
		if ( len > 0 )
		{
			for( var i in menuinput )
			{
				t = parseInt(i) + 1;
				
				thisclass = ( t == len ) ? css_menusep_last : css_menusep;
				
				if ( menuinput[i].match( /^--NODIV--/ ) )
				{
					html += menuinput[i].replace( /^--NODIV--/, '' );
				}
				else
				{
					html += "<div class='" + thisclass + "'>\n" + menuinput[i] + "\n</div>\n";
				}
			}
		}
	}
	else
	{
		html += menuinput;
	}
	
	html += "\n</div>\n";
	
	//----------------------------------
	// Workaround for IE bug which shows
	// select boxes and other windows GUI
	// over divs. Write iframe
	//----------------------------------
	
	if ( is_ie )
	{
		html += "\n"+'<iframe id="if_' + cid + '" src="' + ipb_skin_url + '/iframe.html" scrolling="no" frameborder="0" style="position:absolute;top:0px;left:0px;display:none;"></iframe>'+"\n";
	}
	
	//----------------------------------
	// Write the html
	//----------------------------------
	
	if ( html != '' )
	{
		document.getElementById( cid ).parentNode.innerHTML += html;		
	}
		
	//ipsmenu.dynamic_html += html;
	ipsmenu.dynamic_register[ ipsmenu.dynamic_register.length + 1 ]  = cid;
}



