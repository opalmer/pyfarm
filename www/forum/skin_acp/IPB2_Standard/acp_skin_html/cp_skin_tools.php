<?php

class cp_skin_tools {

var $ipsclass;


//===========================================================================
// Menu manage:Blank Pos
//===========================================================================
function components_position_blank($com_id) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img src='{$this->ipsclass->skin_acp_url}/images/blank.gif' width='12' height='12' border='0' style='vertical-align:middle' />
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Menu manage:Blank Pos
//===========================================================================
function components_position_up($com_id) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=component_move&amp;move=up&amp;com_id={$com_id}' title='Move up in position'><img src='{$this->ipsclass->skin_acp_url}/images/arrow_up.png' width='12' height='12' border='0' style='vertical-align:middle' /></a>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Menu manage:Blank Down
//===========================================================================
function components_position_down($com_id) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=component_move&amp;move=down&amp;com_id={$com_id}' title='Move down in position'><img src='{$this->ipsclass->skin_acp_url}/images/arrow_down.png' width='12' height='12' border='0' style='vertical-align:middle' /></a>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Component FORM
//===========================================================================
function components_form($form, $title, $formcode, $button, $component, $menu_text, $menu_url, $menu_redirect, $menu_permbit, $menu_permlang) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_components.js'></script>
<script type="text/javascript">

  var comp = new components();
  // Title
  var menu_text     =
  {
{$menu_text}
  };

  var menu_url      =
  {
{$menu_url}
  };

  var menu_redirect =
  {
{$menu_redirect}
  };

   var menu_permbit =
  {
{$menu_permbit}
  };

   var menu_permlang =
  {
{$menu_permlang}
  };

