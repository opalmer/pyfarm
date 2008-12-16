<?php

class cp_skin_forums {

var $ipsclass;


//===========================================================================
// Forum: Header
//===========================================================================
function forum_wrapper($content, $r, $reorder, $show_buttons=1, $show_reorder=0) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>
EOF;
if ( $show_buttons )
{
$IPBHTML .= <<<EOF
<table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
  <td align='left' width='95%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;' title='ID: {$r['id']}'>{$r['name']}</td>
  <td align='right' width='5%' nowrap='nowrap'>
   $reorder
   <img id="menum-{$r['id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /> &nbsp;
 </td>
 </tr>
</table>
EOF;
}
else if ( $show_reorder )
{
$IPBHTML .= <<<EOF
<table cellpadding='0' cellspacing='0' border='0' width='100%'>
<tr>
 <td align='left' width='40%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;' title='ID: {$r['id']}'>{$r['name']}</td>
 <td align='right' width='60%'>
 <input type='button' value='Re-order Children' class='realdarkbutton' onclick='locationjump( "&{$this->ipsclass->form_code}&code=reorder&f={$r['id']}&sub=1" )' />
 <img id="menum-{$r['id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /> &nbsp;
</td>
</tr>
</table>
EOF;
}
else
{
$IPBHTML .= <<<EOF
{$r['name']}
EOF;
}
$IPBHTML .= <<<EOF
 </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 $content
 </table>
</div>
<script type="text/javascript">
  menu_build_menu(
  "menum-{$r['id']}",
  new Array( img_add    + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=new&p={$r['id']}'>New Forum...</a>",
  			 img_edit   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=edit&f={$r['id']}'>Edit Settings...</a>",
  			 img_delete + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=delete&f={$r['id']}'>Delete Category...</a>",
  			 img_view   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=skinedit&f={$r['id']}'>Skin Options...</a>"
		    ) );
 </script>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forum: Render REORDER row
//===========================================================================
function render_reorder_row( $r, $reorder="", $depth_guide="" ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1' width='10%'>
   {$reorder}
 </td>
 <td class='tablerow1' align='left' width='90%'>
  {$depth_guide} {$r['name']}
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forum: Render forum_footer
//===========================================================================
function render_forum_header() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
//<![CDATA[
var modshown = 0;

function toggle_mod_settings()
{
	var divs = document.getElementsByTagName('div');
	
	for ( var i = 0 ; i <= divs.length ; i++ )
	{
		var e = divs[i];
		
		if ( e && e.id )
		{
			var name     = e.id;
			var mainname = name.replace( /^(.+?)-.+?$/  , "$1" );
		
			if ( mainname == 'moddiv' )
			{
				e.style.display = modshown ? 'none' : '';
			}
		}
	}
	
	document.getElementById( 'togglemod' ).innerHTML = modshown ? 'Show Moderator Options' : 'Hide Moderator Options';
	modshown = modshown == 1 ? 0 : 1;
}
//]]>
</script>
<div class='taboff'>
EOF;
if ( $this->ipsclass->input['showall'] )
{
$IPBHTML .= <<<EOF
<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&showall=0'>Show Tieried</a>
EOF;
}
else
{
$IPBHTML .= <<<EOF
<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&showall=1'>Show All</a>
EOF;
}
$IPBHTML .= <<<EOF
</div>
<div class='taboff'><a href='#' onclick='toggle_mod_settings()' id='togglemod'>Show Moderator Options</a></div>
<br clear='all' />
<div id='mainforumwrapper'>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forum: Render forum_footer
//===========================================================================
function render_moderator_entry( $data='' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tablerow1' style='white-space:nowrap;font-weight:bold;float:left' id='modmenu{$data['mid']}'>{$data['_fullname']} <img src='{$this->ipsclass->skin_acp_url}/images/icon_open.gif' border='0' style='vertical-align:top'/></div>
<script type="text/javascript">
  menu_build_menu(
  "modmenu{$data['mid']}",
  new Array( img_item   + " <a href='{$this->ipsclass->base_url}&section=content&amp;act=mod&code=remove&mid={$data['mid']}'>Remove...</a>",
  			 img_item   + " <a href='{$this->ipsclass->base_url}&section=content&amp;act=mod&code=edit&mid={$data['mid']}'>Edit...</a>"
		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forum: Render forum_footer
//===========================================================================
function render_forum_footer( $choose="", $mem_group ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
</div>
<script type='text/javascript'>
//<![CDATA[
function gochildrenofthecorn()
{
   var chosenroot = document.forms[0].roots.options[document.forms[0].roots.selectedIndex].value;
   
   self.location.href = '{$this->ipsclass->base_url}&{$this->ipsclass->form_code_js}&code=reorder&f=' + chosenroot;
}

function gomodform()
{
	var checkboxes = document.getElementsByTagName('input');
	
	document.getElementById('modforumids').value = '';
	
	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var cb = checkboxes[i];
		
		if ( cb && cb.type == 'checkbox' && cb.checked == true )  
		{
			var name     = cb.id;
			var mainname = name.replace( /^(.+?)_.+?$/  , "$1" );
			var idname   = name.replace( /^(.+?)_(.+?)$/, "$2" );
			
			if ( mainname == 'id' )
			{
				document.getElementById('modforumids').value += ',' + idname;
			}
		}
	}
	
	document.getElementById('modform').submit();
}
//]]>
</script>
<div class='tableborder'>
<table cellpadding='4' cellspacing='0' width='100%' border='0' class='tablerow1'>
<tr>
 <td align='left' valign='middle'>{$choose}&nbsp;<input type='button' class='realbutton' value='Reorder Children' onclick='gochildrenofthecorn()'/></td>
 <td align='right'><input type='button' class='realbutton' value='Add New Category' onclick='locationjump("&{$this->ipsclass->form_code}&code=new&type=category")' />
 &nbsp;&nbsp;<input type='submit' value='Reorder Categories' class='realbutton' /></form>
 </td>
</tr>
</table>
</div>
<br />
<form method='post' action='{$this->ipsclass->base_url}&amp;section={$this->ipsclass->section_code}&amp;act=mod&amp;code=add' id='modform'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<input type='hidden' name='modforumids' id='modforumids' />
<div class='tableborder'>
<table cellpadding='2' cellspacing='0' width='100%' border='0' class='tablerow1'>
<tr>
 <td valign='middle'><strong>Add a moderator to all checked forum(s):</strong></td>
 <td>
 Name <input class='realbutton' type='text' name='name' size='20' value='' /> <strong><i>OR</i></strong> Group {$mem_group}
 </td>
 <td width='1%' valign='middle'><input type='button' class='realbutton' value='Go &gt;&gt;' onclick='gomodform()' /></td>
</tr>
</table>
</form>

</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Forum: Render normal row
//===========================================================================
function render_forum_row( $desc, $r, $depth_guide, $skin ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1' width='95%'>
	{$depth_guide}
EOF;
if ( $r['id'] == $this->ipsclass->vars['forum_trash_can_id'] )
{
$IPBHTML .= <<<EOF
 <img src='{$this->ipsclass->adskin->img_url}/images/acp_trashcan.gif' border='0' title='This is the trashcan forum' />
EOF;
}

$IPBHTML .= <<<EOF
<strong style='font-size:11px'>{$r['name']}</strong>
EOF;

if ( ($r['skin_id'] != "") and ($r['skin_id'] > 0) )
{
$IPBHTML .= <<<EOF
<br>[ Using Skin Set: {$skin} ]
EOF;
}

$IPBHTML .= <<<EOF
	<div class='graytext'>{$desc}</div>
EOF;

if ( $r['_modstring'] != "" )
{
$IPBHTML .= <<<EOF
<div style='display:none' id='moddiv-{$r['id']}'><fieldset style='padding:4px;height:45px'><legend>Moderators</legend>{$r['_modstring']}</fieldset></div>
EOF;
}

$IPBHTML .= <<<EOF
 </td>
 <td class='tablerow1' align='right' width='5%' nowrap='nowrap'><input type='checkbox' title='Check this box to include this forum in the list of forums to add a moderator to' id='id_{$r['id']}' value='1' /> <img id="menu{$r['id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$r['id']}",
  new Array( img_edit     + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=edit&f={$r['id']}'>Edit Settings...</a>",
  			 img_info     + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=pedit&f={$r['id']}'>Permissions...</a>",
  			 img_delete   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=empty&f={$r['id']}'>Empty Forum...</a>",
  			 img_delete   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=delete&f={$r['id']}'>Delete Forum...</a>",
  			 img_edit     + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=frules&f={$r['id']}'>Forum Rules...</a>",
  			 img_view     + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=skinedit&f={$r['id']}'>Skin Options...</a>",
  			 img_info      + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=recount&f={$r['id']}'>Resynchronise...</a>"
		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Forum: Render category no forums
//===========================================================================
function render_no_forums( $parent_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1' width='100%' colspan='2'>
	<strong style='font-size:11px;color:red;'>You have not created any forums in this category yet.<br /> It will not be displayed on the site, regardless of permission settings, until it contains at least one forum.</strong>
	<div class='graytext'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=new&p={$parent_id}'>Click here to create a forum in this category</a></div>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forum: Build Permissions
//===========================================================================
function render_forum_permissions( $global=array(), $content="", $title='Permission Access Levels' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
//<![CDATA[

var formobj = document.getElementById('adminform');

//----------------------------------
// Check all column
//----------------------------------

function check_all( permtype )
{
	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];
		
		if ( e && (e.id != 'UPLOAD_ALL') && (e.id != 'DOWNLOAD_ALL') && (e.id != 'READ_ALL') && (e.id != 'REPLY_ALL') && (e.id != 'START_ALL') && (e.id != 'SHOW_ALL') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );
			
			if (a == permtype)
			{
				e.checked = true;
			}
		}
	}
	
	if ( document.getElementById( permtype + '_ALL' ).checked )
	{
		document.getElementById( permtype + '_ALL' ).checked = false;
	}
	else
	{
		document.getElementById( permtype + '_ALL' ).checked = true;
	}
	
	return false;
}

//----------------------------------
// Object has been checked
//----------------------------------

function obj_checked( permtype, pid )
{
	var totalboxes = 0;
	var total_on   = 0;
	
	if ( pid )
	{
		document.getElementById( permtype+'_'+pid ).checked = document.getElementById( permtype+'_'+pid ).checked ? false : true;
	}
	
	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];
		
		if ( e && (e.id != 'UPLOAD_ALL') && (e.id != 'DOWNLOAD_ALL') && (e.id != 'READ_ALL') && (e.id != 'REPLY_ALL') && (e.id != 'START_ALL') && (e.id != 'SHOW_ALL') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );
			
			if ( a == permtype )
			{
				totalboxes++;
				
				if ( e.checked )
				{
					total_on++;
				}
			}
		}
	}
	
	if ( totalboxes == total_on )
	{
		document.getElementById( permtype + '_ALL' ).checked = true;
	}
	else
	{
		document.getElementById( permtype + '_ALL' ).checked = false;
	}
	
	return false;
}

//----------------------------------
// Check column
//----------------------------------

function checkcol( permtype ,status)
{
	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];
		
		if ( e && (e.id != 'UPLOAD_ALL') && (e.id != 'DOWNLOAD_ALL') && (e.id != 'READ_ALL') && (e.id != 'REPLY_ALL') && (e.id != 'START_ALL') && (e.id != 'SHOW_ALL') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );
			
			if ( a == permtype )
			{
				if ( status == 1 )
				{
					e.checked = true;
					document.getElementById( permtype + '_ALL' ).checked = true;
				}
				else
				{
					e.checked = false;
					document.getElementById( permtype + '_ALL' ).checked = false;
				}
			}
		}
	}
	
