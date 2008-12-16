<?php

class cp_skin_security {

var $ipsclass;

//===========================================================================
// DIAGNOSTICS
//===========================================================================
function list_admin_overview($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Members With ACP Access</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
	<td class='tablesubheader' width='30%'>Name</td>
	<td class='tablesubheader' width='30%'>Primary Group</td>
	<td class='tablesubheader' width='30%'>Secondary Groups</td>
	<td class='tablesubheader' width='30%'>IP Address</td>
	<td class='tablesubheader' width='30%'>Email Address</td>
	<td class='tablesubheader' width='30%'>Posts</td>
	<td class='tablesubheader' width='30%'>&nbsp;</td>
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
// DIAGNOSTICS
//===========================================================================
function list_admin_row( $member ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
	<strong>{$member['members_display_name']}</strong>
	<div class='desctext'>{$member['_joined']}</div>
 </td>
 <td class='tablerow1'>
	{$member['_mgroup']}
 </td>
 <td class='tablerow2'>
	{$member['_mgroup_others']}&nbsp;
 </td>
 <td class='tablerow1'>
	<div class='desctext'>{$member['ip_address']}</div>
 </td>
 <td class='tablerow2'>
	{$member['email']}
 </td>
 <td class='tablerow2'>
	{$member['posts']}
 </td>
 <td class='tablerow1'>
	<a href='{$this->ipsclass->base_url}&amp;section=content&amp;act=mem&amp;code=doform&amp;mid={$member['id']}'>Edit</a>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}



//===========================================================================
// DIAGNOSTICS
//===========================================================================
function deep_scan_bad_files_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=deep_scan' method='POST'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div style='padding-bottom:10px'>Show: 
	<select name='filter'>
	    <option value='all'>Show All</option>
		<option value='score-5'>Score 5 or more</option>
		<option value='score-6'>Score 6 or more</option>
		<option value='score-7'>Score 7 or more</option>
		<option value='score-8'>Score 8 or more</option>
		<option value='score-9'>Score 9 or more</option>
		<option value='large'>Files 55k or larger</option>
		<option value='recent'>Modified in the past 30 days</option>
	</select>
	<input type='submit' value=' Filter ' />
</div>
</form>
<div class='tableborder'>
 <div class='tableheaderalt'>Executable Files</div>
 <div class='tablesubheader' style='padding-right:0px'>
  <div align='right' style='padding-right:10px'>
     (File Size) &nbsp; Last Modified
    </div>
  </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
	$content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// DIAGNOSTICS
//===========================================================================
function deep_scan_bad_files_row( $file_path, $file_name, $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
	<div style='float:right'>
		<div class='desctext'>({$data['human']}k) &nbsp; {$data['mtime']}</div>
	</div>
	<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/bad_file.png' border='0' alt='-' class='ipd' />
	<span style='border:1px solid #555;background-color:#FFFFFF'>
		<span style='width:{$data['left_width']}px;background-color:{$data['color']}'>
			<img src='{$this->ipsclass->skin_acp_url}/images/blank.gif' height='20' width='{$data['left_width']}' alt='' />
		</span>
		<img src='{$this->ipsclass->skin_acp_url}/images/blank.gif' height='20' width='{$data['right_width']}' alt='' />
	</span>
	&nbsp; <span class='desctext'>[ {$data['score']} ]</span> <a target='_blank' href='{$this->ipsclass->vars['board_url']}/{$file_path}'>$file_name</a>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// DIAGNOSTICS
//===========================================================================
function anti_virus_checked_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br />
<div class='tableborder'>
 <div class='tableheaderalt'>Unauthorized File Checked Folders</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
	$content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// DIAGNOSTICS
//===========================================================================
function anti_virus_checked_row( $file_path ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
	<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/checked_folder.png' border='0' alt='-' class='ipd' />
	$file_path
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}



//===========================================================================
// DIAGNOSTICS
//===========================================================================
function anti_virus_bad_files_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Suspicious Files</div>
 <div class='tablesubheader' style='padding-right:0px'>
  <div align='right' style='padding-right:10px'>
     (File Size) &nbsp; Last Modified
    </div>
  </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
	$content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// DIAGNOSTICS
//===========================================================================
function anti_virus_bad_files_row( $file_path, $file_name, $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
	<div style='float:right'>
		<div class='desctext'>({$data['human']}k) &nbsp; {$data['mtime']}</div>
	</div>
	<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/bad_file.png' border='0' alt='-' class='ipd' />
	<span style='border:1px solid #555;background-color:#FFFFFF'>
		<span style='width:{$data['left_width']}px;background-color:{$data['color']}'>
			<img src='{$this->ipsclass->skin_acp_url}/images/blank.gif' height='20' width='{$data['left_width']}' alt='' />
		</span>
		<img src='{$this->ipsclass->skin_acp_url}/images/blank.gif' height='20' width='{$data['right_width']}' alt='' />
	</span>
	&nbsp; <span class='desctext'>[ {$data['score']} ]</span> <a target='_blank' href='{$this->ipsclass->vars['board_url']}/{$file_path}'>$file_name</a>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// OVERVIEW
//===========================================================================
function security_overview( $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/skin_acp/clientscripts/ipd_form_functions.js'></script>
<script type="text/javascript">
 //<![CDATA[
 // INIT FORM FUNCTIONS stuff
 var formfunctions = new form_functions();
 //]]>
</script>
<form id='mainform'>
<div class='information-box'>
	<table cellpadding='0' cellspacing='0'>
	<tr>
		<td width='1%' valign='top'>
 			<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/id_card_ok.png' alt='information' />
		</td>
		<td width='100%' valig='top' style='padding-left:10px'>
 			<h2 style='margin:0px'>Invision Power Board Security Center</h2>
			 <p style='margin:0px'>
			 	<br />
			 	The Invision Power Board Security Center is a centralized repository of security tools and settings.
				<br />
				Your IPB installation is checked and suggestions are made on how to improve the security of your board.
				<br />
				<br />
				{$content['bad']}
				{$content['ok']}
				{$content['good']}
			 </p>
		</td>
	</tr>
	</table>
</div>
</form>
<script type="text/javascript">
//<![CDATA[
// Init form functions, grab stuff
formfunctions.init();
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// BAD
//===========================================================================
function security_item_bad( $title, $desc, $button, $url, $key ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='bad-box' style='margin-bottom:10px'>
<table cellpadding='0' cellspacing='0'>
<tr>
	<td width='1%' valign='middle'>
			<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/lock_error.png' alt='information' />
	</td>
	<td width='71%' valig='top' style='margin-left:10px'>
		 <div style='font-size:14px;font-weight:bold;border-bottom:1px solid #000;padding-bottom:5px;margin-bottom:5px;margin-right:5px'>{$title}</div>
		 <div>$desc</div>
	</td>
	<td width='18%' valign='middle'>
		<div class='formbutton-wrap'>
		 	<div id='button-link-{$key}'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/run_tool.png' border='0' alt='Run'  title='Run' class='ipd' /> $button</div>
		 </div>
	</td>
</tr>
</table>
</div>
<script type="text/javascript">
//<![CDATA[
formfunctions.add_link_event( 'button-link-{$key}', '{$this->ipsclass->base_url}&$url' );
//]]>
</script>

EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GOOD
//===========================================================================
function security_item_good( $title, $desc, $button, $url, $key ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='good-box' style='margin-bottom:10px'>
<table cellpadding='0' cellspacing='0'>
<tr>
	<td width='1%' valign='middle'>
			<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/lock_ok.png' alt='information' />
	</td>
	<td width='71%' valig='top' style='margin-left:10px'>
		 <div style='font-size:14px;font-weight:bold;border-bottom:1px solid #000;padding-bottom:5px;margin-bottom:5px;margin-right:5px'>{$title}</div>
		 <div>$desc</div>
	</td>
	<td width='18%' valign='middle'>
		<div class='formbutton-wrap'>
		 	<div id='button-link-{$key}'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/run_tool.png' border='0' alt='Run'  title='Run' class='ipd' /> $button</div>
		 </div>
	</td>
</tr>
</table>
</div>
<script type="text/javascript">
//<![CDATA[
formfunctions.add_link_event( 'button-link-{$key}', '{$this->ipsclass->base_url}&$url' );
//]]>
</script>

EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// OK
//===========================================================================
function security_item_ok( $title, $desc, $button, $url, $key ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='ok-box' style='margin-bottom:10px'>
<table cellpadding='0' cellspacing='0'>
<tr>
	<td width='1%' valign='middle'>
			<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/lock_information.png' alt='information' />
	</td>
	<td width='71%' valig='top' style='margin-left:10px'>
		 <div style='font-size:14px;font-weight:bold;border-bottom:1px solid #000;padding-bottom:5px;margin-bottom:5px;margin-right:5px'>{$title}</div>
		 <div>$desc</div>
	</td>
	<td width='18%' valign='middle'>
		<div class='formbutton-wrap'>
		 	<div id='button-link-{$key}'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/run_tool.png' border='0' alt='Run'  title='Run' class='ipd' /> $button</div>
		 </div>
	</td>
</tr>
</table>
</div>
<script type="text/javascript">
//<![CDATA[
formfunctions.add_link_event( 'button-link-{$key}', '{$this->ipsclass->base_url}&$url' );
//]]>
</script>

EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// HTaccess form
//===========================================================================
function htaccess_form() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform' method='post' action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=acphtaccess_do'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='information-box'>
	<table cellpadding='0' cellspacing='0'>
	<tr>
		<td width='1%' valign='top'>
 			<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/id_card_ok.png' alt='information' />
		</td>
		<td width='100%' valig='top' style='padding-left:10px'>
 			<h2 style='margin:0px'>ACP .htaccess protection</h2>
			 <p style='margin:0px'>
			 	<br />
			 	Invision Power Board can write .htaccess protection into your admin directory.
				<br />
				<br />
				<strong>Please note</strong>
				<br />
				Using this tool will overwrite any current protection in your admin directory. You will also be required to enter the username and password immediately after saving.  Additionally, if you choose to rename the admin directory, please remember to delete this file from the directory via FTP or you will be locked out of the ACP.
				<br />
				<br />
				<fieldset>
					<legend><strong>Username</strong></legend>
					<input type='text' name='name' size='40' value='{$_POST['name']}' />
				</fieldset>
				<br />
				<fieldset>
					<legend><strong>Password</strong></legend>
					<strong>Please note that only the first 8 characters of your password will be used</strong><br />
					<input type='password' name='pass' size='40' value='{$_POST['pass']}' />
				</fieldset>
				<br />
				<input type='submit' value=' Proceed ' />
			 </p>
		</td>
	</tr>
	</table>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// HTaccess data
//===========================================================================
function htaccess_data( $htaccess_pw, $htaccess_auth ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform'>
<div class='information-box'>
	<table cellpadding='0' cellspacing='0'>
	<tr>
		<td width='1%' valign='top'>
 			<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/id_card_ok.png' alt='information' />
		</td>
		<td width='100%' valig='top' style='padding-left:10px'>
 			<h2 style='margin:0px'>ACP .htaccess protection</h2>
			 <p style='margin:0px'>
			 	<br />
			 	<strong>Invision Power Board is unable to write into your admin directory</strong>
				<br />
				<br />
				Please create a file called ".htpasswd" and copy the contents below into it. Once saved, upload this new file into your 'admin' folder.
				<br />
				<textarea rows='5' cols='70' style='width:98%;height:100px'>$htaccess_pw</textarea>
				<br />
				<br />
				Please create a file called ".htaccess" and copy the contents below into it. Once saved, upload this new file into your 'admin' folder.
				<br />
				<textarea rows='5' cols='70' style='width:98%;height:100px'>$htaccess_auth</textarea>
			 </p>
		</td>
	</tr>
	</table>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Rename the admin directory
//===========================================================================
function rename_admin_dir() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform'>
<div class='information-box'>
	<table cellpadding='0' cellspacing='0'>
	<tr>
		<td width='1%' valign='top'>
 			<img src='{$this->ipsclass->skin_acp_url}/images/folder_components/security/id_card_ok.png' alt='information' />
		</td>
		<td width='100%' valig='top' style='padding-left:10px'>
 			<h2 style='margin:0px'>Renaming the ACP directory</h2>
			 <p style='margin:0px'>
			 	<br />
			 	Invision Power Board has a dedicated directory for the administration center. To further enhance security, you can rename this ACP directory.
				<br />
				<br />
				<strong>Step 1:</strong>
				<br />
				First, you'll need to physically rename the directory. Connect to your server via FTP and then browse to your IPB directory (this is the one that has your
				index.php file in it).
				<br/ >
				Locate the 'admin' directory. It has just one file inside called 'index.php'. Choose 'rename' from your FTP client menu and rename it. For the purposes of this
				tutorial, we'll rename it 'admin_secret'.  <b>If you have created .htaccess protection inside the existing directory, you MUST delete it or you will not be able to login to the ACP afterwards.  You may recreate the .htaccess protection after you have completed the next step.</b>
				<br />
				<br />
				<strong>Step 2:</strong>
				Locate the 'init.php' file. This is in your IPB root directory. Download it and open it in a text editor.
				<br />Near the top of this file, you'll see:
				<br />
				<pre>//-----------------------------------------------
// USER CONFIGURABLE ELEMENTS
//-----------------------------------------------
/**
* ROOT PATH
*
* If __FILE__ is not supported, try ./ and
* turn off "USE_SHUTDOWN" or manually add in
* the full path
* @since 2.0.0.2005-01-01
*/
define( 'ROOT_PATH', dirname( __FILE__ ) ."/" );

<strong>/**
* Directory name for the admin folder
* @since 2.2.0.2006-06-30
*/
define( 'IPB_ACP_DIRECTORY', 'admin' );</strong></pre>
				
				<br />
				Change the line: <pre>define( 'IPB_ACP_DIRECTORY', '<strong>admin</strong>' );</pre> to <pre>define( 'IPB_ACP_DIRECTORY', '<strong>admin_secret</strong>' );</pre>
				<br />
				<br />
				<strong>Your ACP directory has now been renamed.</strong>
				<br />Remember to remove the ACP link from your board using the Security Center.
			 </p>
		</td>
	</tr>
	</table>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}


?>