  // HTML elements
  var html_add_menu_row     = "<br />[<a href='#' title='Add new menu item' style='color:green;font-weight:bold' onclick='return comp.add_menu_row()'>Add new menu item</a>]";
  var html_box_text         = "<tr><td width='20%'>Menu Text</td><td><input type='text' id='menu_text_<%1>' name='menu_text[<%1>]' size='30' class='forminput' value='<%2>' /></td></tr>";
  var html_box_url          = "<tr><td width='20%'>Menu URL</td><td><input type='text' id='menu_url_<%1>' name='menu_url[<%1>]' size='30' class='forminput' value='<%2>' /></td></tr>";
  var html_box_redirect     = "<tr><td width='20%'>Menu Redirect?</td><td><input type='checkbox' id='menu_redirect_<%1>' name='menu_redirect[<%1>]' class='forminput' value='1' /></td></tr>";
  var html_box_permbit      = "<tr><td width='20%'>Menu Perm Bit</td><td><input type='text' id='menu_permbit_<%1>' name='menu_permbit[<%1>]' size='30' class='forminput' value='<%2>' /></td></tr>";
  var html_box_permlang     = "<tr><td width='20%'>Menu Perm Lang</td><td><input type='text' id='menu_permlang_<%1>' name='menu_permlang[<%1>]' size='30' class='forminput' value='<%2>' /></td></tr>";
  var html_menu_wrap        = "<div><fieldset style='padding:4px'><table cellpadding='2' cellspacing='0' width='100%'><tr><%1></tr></table>[<a href='#' title='Remove menu row' style='color:red;font-weight:bold' onclick='return comp.remove_menu_row("+'"'+'<%2>'+'"'+")'>Remove Menu Row</a>]</fieldset></div>";
 </script>
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=$formcode&amp;com_id={$component['com_id']}' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>$title</div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
   <td width='40%' class='tablerow1'><strong>Component Title</strong></td>
   <td width='60%' class='tablerow2'>{$form['com_title']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Component Version</strong></td>
   <td width='60%' class='tablerow2'>{$form['com_version']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Component Brief Description</strong></td>
   <td width='60%' class='tablerow2'>{$form['com_description']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Component Author</strong></td>
   <td width='60%' class='tablerow2'>{$form['com_author']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Component Home URL</strong></td>
   <td width='60%' class='tablerow2'>{$form['com_url']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1' valign='top'><strong>Component ACP Menu Data</strong>
   	<div class='desctext'>
   	<a href='#' onclick='pop_win("act=quickhelp&id=comp_menu", "help", 250, 400 )'>More information about menu settings</a>
   	</div>
   </td>
   <td width='60%' class='tablerow2'>
   <div id='components-menu-box'>

   </div>
   <!--{$form['com_menu_data']}-->

   </td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Component Board Header URL</strong><div class='desctext'>{ipb.base_url} will be converted to the board's base URL (complete with session ID, etc)</div></td>
   <td width='60%' class='tablerow2'>{$form['com_url_uri']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Component Board Header Title</strong><div class='desctext'>{ipb.lang['some_words']} will be converted to the lang_global's "some_words" entry</div></td>
   <td width='60%' class='tablerow2'>{$form['com_url_title']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Component Enabled</strong></td>
   <td width='60%' class='tablerow2'>{$form['com_enabled']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Component Section Code</strong><div class='desctext'>This is the section code which loads the appropriate file. Each component code must be unique and be exactly the same as the filename of the component_* files (minus .php).</div></td>
   <td width='60%' class='tablerow2'>{$form['com_section']}</td>
 </tr>
 <!--<tr>
   <td width='40%' class='tablerow1'><strong>Component PHP File</strong><div class='desctext'>The name of the PHP file in 'sources/components_*/'</div></td>
   <td width='60%' class='tablerow2'>{$form['com_filename']}<strong>.php</strong></td>
 </tr>-->
EOF;
//startif
if ( $form['com_safemode'] != '' )
{
$IPBHTML .= <<<EOF
<tr>
   <td width='40%' class='tablerow1'><strong>Enable Safemode? (Cannot be deleted or edited by user)</strong></td>
   <td width='60%' class='tablerow2'>{$form['com_safemode']}</td>
 </tr>
EOF;
}//endif
$IPBHTML .= <<<EOF
 </table>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='$button' /></div>
</div>
 <script type="text/javascript">
  comp.init();
 </script>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// COMPONENTS: Overview
//===========================================================================
function component_overview( $content ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Registered Components</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='30%'>Name</td>
  <td class='tablesubheader' width='25%'>Author</td>
  <td class='tablesubheader' width='5%' align='center'>Position</td>
  <td class='tablesubheader' width='5%'>Enabled</td>
  <td class='tablesubheader' width='5%'><img id="menumainone" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
 </tr>
 $content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
<br />
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=component_import' enctype='multipart/form-data' method='POST'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<input type='hidden' name='MAX_FILE_SIZE' value='10000000000' />
<div class='tableborder'>
 <div class='tableheaderalt'>Import XML Component File</div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
  <td class='tablerow1'><strong>Upload XML components file from your computer</strong><div class='desctext'>Duplicate entries will not be overwritten but the default setting and other options will be updated. The file must begin with 'ipd_' and end with either '.xml' or '.xml.gz'</div></td>
  <td class='tablerow2'><input class='textinput' type='file' size='30' name='FILE_UPLOAD' /></td>
 </tr>
 <tr>
  <td class='tablerow1'><strong><u>OR</u> enter the filename of the XML settings file</strong><div class='desctext'>The file must be uploaded into the root folder</div></td>
  <td class='tablerow2'><input class='textinput' type='text' size='30' name='file_location' /></td>
 </tr>
 </table>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='Import' /></div>
</div>
</form>

 <script type="text/javascript">
  menu_build_menu(
  "menumainone",
  new Array( img_add   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=component_add'>Register New Component...</a>" ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// COMPONENTS: row
//===========================================================================
function component_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'><strong>{$data['_fullname']}</strong><div class='desctext'>{$data['com_description']}</td>
 <td class='tablerow2'>{$data['_fullauthor']}</td>
 <td class='tablerow2' align='center' nowrap='nowrap'>{$data['_pos_up']} &nbsp; {$data['_pos_down']}</td>
 <td class='tablerow2' align='center'>
  <a href={$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=component_toggle_enabled&amp;com_id={$data['com_id']}' title='Toggle Enabled/Disabled'><img src='{$this->ipsclass->skin_acp_url}/images/{$data['_enabled_img']}' border='0' alt='YN' class='ipd' /></a>
 </td>
 <td class='tablerow1'><img id="menu{$data['com_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$data['com_id']}",
  new Array(
EOF;
//startif
if ( ! $data['com_safemode'] OR ( $data['com_safemode'] AND IN_DEV ) )
{
$IPBHTML .= <<<EOF
			img_edit   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=component_edit&amp;com_id={$data['com_id']}'>Edit Component...</a>",
			img_delete   + " <a href='#' onclick='maincheckdelete(\"{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=component_delete&amp;com_id={$data['com_id']}\",\"Are you sure you wish to delete?\")'>Delete Component...</a>",
EOF;
}//endif
//startif
if ( $this->ipsclass->member['mgroup'] == $this->ipsclass->vars['admin_group'] && $data['com_hasuninstall'] )
{
$IPBHTML .= <<<EOF
  			 img_delete   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=component_uninstall&amp;com_id={$data['com_id']}'>Uninstall Component...</a>",
EOF;
}//endif
$IPBHTML .= <<<EOF
  			 img_export   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=component_export&amp;com_id={$data['com_id']}'>Export Component XML...</a>"
  			 ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// CACHE: Overview
//===========================================================================
function cache_overview( $content ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Cache Contents</div>
  <div class='tablesubheader' style='padding-right:0px;height:25px'>
    <div style='float:right;padding-right:12px'>
     <span class='desctext'>Size</span> &nbsp;&nbsp;
     <img id='menumainone' src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' />
    </div>
    <div style='padding-top:6px'>Cache Type</div>
  </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 $content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
 <script type="text/javascript">
  menu_build_menu(
  "menumainone",
  new Array( img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=cache_update_all'>Update all caches...</a>" ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// CACHE: row
//===========================================================================
function cache_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
 	<div style='float:left;'>
 	 <img src='{$this->ipsclass->skin_acp_url}/images/menu_item.gif' class='ipd' /> <strong>{$data['cs_key']}</strong><div class='desctext'>{$data['_desc']}</div>
 	</div>
 	 <div align='right' style='height:18px;padding:0px 5px 2px 0px;'>
	   <span class='desctext'>{$data['_size']}kb</span> &nbsp;
	   <img id="menu{$data['cs_key']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' />
	</div>
  </td>
 <script type="text/javascript">
 menu_build_menu(
  "menu{$data['cs_key']}",
  new Array( img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=cacheend&amp;cache={$data['cs_key']}'>Update Cache...</a>",
			 img_view   + " <a href='#' onclick='pop_win(\"{$this->ipsclass->form_code}&amp;code=viewcache&amp;cache={$data['cs_key']}\",\"Preview\", 400,600)'>View Cache Contents...</a>"
		    ) );
 </script>
 </tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOGIN: Overview
//===========================================================================
function login_overview( $content ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Registered Log In Authentication Methods</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
  <td class='tablesubheader' width='90%'>Title</td>
  <td class='tablesubheader' width='5%' nowrap='nowrap'>Installed</td>
  <td class='tablesubheader' width='5%' nowrap='nowrap'>Enabled</td>
  <td class='tablesubheader' width='5%'><img id="menumainone" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
 </tr>
 $content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>

 <script type="text/javascript">
  menu_build_menu(
  "menumainone",
  new Array( img_add   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=login_add'>Register New Log In Method...</a>" ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOGIN: Overview
//===========================================================================
function login_diagnostics( $login=array() ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Diagnostics for: {$login['login_title']}</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
  <tr>
   <td width='49%' valign='top'>
   <table cellpadding='0' cellspacing='0' width='100%'>
   <tr>
	<td class='tablesubheader' width='70%'>&nbsp;</td>
	<td class='tablesubheader' width='30%'>&nbsp;</td>
   </tr>
   <tr>
    <td class='tablerow1'><strong>Log In Enabled</strong></td>
    <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$login['_enabled_img']}' border='0' alt='*' class='ipd' /></td>
   </tr>
   <tr>
    <td class='tablerow1'><strong>Log In Installed</strong></td>
    <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$login['_installed_img']}' border='0' alt='*' class='ipd' /></td>
   </tr>
   <tr>
    <td class='tablerow1'><strong>Log In Has Settings</strong></td>
    <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$login['_has_settings']}' border='0' alt='*' class='ipd' /></td>
   </tr>
   </table>
   <div align='center' class='tablefooter'>&nbsp;</div>
  </td>
  <td width='2%' class='tablefooter'>&nbsp;</td>
  <td width='49%' valign='top'>
   <table cellpadding='0' cellspacing='0' width='100%'>
   <tr>
	<td class='tablesubheader' width='60%'>File Name</td>
	<td class='tablesubheader' width='20%' align='center'>Exists</td>
	<td class='tablesubheader' width='20%' align='center'>Writeable</td>
   </tr>
   <tr>
    <td class='tablerow1'><strong>./sources/loginauth/{$login['login_folder_name']}/auth.php</strong></td>
    <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$login['_file_auth_exists']}' border='0' alt='*' class='ipd' /></td>
    <td class='tablerow2' align='center'>-</td>
   </tr>
   <tr>
    <td class='tablerow1'><strong>./sources/loginauth/{$login['login_folder_name']}/acp.php</strong></td>
    <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$login['_file_acp_exists']}' border='0' alt='*' class='ipd' /></td>
    <td class='tablerow2' align='center'>-</td>
   </tr>
   <tr>
    <td class='tablerow1'><strong>./sources/loginauth/{$login['login_folder_name']}/conf.php</strong></td>
    <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$login['_file_conf_exists']}' border='0' alt='*' class='ipd' /></td>
    <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$login['_file_conf_write']}' border='0' alt='*' class='ipd' /></td>
   </tr>
   </table>
   <div align='center' class='tablefooter'>&nbsp;</div>
  </td>
 </tr>
 </table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOGIN FORM
//===========================================================================
function login_form($form, $title, $formcode, $button, $login) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=$formcode&amp;login_id={$login['login_id']}' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>$title</div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In Title</strong></td>
   <td width='60%' class='tablerow2'>{$form['login_title']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In Description</strong><div class='desctext'>A short description for this log in method</div></td>
   <td width='60%' class='tablerow2'>{$form['login_description']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In Files Folder Name</strong><div class='desctext'>The main folder the PHP files reside.<br />E.G: If ./sources/loginauth/<strong>internal</strong>/auth.php then enter: internal</div></td>
   <td width='60%' class='tablerow2'>{$form['login_folder_name']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In User Maintenance URL</strong><div class='desctext'>The URL for the place they can edit their password and/or email address.</div></td>
   <td width='60%' class='tablerow2'>{$form['login_maintain_url']} <div class='desctext'>(Optional for On-Fail authentication)</div></td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In User Register URL</strong><div class='desctext'>The URL for the place to register a new account.</div></td>
   <td width='60%' class='tablerow2'>{$form['login_register_url']} <div class='desctext'>(Optional for On-Fail authentication)</div></td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In Authentication Type?</strong><div class='desctext'>Pass-Through: This method always queries the remote DB for user authentication. All accounts must be made by remote.<br />On-Fail: If there isn't a member present in the IPB DB, IPB will query the remote DB.</div></td>
   <td width='60%' class='tablerow2'>{$form['login_type']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In Form HTML</strong><div class='desctext'>Enter the HTML to add or replace the log in form.</div></td>
   <td width='60%' class='tablerow2'>{$form['login_alt_login_html']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In HTML Replace Form</strong><div class='desctext'>If 'yes' the above HTML will replace the log in form. If 'no' it will be added alongside the log in form.</div></td>
   <td width='60%' class='tablerow2'>{$form['login_replace_form']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In User Log In URL</strong><div class='desctext'>The URL for the place to log in.</div></td>
   <td width='60%' class='tablerow2'>{$form['login_login_url']} <div class='desctext'></div></td>
 </tr>
<tr>
   <td width='40%' class='tablerow1'><strong>Log In User Log Out URL</strong><div class='desctext'>The URL for the place to log out.</div></td>
   <td width='60%' class='tablerow2'>{$form['login_logout_url']} <div class='desctext'></div></td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In Enabled</strong><div class='desctext'>If 'yes', this log in will be enabled and any current one disabled.</div></td>
   <td width='60%' class='tablerow2'>{$form['login_enabled']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Log In User Name Type</strong><div class='desctext'>Choose the type of user name required for the log in form. This may be fixed in some log in modules.</div></td>
   <td width='60%' class='tablerow2'>{$form['login_user_id']}</td>
 </tr>
  <!--<tr>
   <td width='40%' class='tablerow1'><strong>Log In Has Settings</strong><div class='desctext'>If 'yes', both acp.php and conf.php file must be present and writeable by IPB (see diagnostics to check).</div></td>
   <td width='60%' class='tablerow2'>{$form['login_settings']}</td>
 </tr>-->
EOF;
//startif
if ( $form['login_safemode'] != '' )
{
$IPBHTML .= <<<EOF
<tr>
   <td width='40%' class='tablerow1'><strong>Enable Safemode?</strong><div class='desctext'>Cannot be deleted or edited by user</div></td>
   <td width='60%' class='tablerow2'>{$form['login_safemode']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Flag as installed?</strong><div class='desctext'>Cannot be deleted or edited by user</div></td>
   <td width='60%' class='tablerow2'>{$form['login_installed']}</td>
 </tr>
EOF;
}//endif
$IPBHTML .= <<<EOF
 </table>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='$button' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOGIN: row
//===========================================================================
function login_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'><img src='{$this->ipsclass->skin_acp_url}/images/lock_close.gif' border='0' alt='*' class='ipd' /></td>
 <td class='tablerow1'><strong>{$data['login_title']}</strong><div class='desctext'>{$data['login_description']}</div></td>
 <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$data['_installed_img']}' border='0' alt='YN' class='ipd' /></td>
 <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$data['_enabled_img']}' border='0' alt='YN' class='ipd' /></td>
 <td class='tablerow1'><img id="menu{$data['login_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$data['login_id']}",
  new Array(
EOF;
//startif
if ( ($data['login_safemode'] AND IN_DEV) or $data['login_installed'] )
{
$IPBHTML .= <<<EOF
			 img_edit   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=login_edit_details&amp;login_id={$data['login_id']}'>Edit Details...</a>",
EOF;
}//endif
//startif
if ( $data['login_installed'] != 1 )
{
$IPBHTML .= <<<EOF
			 img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=login_install&amp;login_id={$data['login_id']}'>Install...</a>",
EOF;
}//endif
//startif
if ( $data['login_installed'] == 1 ) // NOT USED??
{
//$IPBHTML .= <<<EOF
//			 img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=login_edit_settings&amp;login_id={$data['login_id']}'>Edit Settings...</a>",
// 			 img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=login_export&amp;login_id={$data['login_id']}'>Export...</a>",
//EOF;
}//endif
$IPBHTML .= <<<EOF
			 img_view   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=login_diagnostics&amp;login_id={$data['login_id']}'>Diagnostics...</a>"
  			 ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// PORTAL: Overview
//===========================================================================
function portal_pop_overview( $title, $content ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->ipsclass->acp_lang['portal_pop_tags']} {$title}</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='30%'>{$this->ipsclass->acp_lang['portal_pop_name']}</td>
  <td class='tablesubheader' width='70%'>{$this->ipsclass->acp_lang['portal_pop_desc']}</td>
 </tr>
 $content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// PORTAL: row
//===========================================================================
function portal_pop_row( $tag, $desc ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>&lt;!--::<strong>{$tag}</strong>::--&gt;</td>
 <td class='tablerow1'><div class='desctext'>{$desc}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// PORTAL: Overview
//===========================================================================
function portal_overview( $content ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->ipsclass->acp_lang['portal_main_title']}</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
  <td class='tablesubheader' width='95%'>{$this->ipsclass->acp_lang['portal_main_key']}</td>
  <td class='tablesubheader' width='5%'>&nbsp;</td>
 </tr>
 $content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// PORTAL: row
//===========================================================================
function portal_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'><img src='{$this->ipsclass->skin_acp_url}/images/menu.png' border='0' alt='Options' class='ipd' /></td>
 <td class='tablerow1'><strong>{$data['pc_title']}</strong><div class='desctext'>{$data['pc_desc']}</td>
 <td class='tablerow1'><img id="menu{$data['pc_key']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$data['pc_key']}",
  new Array(
EOF;
//startif
if ( $data['pc_settings_keyword'] )
{
$IPBHTML .= <<<EOF
			img_edit   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=portal_settings&amp;pc_key={$data['pc_key']}'>{$this->ipsclass->acp_lang['portal_row_menu_settings']}</a>",
EOF;
}//endif
$IPBHTML .= <<<EOF
  			 img_export   + " <a href='#'  onclick=\"menu_action_close(); pop_win('{$this->ipsclass->form_code}&amp;code=portal_viewtags&amp;pc_key={$data['pc_key']}', '{$data['pc_key']}', 400,200)\">{$this->ipsclass->acp_lang['portal_row_menu_view_tags']}</a>"
  			 ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}


}

?>