	return false;
}

//----------------------------------
// Remote click box
//----------------------------------

function toggle_box( compiled_permid )
{
	if ( document.getElementById( compiled_permid ).checked )
	{
		document.getElementById( compiled_permid ).checked = false;
	}
	else
	{
		document.getElementById( compiled_permid ).checked = true;
	}
	
	obj_checked( compiled_permid.replace( /^(.+?)_.+?$/, "$1" ) , '');
	
	return false;
}

//----------------------------------
// INIT
//----------------------------------

function init_perms()
{
	var tds = formobj.getElementsByTagName('td');

	for ( var i = 0 ; i <= tds.length ; i++ )
	{
		var thisobj   = tds[i];
		
		if ( thisobj && thisobj.id )
		{
			var name      = thisobj.id;
			var firstpart = name.replace( /^(.+?)_.+?$/, "$1" );
			
			if ( firstpart == 'clickable' )
			{
				try
				{
					document.getElementById( tds[i].id ).style.cursor = "pointer";
				}
				catch(e)
				{
					document.getElementById( tds[i].id ).style.cursor = "hand";
				}
			}
		}
	}
}

//----------------------------------
// Check row
//----------------------------------

function checkrow( permid, status )
{
	if( document.getElementById( "READ"   	+ '_' + permid ) != null )
	{
		document.getElementById( "READ"   	+ '_' + permid ).checked = status ? true : false;
	}
	
	if( document.getElementById( "REPLY"   	+ '_' + permid ) != null )
	{
		document.getElementById( "REPLY"  	+ '_' + permid ).checked = status ? true : false;
	}
	
	if( document.getElementById( "START"   	+ '_' + permid ) != null )
	{
		document.getElementById( "START"  	+ '_' + permid ).checked = status ? true : false;
	}
	
	if( document.getElementById( "UPLOAD"   	+ '_' + permid ) != null )
	{
		document.getElementById( "UPLOAD" 	+ '_' + permid ).checked = status ? true : false;
	}
	
	if( document.getElementById( "DOWNLOAD"   	+ '_' + permid ) != null )
	{
		document.getElementById( "DOWNLOAD" + '_' + permid ).checked = status ? true : false;
	}
	
	if( document.getElementById( "SHOW"   	+ '_' + permid ) != null )
	{
		document.getElementById( "SHOW"   	+ '_' + permid ).checked = status ? true : false;
	}
	
	
	obj_checked("READ");
	obj_checked("REPLY");
	obj_checked("START");
	obj_checked("UPLOAD");
	obj_checked("DOWNLOAD");
	obj_checked("SHOW");
	
	return false;
}
//]]>
</script>	

