<?php

class cp_skin_groups {

var $ipsclass;

//===========================================================================
// Groups: Overview scream :o :o
//===========================================================================
function groups_overview_wrapper($content, $form) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>User Group Management</div>
 <table cellpadding='4' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='40%'>Group Title</td>
  <td class='tablesubheader' width='10%' align='center'>Can Access ACP</td>
  <td class='tablesubheader' width='10%' align='center'>Is Super Mod</td>
  <td class='tablesubheader' width='10%' align='center'>Members</td>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
 </tr>
 {$content}
 </table>
</div>
<br />
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=add' method='POST' >
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>Create a User Group</div>
 <table cellpadding='4' cellspacing='0' width='100%'>
 <tr>
  <td class='tablerow2' width='40%'><strong>Base new group on...</strong></td>
  <td class='tablerow1' width='60%'>{$form['_new_dd']}</td>
 </tr>
 <tr>
  <td colspan='2' class='tablesubheader' align='center'><input type='submit' value='Create...' class='realbutton' /></td>
 </tr>
 </table>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Groups
//===========================================================================
function groups_overview_wrapper_row( $r="" ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
  <td class='tablerow2' style='font-weight:bold'>
EOF;
if ( $r['g_id'] != $this->ipsclass->vars['auth_group'] and $r['g_id'] != $this->ipsclass->vars['guest_group'] )
{
$IPBHTML .= <<<EOF
	<a href='{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?act=Members&max_results=30&showall=1&filter={$r['g_id']}&sort_order=asc&sort_key=members_display_name&st=0' target='_blank' title='List Users'>{$r['_title']}</a>
EOF;
}
else
{
$IPBHTML .= <<<EOF
    {$r['_title']}
EOF;
}
$IPBHTML .= <<<EOF
 </td>
  <td class='tablerow1' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$r['_can_acp_img']}' border='0' alt='-' class='ipd' /></td>
  <td class='tablerow1' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$r['_can_supmod_img']}' border='0' alt='-' class='ipd' /></td>
  <td class='tablerow1' align='center'>{$r['count']}</td>												
  <td class='tablerow1' align='center'><img id="menu{$r['g_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$r['g_id']}",
  new Array( img_edit   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=edit&amp;id={$r['g_id']}'>Edit Group</a>",
EOF;
if ( ! in_array( $r['g_id'], array( $this->ipsclass->vars['auth_group'], $this->ipsclass->vars['guest_group'], $this->ipsclass->vars['member_group'], $this->ipsclass->vars['admin_group'] ) )  )
{
$IPBHTML .= <<<EOF
              img_delete   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=delete&amp;_admin_auth_key={$this->ipsclass->_admin_auth_key}&amp;id={$r['g_id']}'>Delete</a>"
EOF;
}
else
{
$IPBHTML .= <<<EOF
              img_delete   + " <em>Cannot Delete</em>"
EOF;
}
$IPBHTML .= <<<EOF
		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Groups
//===========================================================================
function groups_perm_splash_wrapper($content, $dlist) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
					    
<div class='tableborder'>
 <div class='tableheaderalt'>Forum Permission Sets</div>
 <table cellpadding='4' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='20%'>Name</td>
  <td class='tablesubheader' width='15%'>Used by Group(s)</td>
  <td class='tablesubheader' width='20%' align='center'>Used by Member(s)</td>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
 </tr>
 {$content}
 </table>
 
</div>
<br />
<form name='theAdminForm' id='adminform' action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=dopermadd' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
 <div class='tableborder'>
  <div class='tableheaderalt'>Create a new permission set</div>
  <table cellpadding='4' cellspacing='0' width='100%'>
  <tr>
   <td class='tablerow1'><strong>Permission Set Name</strong></td>
   <td class='tablerow2'><input type='text' class='input' size='30' name='new_perm_name' /></td>
  </tr>
  <tr>
   <td class='tablerow1'><strong>Base this permission set on which existing set...</strong></td>
   <td class='tablerow2'><select name='new_perm_copy' class='dropdown'>{$dlist}</select></td>
  </tr>
 </table>
 <div class='tablefooter' align='center'><input type='submit' value='Create' class='realbutton' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Groups
//===========================================================================
function groups_perm_splash_row( $r="" ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
  <td class='tablerow2'><strong>{$r['name']}</strong></td>
  <td class='tablerow1'>{$r['groups']}</td>
  <td class='tablerow1' align='center'>
EOF;
if ( $r['mems'] > 0 )
{
$IPBHTML .= <<<EOF
{$r['mems']} (<a href='javascript:pop_win("&amp;{$this->ipsclass->form_code}&amp;code=view_perm_users&amp;id={$r['id']}", "User", "500","350");' title='View the member names of those using this permission set in a new window'>View</a>)
EOF;
}
else
{
$IPBHTML .= <<<EOF
0
EOF;
}
$IPBHTML .= <<<EOF
  </td>															
  <td class='tablerow1' align='center'><img id="menu{$r['id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$r['id']}",
  new Array( img_view   + " <a href='javascript:pop_win(\"&amp;{$this->ipsclass->form_code}&amp;code=preview_forums&amp;id={$r['id']}&amp;t=read\", \"Preview\", \"400\",\"350\");' title='See what this group can see..'>Preview...</a>",
  			 img_edit   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=fedit&amp;id={$r['id']}'>Edit...</a>",
EOF;
if ( ! $r['isactive'] )
{
$IPBHTML .= <<<EOF
              img_delete   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=pdelete&amp;id={$r['id']}'>Delete...</a>"
EOF;
}
else
{
$IPBHTML .= <<<EOF
              img_delete   + " <em>In Use</em>"
EOF;
}
$IPBHTML .= <<<EOF
		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}


function permissions_js()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<script type='text/javascript'>
//<![CDATA[

//----------------------------------
// Check column
//----------------------------------

function checkcol( permtype ,status )
{
	var formobj = document.getElementById('theAdminForm');
	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];
		
		if ( e && (e.id != 'upload') && (e.id != 'download') && (e.id != 'read') && (e.id != 'reply') && (e.id != 'start') && (e.id != 'show') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );

			if ( a == permtype )
			{
				if ( status == 1 )
				{
					e.checked = true;
				}
				else
				{
					e.checked = false;
				}
			}
		}
	}
	
	return false;
}

