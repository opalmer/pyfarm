<?php

class cp_skin_api {

var $ipsclass;

//===========================================================================
// API|LOG View
//===========================================================================
function api_log_detail( $log ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>XML-RPC Log Detail</div>
	<table width='100%' cellpadding='4' cellspacing='0'>
	<tr>
		<td class='tablerow2'>
			<fieldset>
				<legend><strong>Basics</strong></legend>
				<table width='100%' cellpadding='4' cellspacing='0'>
				 <tr>
					<td width='30%' class='tablerow1'>API Key</td>
					<td width='70%' class='tablerow1'><strong>{$log['api_log_key']}</strong></td>
				</tr>
				<tr>
					<td class='tablerow1'>IP Address</td>
					<td class='tablerow1'>{$log['api_log_ip']}</td>
				</tr>
				<tr>
					<td class='tablerow1'>Time</td>
					<td class='tablerow1'>{$log['_api_log_date']}</td>
				</tr>
				<tr>
					<td class='tablerow1'>Success</td>
					<td class='tablerow1'><img src='{$this->ipsclass->skin_acp_url}/images/{$log['_api_log_allowed']}' border='0' alt='-' class='ipd' /></td>
				</tr>
				</table>
			</fieldset>
		<br />
		<fieldset>
			<legend><strong>XML-RPC Data (Form Data)</strong></legend>
			<div style='border:1px solid black;background-color:#FFF;padding:4px;white-space:pre;height:400px;overflow:auto'>
				{$log['_api_log_query']}
			</div>
		</fieldset>
	</td>
</tr>
</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// API|LOG List
//===========================================================================
function api_login_view( $logs, $links ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>XML-RPC Request Log</div>
 <table width='100%' cellpadding='4' cellspacing='0'>
 <tr>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
  <td class='tablesubheader' width='30%'>API Key</td>
  <td class='tablesubheader' width='20%'>IP Address</td>
  <td class='tablesubheader' width='44%' align='center'>Date</td>
  <td class='tablesubheader' width='5%' align='center'>Status</td>
  <td class='tablesubheader' width='5%' align='center'>Log</td>
 </tr>
EOF;

if ( is_array( $logs ) AND count( $logs ) )
{
	foreach( $logs as $r )
	{
$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1' width='1' valign='middle'>
	<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/xmlrpc/log_row.png' border='0' alt='-' class='ipd' />
 </td>
 <td class='tablerow1'> <strong>{$r['api_log_key']}</strong></td>
 <td class='tablerow2'><div class='desctext'>{$r['api_log_ip']}</div></td>
 <td class='tablerow2' align='center'>{$r['_api_log_date']}</td>
 <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$r['_api_log_allowed']}' border='0' alt='-' class='ipd' /></td>
 <td class='tablerow1' width='1' valign='middle'>
 	<a href='#' onclick="return ipsclass.pop_up_window('{$this->ipsclass->base_url}&amp;section=admin&amp;act=api&amp;code=log_view_detail&amp;api_log_id={$r['api_log_id']}', 800, 600)" title='View Details'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/view.png' border='0' alt='-' class='ipd' /></a>
 </td>
</tr>
EOF;
	}
}
$IPBHTML .= <<<EOF
 </table>
 <div class='tablefooter' align='right'>
   $links
 </div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// API LIST
//===========================================================================
function api_list( $api_users ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>XML-RPC API Users</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
  <td class='tablesubheader' width='55%'>API User</td>
  <td class='tablesubheader' width='20%' align='center'>API Key</td>
  <td class='tablesubheader' width='20%' align='center'>IP</td>
  <td class='tablesubheader' width='5%'><img id="menumainone" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
 </tr>
EOF;

if ( count( $api_users ) )
{
	foreach( $api_users as $user )
	{
$IPBHTML .= <<<EOF
 <tr>
	<td class='tablerow1'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/xmlrpc/api_user.png' class='ipb' /></td>
	<td class='tablerow2'><strong>{$user['api_user_name']}</strong>
	<td class='tablerow2'><strong style='font-size:14px'>{$user['api_user_key']}</strong>
	<td class='tablerow2'><strong>{$user['api_user_ip']}</strong>
	<td class='tablerow2' width='5%'><img id="menu{$user['api_user_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$user['api_user_id']}",
  new Array(
			img_edit   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=api_edit&amp;api_user_id={$user['api_user_id']}'>Edit API User...</a>",
  			img_delete + " <a href='#' onclick='maincheckdelete(\"{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=api_remove&amp;api_user_id={$user['api_user_id']}\");'>Remove API User...</a>"
  		    ) );
 </script>
EOF;
	}
}
else
{
$IPBHTML .= <<<EOF
 <tr>
	<td colspan='5' class='tablerow1' style='text-align:center;font-size:14px'><em>There are no API users currently.<br /><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=api_add'>Would you like to create one?</a></em></td>
 </tr>
EOF;
}

$IPBHTML .= <<<EOF
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
<script type="text/javascript">
  menu_build_menu(
  "menumainone",
  new Array( img_add   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=api_add'>Create New API User...</a>" ) );
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// API FORM
//===========================================================================
function api_form( $form, $title, $formcode, $button, $api_user, $type, $permissions ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/skin_acp/clientscripts/ipd_form_functions.js'></script>
<script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/skin_acp/clientscripts/ipd_tab_factory.js'></script>
<script type="text/javascript">
//<![CDATA[
// INIT FORM FUNCTIONS stuff
var formfunctions = new form_functions();
// INIT TAB FACTORY stuff
var tabfactory    = new tab_factory();
</script>
<form id='mainform' action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=$formcode&amp;api_user_id={$api_user['api_user_id']}' method='post'>
<div class='tableborder'>
 <div class='tableheaderalt'>$title</div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
   <td class='tablerow1'>
      <fieldset>
       <legend><strong>API User Basics</strong></legend>
 		<table cellpadding='0' cellspacing='0' border='0' width='100%'>
EOF;
if ( $type == 'add' )
{
$IPBHTML .= <<<EOF
	<tr>
	  <td width='40%' class='tablerow1'>
		<strong>API User Key</strong>
		<div class='desctext'>This key is automatically generated and will be re-generated if you refresh this form.</div>
		<input type='hidden' name='api_user_key' value='{$form['_api_user_key']}' />
	  </td>
	  <td width='60%' class='tablerow2' style='font-size:14px'>{$form['_api_user_key']}</td>
	</tr>
EOF;
}

$IPBHTML .= <<<EOF
 		<tr>
   		  <td width='40%' class='tablerow1'><strong>API User Title</strong><div class='desctext'>The title is just a name for your records for easier identification.</div></td>
   		  <td width='60%' class='tablerow2'>{$form['api_user_name']}</td>
 		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>API Restrict IP</strong><div class='desctext'><strong>Optional</strong>: You may enter the IP Address of the server you only wish to grant access. This increases security and is recommended.</div></td>
		  <td width='60%' class='tablerow2'>{$form['api_user_ip']}</td>
		</tr>
	   </table>
	 </fieldset>
  </td>
 </tr>
 <tr>
  <td class='tablerow1'>
      <fieldset>
       <legend><strong>API Permissions</strong></legend>
		<div class='tabwrap'>
EOF;

if ( is_array( $permissions ) AND count( $permissions ) )
{
	foreach( $permissions as $key => $data )
	{
$IPBHTML .= <<<EOF
			<div id='tabtab-{$key}' class='taboff'>{$data['title']}</div>
EOF;
	}
}

$IPBHTML .= <<<EOF
		</div>
		<div class='tabclear'>API Permissions</div>
EOF;

if ( is_array( $permissions ) AND count( $permissions ) )
{
	foreach( $permissions as $key => $data )
	{
$IPBHTML .= <<<EOF
			<div id='tabpane-{$key}' class='formmain-background'>
			<table cellpadding='0' cellspacing='0' border='0' width='100%'>
EOF;
		if ( is_array( $permissions[ $key ]['form_perms'] ) AND ( $permissions[ $key ]['form_perms'] ) )
		{
			foreach( $permissions[ $key ]['form_perms'] as $perm => $_data )
			{
$IPBHTML .= <<<EOF
			<tr>
				<td class='tablerow1' width='70%'>Allow access to <strong>{$_data['title']}</strong></td>
				<td class='tablerow2' width='30%'>{$_data['form']}</td>
EOF;
			}
		}
$IPBHTML .= <<<EOF
			</table>
			</div>
EOF;
	}
}

$IPBHTML .= <<<EOF
	  </fieldset>
  </td>
 </tr>
 </table>
 <div align='center' class='tablefooter'>
 	<div class='formbutton-wrap'>
 		<div id='button-save'><img src='{$this->ipsclass->skin_acp_url}/images/icons_form/save.gif' border='0' alt='Save'  title='Save' class='ipd-alt' /> $button</div>
	</div>
</div>
</div>
</form>
<script type="text/javascript">
//<![CDATA[
// Init form functions, grab stuff
formfunctions.init();
// Pass ID name of FORM tag
formfunctions.name_form = 'mainform';
formfunctions.add_submit_event( 'button-save' );
// Stuff. Well done Matt
tabfactory.init_tabs();
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

}


?>