<div class='tableborder'>
 <div class='tableheaderalt' id='perm-header'>{$title}</div>
 <table cellpadding='4' cellspacing='0' border='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='13%'>&nbsp;</td>
  <td class='tablesubheader' width='14%' align='center'>Show Forum</td>
  <td class='tablesubheader' width='14%' align='center'>Read Topics</td>
  <td class='tablesubheader' width='14%' align='center'>Reply Topics</td>
  <td class='tablesubheader' width='14%' align='center'>Start Topics</td>
  <td class='tablesubheader' width='14%' align='center'>Upload</td>
  <td class='tablesubheader' width='14%' align='center'>Download</td>
 </tr>
 <tr>
  <td colspan='7' class='tablerow1'>
  <fieldset>
  <legend><strong>Global Permissions</strong> (All current and future permission sets)</legend>
  <table cellpadding='4' cellspacing='0' border='0' class='tablerow1' width='100%'>
  <tr>
   <td class='tablerow2' width='13%'>&nbsp;</td>
   <td class='tablerow1' width='14%' style='background-color:#ecd5d8' onclick='check_all("SHOW")'><center><div class='red-perm'>Show Forum</div> {$global['html_show']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#dbe2de' onclick='check_all("READ")'><center><div class='green-perm'>Read Topics</div> {$global['html_read']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#dbe6ea' onclick='check_all("REPLY")'><center><div class='yellow-perm'>Reply Topics</div> {$global['html_reply']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#d2d5f2' onclick='check_all("START")'><center><div class='blue-perm'>Start Topics</div> {$global['html_start']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#ece6d8' onclick='check_all("UPLOAD")'><center><div class='orange-perm'>Upload</div> {$global['html_upload']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#dfdee9' onclick='check_all("DOWNLOAD")'><center><div class='purple-perm'>Download</div> {$global['html_download']}</center></td>
   </tr>
  </table>
  </fieldset>
  </td>
 </tr>
 {$content}
 <tr>
  <td class='tablerow2'>&nbsp;</td>
  <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("SHOW",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("SHOW",0)' /></center></td>
  <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("READ",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("READ",0)' /></center></td>
  <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("REPLY",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("REPLY",0)' /></center></td>
  <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("START",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("START",0)' /></center></td>
  <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("UPLOAD",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("UPLOAD",0)' /></center></td>
  <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("DOWNLOAD",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("DOWNLOAD",0)' /></center></td>
</tr>		
</table>
</div>
<script type='text/javascript'>
//<![CDATA[
 init_perms();
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Forum: Build Permissions
//===========================================================================
function render_forum_permissions_row( $perm=array(), $data=array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
  <td colspan='7' class='tablerow1'>
  <fieldset>
  <legend><strong>{$data['perm_name']}</strong></legend>
  <table cellpadding='4' cellspacing='0' border='0' class='tablerow1' width='100%'>
  <tr> 
   <td class='tablerow2' width='13%'><input type='button' id='button' value='+' onclick='checkrow({$data['perm_id']},1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkrow({$data['perm_id']},0)' /></td>
   <td class='tablerow1' width='14%' style='background-color:#ecd5d8' id='clickable_{$data['perm_id']}' onclick="toggle_box('SHOW_{$data['perm_id']}')"><center><div class='red-perm'>Show Forum</div> {$perm['html_show']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#dbe2de' id='clickable_{$data['perm_id1']}' onclick="toggle_box('READ_{$data['perm_id']}')"><center><div class='green-perm'>Read Topics</div> {$perm['html_read']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#dbe6ea' id='clickable_{$data['perm_id2']}' onclick="toggle_box('REPLY_{$data['perm_id']}')"><center><div class='yellow-perm'>Reply Topics</div> {$perm['html_reply']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#d2d5f2' id='clickable_{$data['perm_id3']}' onclick="toggle_box('START_{$data['perm_id']}')"><center><div class='blue-perm'>Start Topics</div> {$perm['html_start']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#ece6d8' id='clickable_{$data['perm_id4']}' onclick="toggle_box('UPLOAD_{$data['perm_id']}')"><center><div class='orange-perm'>Upload</div> {$perm['html_upload']}</center></td>
   <td class='tablerow1' width='14%' style='background-color:#dfdee9' id='clickable_{$data['perm_id5']}' onclick="toggle_box('DOWNLOAD_{$data['perm_id']}')"><center><div class='purple-perm'>Download</div> {$perm['html_download']}</center></td>
   </tr>
  </table>
  </fieldset>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forums form
//===========================================================================
function forum_permission_form( $forum, $relative, $perm_matrix ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript' language='javascript'>
</script>
<form name='theAdminForm' id='adminform' action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=pdoedit&amp;f={$this->ipsclass->input['f']}&amp;name={$forum['name']}&amp;nextid={$relative['next']}&amp;previd={$relative['previous']}' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
{$perm_matrix }
<div class='tableborder'><div class='tablesubheader' align='center'>
EOF;
if ( $relative['next'] > 0 )
{
$IPBHTML .= <<<EOF
<input type='submit' name='donext' value='Save and Edit Next' class='realdarkbutton' />
EOF;
}
$IPBHTML .= <<<EOF
<input type='submit' value='Save Only' class='realbutton' />
<input type='submit' name='reload' value='Save and Reload' class='realbutton' />
EOF;
if ( $relative['next'] > 0 )
{
$IPBHTML .= <<<EOF
<input type='submit' name='doprevious' value='Save and Edit Previous' class='realdarkbutton' />
EOF;
}
$IPBHTML .= <<<EOF
</div></div></form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forums form
//===========================================================================
function forum_form( $form, $button, $code, $title, $button, $forum, $perm_matrix ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
function do_convert()
{
	document.getElementById('convert').value = 1;
	document.getElementById('adminform').submit();
}
</script>
<form name='theAdminForm' id='adminform' action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code={$code}&amp;f={$this->ipsclass->input['f']}&amp;name={$forum['name']}' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<input type='hidden' name='convert' id='convert' value='0' />
<input type='hidden' name='type' value='{$this->ipsclass->input['type']}' />
<div class='tableborder'>
 <div class='tableheaderalt'>
  <div style='float:left'>$title</div>
  <div align='right' style='padding-right:5px'>&nbsp;</div>
 </div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 
 <tr>
   <td width='40%' class='tablerow1' colspan='2'>
    <fieldset>
    <legend><strong>Basic Settings</strong></legend>
    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
    <tr>
   		<td width='40%' class='tablerow1'><strong>{$form['addnew_type_upper']} Name</strong></td>
   		<td width='60%' class='tablerow2'>{$form['name']}</td>
 	</tr>
EOF;

if( $form['addnew_type'] != 'category' )
{
$IPBHTML .= <<<EOF
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Forum Description</strong><div class='desctext'>You may use HTML - linebreaks automatically converted to &lt;br&gt;</div></td>
   		<td width='60%' class='tablerow2'>{$form['description']}</td>
 	</tr>

 	<tr>
   		<td width='40%' class='tablerow1'><strong>Forum Parent</strong></td>
   		<td width='60%' class='tablerow2'>{$form['parent_id']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Forum State</strong></td>
   		<td width='60%' class='tablerow2'>{$form['status']}</td>
 	</tr> 	
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Treat this forum as a category?</strong><div class='desctext'>If this is set to 'Yes' previously posted topics will not display, and posting in this forum will not be allowed.  Users will only be allowed to view the subforums under this forum.  If this is set to yes, the rest of the settings will have no effect.</div></td>
   		<td width='60%' class='tablerow2'>{$form['sub_can_post']}</td>
 	</tr>
    </table>
   </fieldset>
  </td>
 </tr>
 
 <tr>
   <td width='40%' class='tablerow1' colspan='2'>
    <fieldset>
    <legend><strong>Forum Redirect Settings</strong></legend>
    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
    <tr>
   		<td width='40%' class='tablerow1'><strong>Redirection URL</strong></td>
   		<td width='60%' class='tablerow2'>{$form['redirect_url']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Enable URL redirection</strong><div class='desctext'>If 'yes' you can skip the rest of this form as the settings will have no effect and this forum will act like as a redirect link. Current posts will not be accessible when enabled.</div></td>
   		<td width='60%' class='tablerow2'>{$form['redirect_on']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Number of redirect clicks</strong></td>
   		<td width='60%' class='tablerow2'>{$form['redirect_hits']}</td>
 	</tr>
    </table>
   </fieldset>
  </td>
 </tr>
 
 <tr>
   <td width='40%' class='tablerow1' colspan='2'>
    <fieldset>
    <legend><strong>Permission Settings</strong></legend>
    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
    <tr>
   		<td width='40%' class='tablerow1'><strong>Allow members who can see the forum but cannot read topics to see the topic list</strong><div class='desctext'>If yes, the member will be able to see the topic titles but will not be able to read the topic posts when clicked.</div></td>
   		<td width='60%' class='tablerow2'>{$form['permission_showtopic']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Custom 'Permission Denied' Message</strong><div class='desctext'>You may use HTML - linebreaks automatically converted to &lt;br&gt;.<br />If left blank, a default 'permission denied' error is used.</div></td>
   		<td width='60%' class='tablerow2'>{$form['permission_custom_error']}</td>
 	</tr>
    </table>
   </fieldset>
  </td>
 </tr>
 
 <tr>
   <td width='40%' class='tablerow1' colspan='2'>
    <fieldset>
    <legend><strong>Postable Forum Settings: Options</strong></legend>
    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
    <tr>
   		<td width='40%' class='tablerow1'><strong>Enable HTML Posting (where allowed)</strong><div class='desctext'>This will allow HTML to be posted and executed.</div></td>
   		<td width='60%' class='tablerow2'>{$form['use_html']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Enable BBCode Posting</strong></td>
   		<td width='60%' class='tablerow2'>{$form['use_ibc']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Enable Quick Reply</strong></td>
   		<td width='60%' class='tablerow2'>{$form['quick_reply']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Enable Polls (per user group option)</strong></td>
   		<td width='60%' class='tablerow2'>{$form['allow_poll']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Enable Poll Bump</strong><div class='desctext'>If enabled, a poll vote will move the topic to the top of the list when sorted by date.</td>
   		<td width='60%' class='tablerow2'>{$form['allow_pollbump']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Enable Topic Rating (per user group option)</strong></td>
   		<td width='60%' class='tablerow2'>{$form['forum_allow_rating']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Enable Post Count Incrementation</strong><div class='desctext'>If enabled, posts in this forum increase member's cumulative post count.</td>
   		<td width='60%' class='tablerow2'>{$form['inc_postcount']}</td>
 	</tr>
    </table>
   </fieldset>
  </td>
 </tr>
 
 <tr>
   <td width='40%' class='tablerow1' colspan='2'>
    <fieldset>
    <legend><strong>Postable Forum Settings: Moderation</strong></legend>
    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
    <tr>
   		<td width='40%' class='tablerow1'><strong>Enable Posting Moderation</strong><div class='desctext'>Requires a moderator to manually add posts/topics to the forum.</div></td>
   		<td width='60%' class='tablerow2'>{$form['preview_posts']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Email addresses to send new topic awaiting approval notification</strong><div class='desctext'>This can be left blank</div></td>
   		<td width='60%' class='tablerow2'>{$form['notify_modq_emails']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Forum Password</strong><div class='desctext'>Leave this box empty if you do not require this.</div></td>
   		<td width='60%' class='tablerow2'>{$form['password']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Exempt Groups</strong><div class='desctext'>If you require a password to access this forum, you can select groups who will not need to enter the password in.</div></td>
   		<td width='60%' class='tablerow2'>{$form['password_override']}</td>
 	</tr> 	
    </table>
   </fieldset>
  </td>
 </tr>
 
 <tr>
   <td width='40%' class='tablerow1' colspan='2'>
    <fieldset>
    <legend><strong>Postable Forum Settings: Sorting and Ordering</strong></legend>
    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
    <tr>
   		<td width='40%' class='tablerow1'><strong>Default date cut off for topic display</strong></td>
   		<td width='60%' class='tablerow2'>{$form['prune']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Default sort key</strong></td>
   		<td width='60%' class='tablerow2'>{$form['sort_key']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Default sort order</strong></td>
   		<td width='60%' class='tablerow2'>{$form['sort_order']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Default sort filter</strong></td>
   		<td width='60%' class='tablerow2'>{$form['topicfilter']}</td>
 	</tr>
    </table>
   </fieldset>
  </td>
 </tr>
 </table>
</div>
EOF;
}
else
{
$IPBHTML .= <<<EOF
	</table>
   </fieldset>
  </td>
 </tr>
 </table>
 <input type='hidden' name='parent_id' value='-1' />
 <input type='hidden' name='sub_can_post' value='0' />
 <input type='hidden' name='permission_showtopic' value='1' />
</div>
EOF;
}

if ( $perm_matrix )
{
$IPBHTML .= <<<EOF
<br />
$perm_matrix
<br />
EOF;
}
$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='$button' /> {$form['convert_button']}</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Forum: Build Permissions
//===========================================================================
function render_cat_permissions( $data=array(), $select_all='', $title='Permission Access Levels' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
//<![CDATA[

var formobj = document.getElementById('adminform');

function perm_check( obj )
{
	var real_obj      = document.getElementById( obj );
	var total_options = real_obj.options.length;
	var total_checked = 0;
	
	for( var i = 0 ; i < real_obj.options.length ; i++ )
	{
		if ( real_obj.options[i].selected )
		{
			total_checked++;
		}
	}
	
	if ( total_checked == total_options )
	{
		document.getElementById( 'show_all' ).checked = true;
	}
	else
	{
		document.getElementById( 'show_all' ).checked = false;
	}
}

function perm_check_all( obj )
{
	var real_obj   = document.getElementById( obj );
	var isselected = document.getElementById( 'show_all').checked ? true : false;
	
	for( var i = 0 ; i < real_obj.options.length ; i++ )
	{
		real_obj.options[i].selected = isselected;
	}
	
	document.getElementById( 'show_all').checked = isselected;
}

//]]>
</script>	

<div class='tableborder'>
 <div class='tableheaderalt' id='perm-header'>{$title}</div>
 <table cellpadding='4' cellspacing='0' border='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='100%' align='center'>Show Category</td>
 </tr>
<tr>
  <td class='tablerow1'>
  <fieldset>
  <legend><strong>Show Permissions</strong></legend>
  <table cellpadding='4' cellspacing='0' border='0' class='tablerow1' width='100%'>
  <tr>
   <td class='tablerow2' width='40%' valign='middle' align='right'&nbsp;&nbsp;><span class='desctext'>Show Forum</span></td>
   <td class='tablerow1' width='60%'>
   	<input type='checkbox' name='show_all' id='show_all' value='1' onclick='perm_check_all("show")' {$select_all} /> Select All Current and Future Sets
   	<br /><select onchange='perm_check("show")' id='show' name='show_permissions[]' size='6' multiple='true'>
EOF;

if( count($data) )
{
	foreach( $data as $perm_row )
	{
$IPBHTML .= <<<EOF
		<option value='{$perm_row['perm_id']}' {$perm_row['perm_selected']}>{$perm_row['perm_name']}</option>
EOF;
	}
}
$IPBHTML .= <<<EOF
	</select>
	</td>
   </tr>
  </table>
  </fieldset>
 </td>
</tr> 
</table>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}




}


?>