function checkrow( permid, status )
{
	if( document.getElementById( "read"   	+ '_' + permid ) != null )
	{
		document.getElementById( "read"   	+ '_' + permid ).checked = status;
	}
	
	if( document.getElementById( "reply"   	+ '_' + permid ) != null )
	{
		document.getElementById( "reply"  	+ '_' + permid ).checked = status;
	}
	
	if( document.getElementById( "start"   	+ '_' + permid ) != null )
	{
		document.getElementById( "start"  	+ '_' + permid ).checked = status;
	}
	
	if( document.getElementById( "upload"   	+ '_' + permid ) != null )
	{
		document.getElementById( "upload" 	+ '_' + permid ).checked = status;
	}
	
	if( document.getElementById( "download"   	+ '_' + permid ) != null )
	{
		document.getElementById( "download" + '_' + permid ).checked = status;
	}
	
	if( document.getElementById( "show"   	+ '_' + permid ) != null )
	{
		document.getElementById( "show"   	+ '_' + permid ).checked = status;
	}
	
	obj_checked( "read", permid );
	obj_checked( "reply", permid );
	obj_checked( "start", permid );
	obj_checked( "show", permid );
	obj_checked( "upload", permid );
	obj_checked( "download", permid );

	return false;
}


function obj_checked( permtype, pid )
{
	var formobj = document.getElementById('theAdminForm');
	
	var totalboxes = 0;
	var total_on   = 0;
	
	if ( pid )
	{
		document.getElementById( permtype+'_'+pid ).checked = document.getElementById( permtype+'_'+pid ).checked ? true : false;
	}
	
	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];
		
		if ( e && (e.id != 'upload') && (e.id != 'download') && (e.id != 'read') && (e.id != 'reply') && (e.id != 'start') && (e.id != 'show') && (e.type == 'checkbox') && (! e.disabled) )
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
		document.getElementById( permtype ).checked = true;
	}
	else
	{
		document.getElementById( permtype ).checked = false;
	}
	
	return false;
}


function init_perms(  )
{
	var formobj = document.getElementById('theAdminForm');
	var checkboxes = formobj.getElementsByTagName('input');
		
	var totalboxes = new Array();
	totalboxes['upload'] 	= 0;
	totalboxes['download'] 	= 0;
	totalboxes['read'] 		= 0;
	totalboxes['reply'] 	= 0;
	totalboxes['start'] 	= 0;
	totalboxes['show'] 		= 0;
	
	var total_on   = new Array();
	total_on['upload'] 		= 0;
	total_on['download'] 	= 0;
	total_on['read'] 		= 0;
	total_on['reply'] 		= 0;
	total_on['start'] 		= 0;
	total_on['show'] 		= 0;	
	
	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];
		
		if ( e && (e.id != 'upload') && (e.id != 'download') && (e.id != 'read') && (e.id != 'reply') && (e.id != 'start') && (e.id != 'show') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );
			var b = s.replace( /^(.+?)_(.+?)$/, "$2" );
			
			totalboxes[a]++;
			
			if ( e.checked )
			{
				total_on[a]++;
			}
		}
	}
	
	for ( key in totalboxes )
	{
		if ( totalboxes[key] == total_on[key] )
		{
			document.getElementById( key ).checked = true;
		}
		else
		{
			document.getElementById( key ).checked = false;
		}
	}
	
	return false;
}

init_perms();

//]]>
</script>	

EOF;

return $IPBHTML;
}



}

?>