<?php

class cp_skin_admin {

var $ipsclass;

//===========================================================================
// Index
//===========================================================================
function acp_last_logins_detail( $log ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Log in Detail</div>
	<table width='100%' cellpadding='4' cellspacing='0'>
	<tr>
		<td class='tablerow2'>
			<fieldset>
				<legend><strong>Basics</strong></legend>
				<table width='100%' cellpadding='4' cellspacing='0'>
				 <tr>
					<td width='30%' class='tablerow1'>Username</td>
					<td width='70%' class='tablerow1'>{$log['admin_username']}</td>
				</tr>
				<tr>
					<td class='tablerow1'>IP Address</td>
					<td class='tablerow1'>{$log['admin_ip_address']}</td>
				</tr>
				<tr>
					<td class='tablerow1'>Log in Time</td>
					<td class='tablerow1'>{$log['_admin_time']}</td>
				</tr>
				<tr>
					<td class='tablerow1'>Success</td>
					<td class='tablerow1'><img src='{$this->ipsclass->skin_acp_url}/images/{$log['_admin_img']}' border='0' alt='-' class='ipd' /></td>
				</tr>
				</table>
			</fieldset>
		<br />
		<fieldset>
			<legend><strong>POST Data (Form Data)</strong></legend>
			<table width='100%' cellpadding='4' cellspacing='0'>
EOF;
		if ( is_array( $log['_admin_post_details']['post'] ) AND count( $log['_admin_post_details']['post'] ) )
		{
			foreach( $log['_admin_post_details']['post'] as $k => $v )
			{
				$IPBHTML .= "<tr>
								<td width='30%' class='tablerow1'>{$k}</td>
								<td width='70%' class='tablerow1'>{$v}</td>
							</tr>";
			}
		}
$IPBHTML .= <<<EOF
			</table>
		</fieldset>
		<br />
		<fieldset>
			<legend><strong>GET Data (URL Data)</strong></legend>
			<table width='100%' cellpadding='4' cellspacing='0'>
EOF;
		if ( is_array( $log['_admin_post_details']['get'] ) AND count( $log['_admin_post_details']['get'] ) )
		{
			foreach( $log['_admin_post_details']['get'] as $k => $v )
			{
				$IPBHTML .= "<tr>
								<td width='30%' class='tablerow1'>{$k}</td>
								<td width='70%' class='tablerow1'>{$v}</td>
							</tr>";
			}
		}
$IPBHTML .= <<<EOF
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

//===========================================================================
// Index
//===========================================================================
function acp_last_logins_row( $r ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1' width='1' valign='middle'>
	<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/user.png' border='0' alt='-' class='ipd' />
 </td>
 <td class='tablerow1'> <strong>{$r['admin_username']}</strong></td>
 <td class='tablerow2'><div class='desctext'>IP: {$r['admin_ip_address']}</div></td>
 <td class='tablerow2' align='center'>{$r['_admin_time']}</td>
 <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$r['_admin_img']}' border='0' alt='-' class='ipd' /></td>
 <td class='tablerow1' width='1' valign='middle'>
 	<a href='#' onclick="return ipsclass.pop_up_window('{$this->ipsclass->base_url}&section=admin&amp;act=loginlog&amp;code=view_detail&amp;detail={$r['admin_id']}', 400, 400)" title='View Details'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/view.png' border='0' alt='-' class='ipd' /></a>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_last_logins_wrapper($content, $links) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>ACP Log in Attempts</div>
 <table width='100%' cellpadding='4' cellspacing='0'>
 <tr>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
  <td class='tablesubheader' width='30%'>Name</td>
  <td class='tablesubheader' width='20%'>IP Address</td>
  <td class='tablesubheader' width='44%' align='center'>Date</td>
  <td class='tablesubheader' width='5%' align='center'>Status</td>
  <td class='tablesubheader' width='5%' align='center'>Log</td>
 </tr>
 $content
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
// RSS
//===========================================================================
function acp_perms_add_admin_form() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_xhr_findnames.js'></script>
<form id='postingform' style='display:block' action="{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=acpperms-member-add-complete" method="post" name="REPLIER">
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div id='ipb-get-members' style='border:1px solid #000; background:#FFF; padding:2px;position:absolute;width:210px;display:none;z-index:100'></div>
<div class='tableborder'>
 <div class='tableheaderalt'>ACP Permissions: Find an administrator</div>
 <div class='tablesubheader'>&nbsp;</div>
  <table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
  <tr>
    <td class='tablerow1'  width='50%'  valign='middle'><b>Enter an administrator's display name</b><div style='color:gray'>An administrator is someone that has access to the ACP via their primary or secondary groups and is NOT in the Root Admin group.</div></td>
    <td class='tablerow2'  width='50%'  valign='middle'><input type="text" id='entered_name' name="entered_name" size="30" autocomplete='off' style='width:210px' value="" tabindex="1" /></td>
  </tr>
  <tr>
  <td align='center' class='tablesubheader' colspan='2' ><input type='submit' value='Proceed &gt;&gt;' class='realbutton' accesskey='s'></td>
  </tr>
  </table>
</div>
<script type="text/javascript">
	// INIT find names
	init_js( 'postingform', 'entered_name');
	// Run main loop
	var tmp = setTimeout( 'main_loop()', 10 );
</script>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}



//===========================================================================
// RSS
//===========================================================================
function acp_perms_overview($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_perms.js'></script>
<div id='perms-wrapper' style='display:none;width:800px;text-align:left'>
<div class="tableborder" style='border-width:2px'>
 <div class='tableheaderalt' id='perms-drag' title='{$this->ipsclass->lang['myass_drag']}'>
  <div style='float:right'><a href='#' onclick='document.getElementById("perms-wrapper").style.display="none"'>[X]</a></div>
  <div>ACP Restriction Permissions</div>
 </div>
  <div id='perms-content'></div>
  <div id='perms-status' class='tablerow4' style='height:30px;padding:0px'><div style='width:100px;float:left' id='perms-status-msg'></div></div>
 </div>
</div>

<div class='tableborder'>
 <div class='tableheaderalt'>ACP Restriction Permissions</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='40%'>Member</td>
  <td class='tablesubheader' width='20%' align='center'>Primary Group</td>
  <td class='tablesubheader' width='20%' align='center'>Updated</td>
  <td class='tablesubheader' width='5%'><img id="menumainone" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
 </tr>
 $content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
<br />
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=accperms-xml-import' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<input type='hidden' name='MAX_FILE_SIZE' value='10000000000'>
<div class='tableborder'>
 <div class='tableheaderalt'>Import an XML Permissions file</div>
  <table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
  <tr>
    <td class='tablerow1'  width='50%'  valign='middle'><b>Upload XML permissions file from your computer</b><div style='color:gray'>Duplicate entries will not be overwritten. The file must begin with 'ipb_' and end with either '.xml' or '.xml.gz'</div></td>
    <td class='tablerow2'  width='50%'  valign='middle'><input class='textinput' type='file'  size='30' name='FILE_UPLOAD'></td>
  </tr>
  <tr>
    <td class='tablerow1'  width='50%'  valign='middle'><b><u>OR</u> enter the filename of the XML permissions file</b><div style='color:gray'>The file must be uploaded into the forum's root folder</div></td>
    <td class='tablerow2'  width='50%'  valign='middle'><input type='text' name='file_location' value='ipb_acpperms.xml' size='30' class='textinput'></td>
  </tr>
  <tr>
  <td align='center' class='tablesubheader' colspan='2' ><input type='submit' value='Import XML settings Set' class='realbutton' accesskey='s'></td>
  </tr>
  </table>
</div>
</form>
 
EOF;
if ( IN_DEV )
{
$IPBHTML .= <<<EOF
<br />
<div class='tableborder'>
 <div class='tableheaderalt'>Developer Tools</div>
 <div class='tablerow1'><strong>Generate XML from source files</strong>
 	<div class='desctext'>Pick through /sources/action_admin for permission check rows and build an XML document ready for import</div>
 	<div align='right'><strong><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=acpperms-dev-source-to-xml'>RUN TOOL</a> &gt;&gt;</strong></div>
 </div>
 
 <div class='tablerow1'><strong>Generate Report: Uncompleted Files</strong>
 	<div class='desctext'>Pick through /sources/action_admin for files that don't have any permission rows</div>
 	<div align='right'><strong><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=acpperms-dev-report-missing'>RUN TOOL</a> &gt;&gt;</strong></div>
 </div>
 
 <div class='tablerow1'><strong>Generate Report: Language File</strong>
 	<div class='desctext'>Pick through /sources/action_admin and generate the language file basics</div>
 	<div align='right'><strong><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=acpperms-dev-report-language'>RUN TOOL</a> &gt;&gt;</strong></div>
 </div>
</div>
EOF;
}
$IPBHTML .= <<<EOF
<script type="text/javascript">
//<![CDATA[
  var permobj = new acpperms();
  menu_build_menu(
  "menumainone",
  new Array( 
  			 img_add + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=acpperms-member-add'>Find and add administrator...</a>"
           ) );
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// RSS
//===========================================================================
function acp_perms_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
   <img src='{$this->ipsclass->skin_acp_url}/images/lock_close.gif' border='0' alt='@' style='vertical-align:top' />
   <strong>{$data['members_display_name']}</strong>
 </td>
 <td class='tablerow2' align='center'>{$data['_group_name']}</td>
 <td class='tablerow2' align='center'>{$data['_date']}</td>
 <td class='tablerow1'><img id="menu{$data['id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$data['id']}",
  new Array(
  			img_delete   + " <a href='#' onclick='maincheckdelete(\"{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=accperms-member-remove&amp;mid={$data['id']}\");'>Remove all member's restrictions...</a>",
  			img_item   + " <a href='#' onclick='permobj.init(\"\",{$data['id']})'>Manage member's restrictions...</a>"
  		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_welcome($member=array()) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<div class='tablerow2'>
<input type='hidden' id='perms-perm-child-id' value=' ' />
ACP permission restrictions for <strong>{$member['members_display_name']}</strong>
<br />
<br />
Simply click on one of the tabs above to set the access level for all functions in this
tab. If you want to choose access on a per-function level, click on the relevant link
to be shown this function's options.
</div>
EOF;
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_tab_no_access($member=array()) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<div class='tablerow2'>
<input type='hidden' id='perms-perm-child-id' value='' />
ACP permission restritctions for <strong>{$member['members_display_name']}</strong>
<br />
<br />
Access to this tab has not yet been enabled. To enable individual access permissions for this tab
simply click the green cross icon next to the tab title.
<br />
<br />
You can then set individual permissions for each function which is grouped under this tab.
</div>
EOF;
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_tabs($onoff=array(), $member_id=0, $tabinit=array()) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
 <div style='height:36px;margin-left:1px'>
   <div class='{$onoff['content']}' style='padding:7px'>
   	<input type='hidden' id='tab_content' value='{$tabinit['content']}' />
   	<a href='#' id='href_content' onclick='permobj.init("content", $member_id)'>MANAGEMENT</a>
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_tick_small.png' id='img-content-tick' onclick="return permobj.save_tab('content', $member_id, 1 );" title='Allow Access to this tab' style='cursor:pointer' border='0' alt='Allow' class='img-boxed-off' />
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_cross_small.png' id='img-content-cross' onclick="return permobj.save_tab('content', $member_id, 0 );" title='Deny Access to this tab' style='cursor:pointer' border='0' alt='Deny' class='img-boxed-off' />
   </div>
   <div class='{$onoff['lookandfeel']}' style='padding:7px'>
   	<input type='hidden' id='tab_lookandfeel' value='{$tabinit['lookandfeel']}' />
   	<a href='#' id='href_lookandfeel' onclick='permobj.init("lookandfeel", $member_id)'>LOOK &amp; FEEL</a>
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_tick_small.png' id='img-lookandfeel-tick' onclick="return permobj.save_tab('lookandfeel', $member_id, 1 );" title='Allow Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_cross_small.png' id='img-lookandfeel-cross' onclick="return permobj.save_tab('lookandfeel', $member_id, 0 );" title='Deny Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   </div>
   <div class='{$onoff['tools']}' style='padding:7px'>
   	<input type='hidden' id='tab_tools' value='{$tabinit['tools']}' />
   	<a href='#' id='href_tools' onclick='permobj.init("tools", $member_id )'>TOOLS &amp; SETTINGS</a>
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_tick_small.png' id='img-tools-tick' onclick="return permobj.save_tab('tools', $member_id, 1 );" title='Allow Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_cross_small.png' id='img-tools-cross' onclick="return permobj.save_tab('tools', $member_id, 0 );" title='Deny Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   </div>
   <div class='{$onoff['components']}' style='padding:7px'>
   	<input type='hidden' id='tab_components' value='{$tabinit['components']}' />
   	<a href='#' id='href_components' onclick='permobj.init("components", $member_id)'>COMPONENTS</a>
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_tick_small.png' id='img-components-tick' onclick="return permobj.save_tab('components', $member_id, 1 );" title='Allow Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_cross_small.png' id='img-components-cross' onclick="return permobj.save_tab('components', $member_id, 0 );" title='Deny Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   </div>
   <div class='{$onoff['admin']}' style='padding:7px'>
   	<input type='hidden' id='tab_admin' value='{$tabinit['admin']}' />
   	<a href='#' id='href_admin' onclick='permobj.init("admin", $member_id)'>ADMIN</a>
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_tick_small.png' id='img-admin-tick' onclick="return permobj.save_tab('admin', $member_id, 1 );" title='Allow Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_cross_small.png' id='img-admin-cross' onclick="return permobj.save_tab('admin', $member_id, 0 );" title='Deny Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   </div>
   <div class='{$onoff['help']}' style='padding:7px'>
   	<input type='hidden' id='tab_help' value='{$tabinit['help']}' />
   	<a href='#' id='href_help' onclick='permobj.init("help", $member_id)'>HELP & SUPPORT</a>
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_tick_small.png' id='img-help-tick' onclick="return permobj.save_tab('help', $member_id, 1 );" title='Allow Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   	<img src='{$this->ipsclass->skin_acp_url}/images/aff_cross_small.png' id='img-help-cross' onclick="return permobj.save_tab('help', $member_id, 0 );" title='Deny Access to this tab' style='cursor:pointer' border='0' alt='*' class='img-boxed-off' />
   </div>   
 </div>
EOF;
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_wrap($tabs, $content) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<div class='body-bg'>
 {$tabs}
</div>

<div style='background-color:#FFF;clear: both;'>
$content
</div>
EOF;
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_global_main_wrap($content, $class_name, $perm_value, $main_img_classes) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<div id='perms-child-wrap'>
	<table width='100%' cellpadding='0' cellspacing='0'>
		<tr>
 			<td width='1%' nowrap='nowrap' id='td_mainbit_a' class='{$class_name}'>
 				<div style='padding:6px'>
 					<img src='{$this->ipsclass->skin_acp_url}/images/mainmenu.png' border='0' alt='*' class='ipd' />
 				</div>
 			</td>
 			<td width='60%' id='td_mainbit_b' class='{$class_name}'>
 				<div style='padding:6px'>
 					<input type='hidden' id='pb_mainbit' name='mainbit' value='{$perm_value}' />
 					<strong>ALLOW ACCESS TO THIS FUNCTION</strong>
 				</div>
 			</td>
 			<td width='5%' id='td_mainbit_c' class='{$class_name}'>
 				<div style='cursor:pointer' onclick='return permobj.clicked("mainbit", 1 );'>
 					<img src='{$this->ipsclass->skin_acp_url}/images/aff_tick.png' border='0' alt='*' class='{$main_img_classes['tick']}' />
 				</div>
 			</td>
 			<td width='5%' id='td_mainbit_d' class='{$class_name}'>
 				<div style='cursor:pointer' onclick='return permobj.clicked("mainbit", 0 );'>
 					<img src='{$this->ipsclass->skin_acp_url}/images/aff_cross.png' border='0' alt='*' class='{$main_img_classes['cross']}' />
 				</div>
 			</td>
		</tr>
		<tr>
 			<td colspan='4' style='padding:1px;height:12px'>
 				<!-- ta -->
 			</td>
		</tr>
{$content}
EOF;
if ( $perm_value != 0 )
{
$IPBHTML .= <<<EOF
<tr>
 <td colspan='4' style='height:25px' align='right'>
  <div style='width:80px;float:right;text-align:center;cursor:pointer' onclick='return permobj.save_bits(1);' class='input-ok-content' id='perms-save-box'>Save</div>
  <div style='width:80px;float:right;text-align:center;cursor:pointer' onclick='return permobj.undo_bits();' class='input-ok-content' id='perms-undo-box'>UNDO</div>
 </td>
</tr>
EOF;
}
$IPBHTML .= <<<EOF
	</table>
</div>
<div class='desctext' style='padding:4px'>
	When "ALLOW ACCESS TO THIS FUNCTION" is enabled you may set the individual access permissions for the sub-features of this particular function. Otherwise, changes to the individual access permission will have no effect.
</div>
EOF;
//--endhtml--//
return $IPBHTML;
}



function acp_xml_global_main_nocomponents($class_name) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div id='perms-child-wrap'>
<input type='hidden' id='perms-perm-child-id' value='' />
<table width='100%' cellpadding='0' cellspacing='0'>
<tr>
 <td width='100%' nowrap='nowrap' id='td_mainbit_a' class='{$class_name}' colspan='4'>There are either no components enabled, or none of the components enabled have permission settings.</td>
</tr>
<tr>
 <td colspan='4' style='padding:1px;height:12px'><!-- ta --></td>
</tr>
$content
</table>
</div>
EOF;
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_global_main_row($lang_bit, $perm_bit, $perm_value, $class_name, $img_classes) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<tr>
 <td width='1%' nowrap='nowrap' id='td_{$perm_bit}_a' class='{$class_name}'><div style='padding:6px'><img src='{$this->ipsclass->skin_acp_url}/images/content.png' border='0' alt='*' class='ipd' /></div></td>
 <td width='60%' id='td_{$perm_bit}_b' class='{$class_name}'><div style='padding:6px'><input type='hidden' id='pb_{$perm_bit}' name='{$perm_bit}' value='{$perm_value}' /><strong>{$lang_bit}</strong></div></td>
EOF;
if ( $class_name != 'perms-gray' )
{
$IPBHTML .= <<<EOF
 <td width='5%' id='td_{$perm_bit}_c' class='{$class_name}'><div style='cursor:pointer' onclick='return permobj.clicked("$perm_bit", 1 );'><img src='{$this->ipsclass->skin_acp_url}/images/aff_tick.png' id='img-{$perm_bit}-tick' border='0' alt='*' class='{$img_classes['tick']}' /></div></td>
 <td width='5%' id='td_{$perm_bit}_d' class='{$class_name}'><div style='cursor:pointer' onclick='return permobj.clicked("$perm_bit", 0 );'><img src='{$this->ipsclass->skin_acp_url}/images/aff_cross.png' id='img-{$perm_bit}-cross' border='0' alt='*' class='{$img_classes['cross']}' /></div></td>
EOF;
}
else
{
$IPBHTML .= <<<EOF
 <td colspan='2' class='{$class_name}'>&nbsp;</td>
EOF;
}
$IPBHTML .= <<<EOF
</tr>
<tr>
 <td colspan='4' style='padding:1px;height:4px'><!-- ta --></td>
</tr>
EOF;
//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_global_sidebar_wrap($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div>
$content
</div>
EOF;
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_global_sidebar_link($lang_bit, $member_id, $perm_main, $perm_child, $allow=0, $img_classes=array()) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<div class='tablerow1' onclick='permobj.init("{$perm_main}",{$member_id}, "{$perm_child}")' style='cursor:pointer'>
 <img src='{$this->ipsclass->skin_acp_url}/images/folder.gif' border='0' alt='*' class='ipd' />
 <img src='{$this->ipsclass->skin_acp_url}/images/aff_tick_small.png' id='img-content-tick' onclick='return permobj.save_group("{$perm_main}","{$perm_child}", $member_id, 1 );' title='Allow Access to ALL functions of this feature' style='cursor:pointer' border='0' alt='Allow' class='img-boxed-off' />
 <img src='{$this->ipsclass->skin_acp_url}/images/aff_cross_small.png' id='img-content-cross' onclick='return permobj.save_group("{$perm_main}","{$perm_child}", $member_id, 0 );' title='Deny Access to ALL functions of this feature' style='cursor:pointer' border='0' alt='Deny' class='img-boxed-off' />

EOF;
if ( $allow != 0 )
{
$IPBHTML .= <<<EOF
 <span style='color:#000;font-weight:bold'>{$lang_bit}</span>
EOF;
}
else
{
$IPBHTML .= <<<EOF
 <span style='color:#777;font-weight:bold'>{$lang_bit}</span>
EOF;
}
$IPBHTML .= <<<EOF
</div>
EOF;
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_global_sidebar_link_chosen($lang_bit, $member_id, $perm_main, $perm_child, $allow=0, $img_classes=array()) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<div class='tablerow3' onclick='permobj.init("{$perm_main}",{$member_id}, "{$perm_child}")' style='cursor:pointer'>
  <input type='hidden' id='perms-perm-child-id' value='$perm_child' />
  <img src='{$this->ipsclass->skin_acp_url}/images/folder.gif' border='0' alt='*' class='ipd' />
   <img src='{$this->ipsclass->skin_acp_url}/images/aff_tick_small.png' id='img-content-tick' onclick='return permobj.save_group("{$perm_main}","{$perm_child}", $member_id, 1 );' title='Allow Access to ALL functions of this feature' style='cursor:pointer' border='0' alt='Allow' class='img-boxed-off' />
   <img src='{$this->ipsclass->skin_acp_url}/images/aff_cross_small.png' id='img-content-cross' onclick='return permobj.save_group("{$perm_main}","{$perm_child}", $member_id, 0 );' title='Deny Access to ALL functions of this feature' style='cursor:pointer' border='0' alt='Deny' class='img-boxed-off' />

EOF;
if ( $allow != 0 )
{
$IPBHTML .= <<<EOF
 <span style='color:#000;font-weight:bold'>{$lang_bit}</span>
EOF;
}
else
{
$IPBHTML .= <<<EOF
 <span style='color:#777;font-weight:bold'>{$lang_bit}</span>
EOF;
}
$IPBHTML .= <<<EOF
</div>
EOF;
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// ACP Perms
//===========================================================================
function acp_xml_main_wrap($sidebar="", $content="") {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<div id='perms-main-wrap'>
<table cellspacing='0' cellpadding='0' width='100%'>
<tr>
 <td width='220' valign='top' class='tablerow1' style='padding:0px;border:0px'>{$sidebar}</td>
 <td width='*' valign='top' class='tablerow3'>{$content}</td>
</tr>
</table>
</div>
EOF;
//--endhtml--//
return $IPBHTML;
}

}


?>