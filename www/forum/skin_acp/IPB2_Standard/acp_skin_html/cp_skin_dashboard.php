<?php

class cp_skin_dashboard {

var $ipsclass;


//===========================================================================
// Index http://www.invisionboard.com/acp-ipb/getnews.php
//===========================================================================
function acp_main_template( $content, $f_dd, $g_dd, $urls=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div style='font-size:30px; padding-left:7px; letter-spacing:-2px; border-bottom:1px solid #EDEDED'>
 Welcome to Invision Power Board
</div>
<br />
<script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_xhr_findnames.js'></script>
<div id='ipb-get-members' style='border:1px solid #000; background:#FFF; padding:2px;position:absolute;width:120px;display:none;z-index:100'></div>
<!--in_dev_notes-->
<!--in_dev_check-->
<table border='0' width='100%' cellpadding='0' cellspacing='4'>
<tr>
 <td valign='top' width='75%'>
	<table border='0' width='100%' cellpadding='0' cellspacing='0'>
	<tr>
	 <td>
		<div class='homepage_pane_border'>
		 <div class='homepage_section'>Common Actions</div>
		 <table width='100%' cellspacing='0' cellpadding='4' id='common_actions'>
			 <tr>
			  <td width='33%' valign='top'>
				<div><a href='{$this->ipsclass->base_url}&section=content&act=mem&code=search' title='Manage Members'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/members.png' border='0' alt='Manage Members' /> Manage Members</a></div>
				<div><a href='{$this->ipsclass->base_url}&section=content&act=mtools&code=mod' title='Process Validating Members'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/validating.png' border='0' alt='Process Validating Members' /> Validating Members</a></div>
				<div><a href='{$this->ipsclass->base_url}&section=content&act=forum' title='Manage Forums'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/forums.png' border='0' alt='Manage Forums' /> Manage Forums</a></div>
			</td>
			<td width='33%' valign='top'>
				<div><a href='{$this->ipsclass->base_url}&section=tools' title='Edit System Settings'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/settings.png' border='0' alt='Edit System Settings' /> Edit System Settings</a></div>
				<div><a href='{$this->ipsclass->base_url}&section=lookandfeel&act=sets' title='Skin Manager'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/skins.png' border='0' alt='Skin Manager' /> Skin Manager</a></div>
				<div><a href='{$this->ipsclass->base_url}&section=tools&act=postoffice' title='Bulk Mailer'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/bulkmail.png' border='0' alt='Bulk Mailer' /> Bulk Mailer</a></div>
			</td>
			<td width='33%' valign='top'>
				<div><a href='{$this->ipsclass->base_url}&section=content&act=group' title='Manage Groups'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/groups.png' border='0' alt='Manage Groups' /> Manage Groups</a></div>
				<div><a href='{$this->ipsclass->base_url}&section=lookandfeel&act=lang' title='Language Manager'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/languages.png' border='0' alt='Language Manager' /> Language Manager</a></div>
				<div><a href='{$this->ipsclass->base_url}&section=lookandfeel&act=emoticons&code=emo' title='Emoticon Manager'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/emos.png' border='0' alt='Emoticon Manager' /> Emoticon Manager</a></div>
			 </tr>
		 </table>
		</div>
	</td>
	</tr>
	<tr>
	 <td>&nbsp;</td>
	</tr>
	<tr>
	 <td>
		<div class='homepage_pane_border'>
		 <div class='homepage_section'>Tasks and Statistics</div>
		 <table width='100%' cellspacing='0' cellpadding='4'>
			 <tr>
			  <td width='50%' valign='top'>
			  	{$content['stats']}
			  </td>
			  <td width='50%' valign='top'>
				<div class='homepage_border'>
				 <div class='homepage_sub_header'>Quick Actions</div>
				 <table width='100%' cellpadding='4' cellspacing='0'>
				 <tr>
				  <td class='homepage_sub_row'>
					<strong>Find/Edit Member</strong> <span class='desctext' title='Enter a partial name to search for'>?</span>
					<br /><form name='DOIT' id='DOIT' action='{$this->ipsclass->adskin->base_url}&section=content&act=mem&code=searchresults&searchtype=normal&' method='post'><input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' /><input type='text' size='33' class='textinput' id='members_display_name' name='members_display_name' value='' /> <input type='submit' value='Go...' class='realbutton' onclick='edit_member()' /></form>
				  </td>
				 </tr>
				 <tr>
				  <td class='homepage_sub_row'>
					<strong>Add New Member</strong> <span class='desctext' title='Enter a name and group'>?</span>
				    <br /><form name='newmem' id='newmem' action='{$this->ipsclass->adskin->base_url}&section=content&act=mem&code=add' method='post'><input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' /><input type='text' size='17' class='textinput' name='name' value='' /> <select name='mgroup'>{$g_dd}</select> <input type='submit' value='Go...' class='realbutton' /></form></td>
				 </tr>
				 <tr>
				  <td class='homepage_sub_row'>
					<strong>Edit a Forum</strong> <span class='desctext' title='Select the forum to edit'>?</span>
				    <br /><form name='newmem' id='newmem' action='{$this->ipsclass->adskin->base_url}&section=content&act=forum&code=edit' method='post'><input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' /><select name='f'>{$f_dd}</select> <input type='submit' value='Go...' class='realbutton' /></form></td>
				 </tr>
				 <tr>
				  <td class='homepage_sub_row'>
					<strong>IP Address Search</strong> <span class='desctext' title='Lookup info on an IP address'>?</span>
				  	<br /><form name='ipform' id='ipform' action='{$this->ipsclass->adskin->base_url}&section=content&act=mtools&code=learnip' method='post'><input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' /><input type='text' size='33' class='textinput' name='ip' value='' /> <input type='submit' value='Go...' class='realbutton' /></form></td>
				 </tr>
				 <tr>
				  <td class='homepage_sub_row'>
					<strong>Search System Settings</strong> <span class='desctext' title='Search for a setting to edit'>?</span>
				  	<br /><form name='settingform' id='settingform' action='{$this->ipsclass->adskin->base_url}&section=tools&act=op&code=setting_view' method='post'><input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' /><input type='text' size='33' class='textinput' name='search' value='' /> <input type='submit' value='Go...' class='realbutton' /></form></td>
				 </tr>
				 </table>
				</div>
		  	</td>
		   </tr>
	    </table>
	   </div>
	 </td>
	</tr>
	<tr>
	 <td>&nbsp;</td>
	</tr>
	<tr>
	 <td>
		<div class='homepage_pane_border'>
		 <div class='homepage_section'>Communication</div>
		 <table width='100%' cellspacing='0' cellpadding='4'>
			 <tr>
			  <td valign='top' width='50%'>
			  	<div class='homepage_border'>
					<div class='homepage_sub_header'>ACP Notes</div>
					<br />{$content['ad_notes']}<br />
				</div>
			  </td>
			  <td width='50%' valign='top'>
			  	{$content['acp_online']}
			  </td>
			 </tr>
		 </table>
		</div>
	 </td>
	</tr>
	
	</table>
 </td>
 <td valign='top' width='25%'>
	<div id='acp-update-wrapper' style='display:none'>
		<!-- Security Update -->
		<div class='homepage_pane_warning_border' id='acp-update-security' style='display:none'>
		 <div class='homepage_section_warning'>Security Update Available</div>
			<div style='font-size:12px;padding:4px; text-align:center'>
				<p>
					<strong><span id='acp-version-security'></span></strong> Security Update Available!
				</p>
				<input type='button' onclick='VU_moreinfo()' value=' More Information ' /> <input type='button' onclick='VU_reset()' value=' Reset Warning ' />
			</div>
		</div>
		<!-- Normal Version Upgrade -->
		<div class='homepage_pane_border' id='acp-update-update' style='display:none'>
		 <div class='homepage_section'>Update Available</div>
			<div style='font-size:12px;padding:4px; text-align:center'>
				<p>
					<strong><span id='acp-version-update'></span></strong> Update Available Now!
				</p>
				<input type='button' onclick='VU_moreinfo()' value=' More Information ' /> <input type='button' onclick='VU_reset()' value=' Reset Notice ' />
			</div>
		</div>
		<!-- Normal Version Upgrade -->
		<div class='homepage_pane_border' id='acp-update-normal' style='display:none'>
		 <div class='homepage_section'>New Version Available</div>
			<div style='font-size:12px;padding:4px; text-align:center'>
				<p>
					Version <strong><span id='acp-version-normal'></span></strong> Available Now!
				</p>
				<input type='button' onclick='VU_moreinfo()' value=' More Information ' />
			</div>
		</div>
		<br />
	</div>
	<!--warninginstaller-->
	<!--warningupgrade-->
	<!--warningskin-->
	<!--warningftext-->
	<!--phpversioncheck-->
	<!--boardoffline-->
	{$content['validating']}
	<div class='homepage_pane_border' id='acp-news-outer'>
	 <div class='homepage_section'>Latest IPS News</div>
		<div>
			<div id='acp-news-wrapper'>
			</div>
		</div>
	</div>
	<br />
	<div class='homepage_pane_border' id='acp-blog-outer'>
	 <div class='homepage_section'>Latest IPS Blogs</div>
		<div id='acp-blog-wrapper'>
		</div>
	</div>
	<br />
	<div class='homepage_pane_border'>
	 <div class='homepage_section'>IP.Board Bulletin</div>
		<div id='keith-is-not-hidden'>
		</div>
	</div>
	<!--acplogins-->
 </td>
</tr>
</table>
<!-- HIDDEN "STOP" DIV -->
<div id='acp-update-stop-wrapper' style='display:none;width:450px;'>
	<div class='homepage_pane_warning_border' style='height:130px'>
		<div class='homepage_section_warning'>NOTICE: RESET UPDATE NOTICE</div>
		<div style='float:left'>
			<img src='{$this->ipsclass->skin_acp_url}/images/update_icons/update_warning.png' border='0' />
		</div>
		<div style='padding:4px;font-size:12px'>
			Only reset this notice if you <strong>have already performed the upgrade</strong>.
			<p>
				Resetting this notice <strong>will not</strong> perform the upgrade for you.
			</p>
			<p style='text-align: right'>
				<input type='button' value=' CONTINUE ' onclick='VR_continue()' style='background-color:lightgreen;font-size:14px;' />
				<input type='button' value=' CANCEL ' onclick='VR_cancel()' style='background-color:pink;font-size:14px;' />
			</p>
		</div>
	</div>
</div>
<!-- / HIDDEN "STOP" DIV -->
<!-- HIDDEN "INFORMATION" DIV -->
<div id='acp-update-info-wrapper' style='display:none;width:450px;'>
	<div class='homepage_pane_border' style='height:130px'>
		<div class='homepage_section'>NOTICE: UPDATE INFORMATION</div>
		<div style='float:left'>
			<img src='{$this->ipsclass->skin_acp_url}/images/update_icons/update_info.png' border='0' />
		</div>
		<div style='padding:4px;font-size:12px'>
			<p>
				To download the latest update, please log into the <strong>IPS Client Center</strong> and navigate to <strong>Your Downloads</strong>
			</p>
			<p style='text-align: right;'>
				<input type='button' value=' VISIT IPS CLIENT CENTER ' onclick='VU_continue()' style='background-color:lightgreen;font-size:14px;width:190px;' />
				<input type='button' value=' CLOSE ' onclick='VU_cancel()' style='background-color:pink;font-size:14px;width:70px;' />
			</p>
		</div>
	</div>
</div>
<!-- / HIDDEN "INFORMATION" DIV -->

<script type='text/javascript'>

var infoCenterDiv = '';

/* Upgrade DOWNLOAD / RESET */
function VU_reset()
{
	centerDiv = new center_div();
	centerDiv.divname = 'acp-update-stop-wrapper';
	centerDiv.move_div();
}
function VU_moreinfo()
{
	if( !infoCenterDiv )
	{
		infoCenterDiv = new center_div();
		infoCenterDiv.divname = 'acp-update-info-wrapper';
	}

	infoCenterDiv.move_div();
}
function VU_cancel()
{
	//document.getElementById( 'acp-update-info-wrapper' ).style.display = 'none';
	infoCenterDiv.hide_div();
}
function VU_continue()
{
	ipsclass.pop_up_window( IPSSERVER_download_link, 800, 600 );
	document.getElementById( 'acp-update-info-wrapper' ).style.display = 'none';
}

/* Warning CONTINUE / CANCEL */
function VR_continue()
{
	ipsclass.location_jump( ipb_var_base_url + "&amp;section=dashboard&amp;reset_security_flag=1&amp;new_build=" + IPSSERVER_download_ve + "&amp;new_reason=" + IPSSERVER_download_vt, 1 );
}
function VR_cancel()
{
	document.getElementById( 'acp-update-stop-wrapper' ).style.display = 'none';
}

/* Edit member box */
function edit_member()
{
	if (document.getElementById('DOIT').members_display_name.value == "")
	{
		alert("You must enter a username!");
		return false;
	}
}

/* INIT find names */
init_js( 'DOIT', 'members_display_name');

/* Run main loop */
var tmp = setTimeout( 'main_loop()', 10 );
//]]>
</script>
EOF;

if ( IN_DEV )
{
$IPBHTML .= <<<EOF
<br />
<div class='tableborder'>
 <div class='tableheaderalt'>DEV Installer XML Exports</div>
 <div class='tablepad'>
	<a href='{$this->ipsclass->base_url}&amp;section=admin&amp;act=components&amp;code=master_xml_export'>Components</a>
	&middot; <a href='{$this->ipsclass->base_url}&amp;section=tools&amp;act=loginauth&amp;code=master_xml_export'>Log In Modules</a>
	&middot; <a href='{$this->ipsclass->base_url}&amp;section=content&amp;act=group&amp;code=master_xml_export'>Groups</a>
	&middot; <a href='{$this->ipsclass->base_url}&amp;section=content&amp;act=attach&amp;code=master_xml_export'>Attachments</a>
	&middot; <a href='{$this->ipsclass->base_url}&amp;section=lookandfeel&amp;act=sets&amp;code=master_xml_export'>Skins</a>
	&middot; <a href='{$this->ipsclass->base_url}&amp;section=tools&amp;act=task&amp;code=master_xml_export'>Tasks</a>
	&middot; <a href='{$this->ipsclass->base_url}&amp;section=tools&amp;act=help&amp;code=master_xml_export'>FAQ</a>
	&middot; <a href='{$this->ipsclass->base_url}&amp;section=content&amp;act=bbcode&amp;code=bbcode_export'>BBCode</a>
 </div>
</div>
EOF;
}

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Index
//===========================================================================
function acp_validating_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='homepage_pane_border'>
	<div class='homepage_section'>Admin Validation Queue</div>
	{$content}
	<div align='right'>
	   <a href='{$this->ipsclass->base_url}&amp;section=content&amp;act=mtools&amp;code=mod' style='text-decoration:none'>MORE &raquo;</a>
	 </div>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_validating_block( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='homepage_sub_row_3'>
 <div style='float:right;'>
  <a href='{$this->ipsclass->base_url}&section=content&act=mtools&code=domod&_admin_auth_key={$this->ipsclass->_admin_auth_key}&mid_{$data['member_id']}=1&type=approve'><img src='{$this->ipsclass->skin_acp_url}/images/aff_tick.png' alt='Yes' class='ipd' /></a>&nbsp;
  <a href='{$this->ipsclass->base_url}&section=content&act=mtools&code=domod&_admin_auth_key={$this->ipsclass->_admin_auth_key}&mid_{$data['member_id']}=1&type=delete'><img src='{$this->ipsclass->skin_acp_url}/images/aff_cross.png' alt='No' class='ipd' /></a>
 </div>
 <div>
  <strong><a href='{$this->ipsclass->vars['board_url']}/index.php?showuser={$data['member_id']}' target='_blank'>{$data['members_display_name']}</a></strong>{$data['_coppa']}<br />
  &nbsp;&nbsp;{$data['email']}</a><br />
  <div class='desctext'>&nbsp;&nbsp;IP: <a href='{$this->ipsclass->base_url}&section=content&act=mtools&code=learnip&ip={$data['ip_address']}'>{$data['ip_address']}</a></div>
  <div class='desctext'>&nbsp;&nbsp;Registered {$data['_entry']}</div>
 </div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function warning_converter() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	We recommend you remove the Converter System
   	from your server for security.
   	<br />Simply remove <b>convert/index.php</b> from your installation to remove this message.
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_notes($notes) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div align='center'>
<form action='{$this->ipsclass->base_url}&amp;section=dashboard&amp;act=dashboard&amp;save=1' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<textarea name='notes' style='background-color:#F9FFA2;border:1px solid #CCC;width:95%;font-family:verdana;font-size:10px' rows='8' cols='25'>{$notes}</textarea>
<div><br /><input type='submit' value='Save Admin Notes' class='realbutton' /></div>
</form>
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
 <td class='homepage_sub_row_3' width='1' valign='middle'>
	<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/user.png' border='0' alt='-' class='ipd' />
 </td>
 <td class='homepage_sub_row_3'>
	<strong>{$r['admin_username']}</strong>
	<div class='desctext'>
		{$r['_admin_time']}
	</div>
 </td>
 <td class='homepage_sub_row_3' align='center'>
	<img src='{$this->ipsclass->skin_acp_url}/images/{$r['_admin_img']}' border='0' alt='-' class='ipd' />
	<br /><a href='#' onclick="return ipsclass.pop_up_window('{$this->ipsclass->base_url}&amp;section=admin&amp;act=loginlog&amp;code=view_detail&amp;detail={$r['admin_id']}', 400, 400)" title='View Details'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/view.png' border='0' alt='-' class='ipd' /></a>
</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_last_logins_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br />
<div class='homepage_pane_border'>
 <div class='homepage_section'>Latest ACP Log Ins</div>
	<table cellspacing='0' cellpadding='0' border='0' width='100%'>
	$content
	</table>
	<div align='right'><a href='{$this->ipsclass->base_url}&amp;section=admin&amp;act=loginlog' style='text-decoration:none'>MORE &raquo;</a></div>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Index
//===========================================================================
function acp_onlineadmin_row( $r ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1' align='center'>
	 <div><img src='{$r['pp_thumb_photo']}' width='{$r['pp_thumb_width']}' height='{$r['pp_thumb_height']}' style='border:1px solid #000000; background-color:#FFFFFF; padding:6px' /></div>
</td>
 <td class='tablerow2'>
	<strong style='font-size:12px'><a href='{$this->ipsclass->vars['board_url']}/index.php?showuser={$r['session_member_id']}' target='_blank'>{$r['members_display_name']}</a></strong>
	<div style='margin-top:6px'>Logged in: {$r['_log_in']}</div>
	<div class='desctext'>IP: {$r['session_ip_address']}</div>
	<div class='desctext'>Using: {$r['session_location']}</div>
	<div class='desctext'>Last click: {$r['_click']}</div>
</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_onlineadmin_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='homepage_border'>
 <div class='homepage_sub_header'>Administrators Using ACP</div>
 <table width='100%' cellpadding='4' cellspacing='0'>
 $content
 </table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_lastactions_row( $rowb ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1' width='1' valign='middle'>
	<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/index/user.png' border='0' alt='-' class='ipd' />
 </td>
 <td class='tablerow1'>
	 <b>{$rowb['members_display_name']}</b>
	<div class='desctext'>IP: {$rowb['ip_address']}</div>
</td>
 <td class='tablerow2'>{$rowb['_ctime']}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_lastactions_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Last 5 ACP Actions</div>
 <table width='100%' cellpadding='4' cellspacing='0'>
 <tr>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
  <td class='tablesubheader' width='44'>Member Name</td>
  <td class='tablesubheader' width='55%'>Time of action</td>
 </tr>
 $content
 </table>
 <div class='tablefooter' align='right'>
   <a href='{$this->ipsclass->base_url}section=admin&amp;act=adminlog' style='text-decoration:none'>MORE &raquo;</a>
 </div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Index
//===========================================================================
function acp_stats_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='homepage_border'>
 <div class='homepage_sub_header'><a href='{$this->ipsclass->base_url}&amp;section=help&amp;act=diag'>System Overview</a></div>
 <table width='100%' cellpadding='4' cellspacing='0'>
 <tr>
  <td class='homepage_sub_row' width='60%'><strong>IP.Board Version</strong> &nbsp;(<a href='{$this->ipsclass->base_url}&amp;section=help&amp;act=diag#versions'>History</a>)</td>
  <td class='homepage_sub_row' width='40%'><span style='color:red'>{$content['ipb_version']} (ID: {$content['ipb_id']})</span></td>
 </tr>
 <tr>
  <td class='homepage_sub_row'><strong>Members</strong></td>
  <td class='homepage_sub_row'>
  	<a href='{$this->ipsclass->base_url}&amp;section=content&amp;act=mem&amp;code=search'>Manage</a>
  	(<strong>{$content['members']}</strong>)
  </td>
 </tr>
 <tr>
  <td class='homepage_sub_row'>&nbsp;&nbsp;&#124;-<strong>Online Users</strong></td>
  <td class='homepage_sub_row'>
  	<a href='{$this->ipsclass->vars['board_url']}/index.php?act=online' target='_blank'>View Online List</a>
  	(<strong>{$content['sessions']}</strong>)
  </td>
 </tr> 
 <tr>
  <td class='homepage_sub_row'>&nbsp;&nbsp;&#124;-<strong>Awaiting Validation</strong></td>
  <td class='homepage_sub_row'>
	<a href='{$this->ipsclass->base_url}&amp;section=content&amp;act=mtools&amp;code=mod'>Manage</a>
  	(<strong>{$content['validate']}</strong>)
  </td>
 </tr>
EOF;

if( $this->ipsclass->vars['ipb_bruteforce_attempts'] > 0 )
{
$IPBHTML .= <<<EOF
 <tr>
  <td class='homepage_sub_row'>&nbsp;&nbsp;&#124;-<strong>Locked Accounts</strong></td>
  <td class='homepage_sub_row'>
  	<a href='{$this->ipsclass->base_url}&amp;section=content&amp;act=mtools&amp;code=lock'>Manage</a>
  	(<strong>{$content['locked']}</strong>)
  </td>
 </tr>
EOF;
}

$IPBHTML .= <<<EOF
 <tr>
  <td class='homepage_sub_row'>&nbsp;&nbsp;&#124;-<strong>COPPA Accounts</strong></td>
  <td class='homepage_sub_row'>
  	<a href='{$this->ipsclass->base_url}&amp;section=content&amp;act=mtools&amp;code=mod&amp;filter=coppa'>Manage</a>
  	(<strong>{$content['coppa']}</strong>)
  </td>
 </tr>
 <tr>
  <td class='homepage_sub_row'><strong>Topics</strong></td>
  <td class='homepage_sub_row'>
  	<strong>{$content['topics']}</strong>
  </td>
 </tr>
 <tr>
  <td class='homepage_sub_row'>&nbsp;&nbsp;&#124;-<strong>Awaiting Moderation</strong></td>
  <td class='homepage_sub_row'>
  	{$content['topics_mod']}
  </td>
 </tr>
 <tr>
  <td class='homepage_sub_row'><strong>Posts</strong></td>
  <td class='homepage_sub_row'>
  	<strong>{$content['replies']}</strong>
  </td>
 </tr>
 <tr>
  <td class='homepage_sub_row'>&nbsp;&nbsp;&#124;-<strong>Awaiting Moderation</strong></td>
  <td class='homepage_sub_row'>
  	{$content['posts_mod']}
  </td>
 </tr>
 <tr>
  <td class='homepage_sub_row'><strong>Uploads</strong></td>
  <td class='homepage_sub_row'><strong><em><div id='uploads-size'><i>Loading...</i></div></em></strong></td>
 </tr>

 </table>
</div>

<script type='text/javascript'>
function get_uploads_size()
{
	var content = document.getElementById( 'uploads-size' );

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
			// Could do a little loading graphic here?
			return;
		}
		
		//----------------------------------
		// INIT
		//----------------------------------
		
		var returned = xmlobj.xmlhandler.responseText;
		content.innerHTML = returned;
	}
	
	//----------------------------------
	// LOAD XML
	//----------------------------------
	
	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );
	
	xmlobj.process( ipb_var_base_url + '&act=xmlout&do=get-dir-size' );
	
	return false;
}
	
get_uploads_size();

</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_php_version_warning( $phpversion ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	Invision Power Board requires at least PHP 4.3.0 to operate fully.<br />Some sections of the ACP may not operate until you upgrade.
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_ftext_warning( $a, $b ) {

$IPBHTML = "";
//--starthtml--//

if( $a )
{
$IPBHTML .= <<<EOF
	Your SQL server supports fulltext searching, which performs much better than the manual search method, however you have not yet created your fulltext indexes.
	<br /><br />
	To create fulltext indexes please see <a href='{$this->ipsclass->base_url}&section=tools&act=op&code=dofulltext'>this page</a> (please note that you can <a href='{$this->ipsclass->base_url}&section=help&act=support&code=support'>submit a ticket</a> for assistance)
	<br /><br />
EOF;
}

if( $b )
{
	$query = urlencode( 'Type of search to use' );
$IPBHTML .= <<<EOF
	It appears fulltext searching is not enabled on your board.  You should enable <a href='{$this->ipsclass->base_url}&section=tools&act=op&code=setting_view&search={$query}'>this setting</a> if fulltext indexes have been created.
EOF;
}

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Index
//===========================================================================
function warning_box($title, $content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='homepage_pane_warning_border'>
 <div class='homepage_section_warning'>$title</div>
 $content
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function warning_unlocked_installer() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	Remove <b>install/index.php</b> from your server at once!
  	<br />Leaving it on your server WILL compromise the security of your system.
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Index
//===========================================================================
function warning_upgrade() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	IPB has detected that an upgrade has not been completed.  Click <a href='{$this->ipsclass->vars['board_url']}/upgrade/index.php'>here</a> to complete.
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function warning_installer() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	Although the installer appears to be locked, we recommend you remove it
   	from your server for security.
   	<br />Simply remove <b>install/index.php</b> from your installation to remove this message.
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function warning_rebuild_emergency() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
   Either you or one of your members encountered a skin error. The following took place
   automatically:
   <ul>
	<li>They were asked to clear their skin settings</li>
	<li>They were asked to click a link to attempt access in the ACP</li>
	<li>The ACP picked up the skin error and rebuilt the skin ID cache, the default skin and it may have turned on safe mode skins</li>
   </ul>
   <b>What to do now</b>
   <ul>
	<li>Firstly, if you don't wish to use safe mode skins, check the CHMOD value of the 'skin_cache' directory to make sure IPB can write into that directory</li>
	<li>If the permissions are correct, check your 'System Settings -&gt; General Configuration' settings to check the value of 'Safe Mode Skins' - disable if not required</li>
	<li>As a precaution, rebuild all your skins by following the link below</li>
   </ul>
   <b>&gt;&gt; <a href='{$this->ipsclass->base_url}&section=lookandfeel&act=sets&code=rebuildalltemplates&removewarning=1'>REBUILD ALL SKIN CACHES & REMOVE THIS WARNING</a> &lt;&lt;</b>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function warning_rebuild_upgrade() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
   You'll need to update all your skin caches to ensure the new template bits have been added correctly.
   <br /><br /><b>&gt;&gt; <a href='{$this->ipsclass->base_url}&section=lookandfeel&act=sets&code=rebuildalltemplates&removewarning=1'>REBUILD ALL SKIN CACHES & REMOVE THIS WARNING</a> &lt;&lt;</b>
EOF;

//--endhtml--//
return $IPBHTML;
}

}

?>