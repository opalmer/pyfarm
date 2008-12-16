<?php

class cp_skin_global
{


//===========================================================================
// global_popup
//===========================================================================

function global_popup($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
  <head><title>IPB</title>
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Cache-Control" content="no-cache" />
  <meta http-equiv="Expires" content="Mon, 06 May 1996 04:57:00 GMT" />
  <link rel="stylesheet" type="text/css" media="all" href="{$this->ipsclass->skin_acp_url}/acp_css.css" />
  <script type="text/javascript">
  <!--
   var ipb_var_st            = "{$this->ipsclass->input['st']}";
   var ipb_lang_tpl_q1       = "{$this->ipsclass->lang['tpl_q1']}";
   var ipb_var_phpext        = "{$this->ipsclass->vars['php_ext']}";
   var ipb_var_base_url      = "{$this->ipsclass->base_url}&";
   var ipb_var_cookieid      = "{$this->ipsclass->vars['cookie_id']}";
   var ipb_var_cookie_domain = "{$this->ipsclass->vars['cookie_domain']}";
   var ipb_var_cookie_path   = "{$this->ipsclass->vars['cookie_path']}";
   var ipb_skin_url          = "{$this->ipsclass->skin_acp_url}";
   var ipb_var_image_url	 = "{$this->ipsclass->skin_acp_url}/images";
   var use_enhanced_js       = {$this->ipsclass->can_use_fancy_js};
   var ipb_is_acp            = 1;
   //-->
  </script>
  <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_ipsclass.js'></script>
  <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_global.js'></script>
  <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js.js'></script>
  <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_menu.js'></script>
  <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js_skin/ips_menu_html.js'></script>
  <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_xmlhttprequest.js'></script>
  <script type="text/javascript">
  //<![CDATA[
  var ipsclass = new ipsclass();
  ipsclass.init();
  // Validate form to be overwritten
  function ValidateForm() { }
  //]]>
  </script>  
  </head>
  
 <body style='background-image:url({$this->ipsclass->skin_acp_url}/images/blank.gif);text-align:left'>
$content
<script type="text/javascript">
 menu_do_global_init();
</script>
</body>
</html>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// global_wrapper
//===========================================================================

function global_main_wrapper() {

$date = date("Y");

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset={$this->ipsclass->vars['gb_char_set']}" /> 
<title><%TITLE%></title>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="Mon, 06 May 1996 04:57:00 GMT" />
<link rel="shortcut icon" href="favicon.ico" />
<style type='text/css' media="all">
@import url( "{$this->ipsclass->skin_acp_url}/acp_css.css" );
</style>
 <script type="text/javascript">
 <!--
  var ipb_var_st            = "{$this->ipsclass->input['st']}";
  var ipb_lang_tpl_q1       = "{$this->ipsclass->lang['tpl_q1']}";
  var ipb_var_phpext        = "{$this->ipsclass->vars['php_ext']}";
  var ipb_var_base_url      = "{$this->ipsclass->base_url}";
  var ipb_var_cookieid      = "{$this->ipsclass->vars['cookie_id']}";
  var ipb_var_cookie_domain = "{$this->ipsclass->vars['cookie_domain']}";
  var ipb_var_cookie_path   = "{$this->ipsclass->vars['cookie_path']}";
  var ipb_skin_url          = "{$this->ipsclass->skin_acp_url}";
  var ipb_var_image_url		= "{$this->ipsclass->skin_acp_url}/images";
  var ipb_md5_check         = "{$this->ipsclass->md5_check}";
  var use_enhanced_js       = {$this->ipsclass->can_use_fancy_js};
  var ipb_is_acp            = 1;
  //-->
 </script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_ipsclass.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_global.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_menu.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js_skin/ips_menu_html.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_xmlhttprequest.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/dom-drag.js'></script>
 <script type="text/javascript">
 //<![CDATA[
 var ipsclass = new ipsclass();
 ipsclass.init();
 // Validate form to be overwritten
 function ValidateForm() { }
 //]]>
 </script>
</head>
<body>
<div id='loading-layer' style='display:none'>
	<div id='loading-layer-shadow'>
	   <div id='loading-layer-inner' >
		   <img src='{$this->ipsclass->skin_acp_url}/images/loading_anim.gif' style='vertical-align:middle' border='0' alt='Loading...' /><br />
		   <span style='font-weight:bold' id='loading-layer-text'>Loading Data. Please Wait...</span>
	   </div>
	</div>
</div>
<div id='ipdwrapper'><!-- IPDWRAPPER -->
<%CONTENT%>
<br />
 <div class='copy' align='center'>Invision Power Board &copy $date IPS, Inc.</div>
</div><!-- / IPDWRAPPER -->
<script type="text/javascript">
menu_do_global_init();
// Uncomment this to fix IE png images
// causes page slowdown, and some missing images occasionally
// if ( is_ie )
// {
//	 ie_fix_png();
// }
</script>
</body>
</html>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Information box...
//===========================================================================
function information_box($title="", $content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='information-box'>
 <img src='{$this->ipsclass->skin_acp_url}/images/icon_information.png' alt='information' />
 <h2>$title</h2>
 <p>
 	<br />
 	$content
 </p>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Information box...
//===========================================================================
function warning_box($title="", $content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='warning-box'>
 <img src='{$this->ipsclass->skin_acp_url}/images/icon_warning.png' alt='information' />
 <h2>$title</h2>
 <p>
 	<br />
 	$content
 </p>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Help box...
//===========================================================================
function help_box( $show=array(), $title="", $content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='help-box' style="display:{$show['div_fc']}" id="fc_{$show['div_key']}">
	<h2>
		<div style='float:right;'><a href="javascript:togglecategory('{$show['div_key']}', 0);"><img style='margin: 4px 4px 4px 2px;' src='{$this->ipsclass->skin_acp_url}/images/arrow_down.png' alt='Show' /></a></div>
		<div><a href="javascript:togglecategory('{$show['div_key']}', 0);" style='text-decoration:none;'>{$title}</a></div>
	</h2>
</div>
<div class='help-box' style="display:{$show['div_fo']}" id="fo_{$show['div_key']}">
 <img src='{$this->ipsclass->skin_acp_url}/images/icon_help.png' alt='help' />
  <h2>
  	<div style='float:right;'><a href="javascript:togglecategory('{$show['div_key']}', 1);"><img style='margin: 4px 4px 4px 2px;' src='{$this->ipsclass->skin_acp_url}/images/arrow_up.png' alt='Hide' /></a></div>
  	<div><a href="javascript:togglecategory('{$show['div_key']}', 1);" style='text-decoration:none;'>{$title}</a></div>
  </h2>
 <p>
 	<br />
 	$content
 </p>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Information box...
//===========================================================================
function global_memberbar() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='global-memberbar'>
 Welcome <strong>{$this->ipsclass->member['members_display_name']}</strong> [
 <a href='{$this->ipsclass->vars['board_url']}/index.php' target='_blank'>View IPB</a> &middot;
 <a href='{$this->ipsclass->base_url}&amp;act=login&amp;code=login-out'>Log Out</a>
 ]
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Global in-line message
//===========================================================================
function global_help_block($title, $msg) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div><strong>$title</strong><p>$msg</p></div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Global in-line message
//===========================================================================
function global_message() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='information-box'>
 <img src='{$this->ipsclass->skin_acp_url}/images/global-infoicon.gif' alt='information' />
 <h2>Invision Power Board Message</h2>
 <p>
 	<br />
 	{$this->ipsclass->main_msg}
 </p>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Main Frames
//===========================================================================
function global_frame_wrapper() {

$year = date( 'Y' );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<!-- TOP TABS -->
<div class='tabwrap-main'>
<%TABS%>
<div class='logoright'><img src='{$this->ipsclass->skin_acp_url}/images/acp-logo.gif' alt='IP.Board' border='0' /></div>
</div>
<!-- / TOP TABS -->

<div class='sub-tab-strip'>
    <%MEMBERBAR%>
	<%NAV%>
</div>
<div class='outerdiv' id='global-outerdiv'><!-- OUTERDIV -->
<table cellpadding='0' cellspacing='8' width='100%' id='tablewrap'>
<tr>
EOF;
if ( $this->ipsclass->menu_type AND $this->ipsclass->menu_type != 'dashboard' )
{
	$IPBHTML .= <<<EOF
 <td width='22%' valign='top' id='leftblock'>
 <div>
 <!-- LEFT CONTEXT SENSITIVE MENU -->
 <%MENU%>
 <!-- / LEFT CONTEXT SENSITIVE MENU -->
 </div>
 </td>
 <td width='78%' valign='top' id='rightblock'>
 <div><!-- RIGHT CONTENT BLOCK -->
 <%HELP%>
 <%MSG%>
 <%SECTIONCONTENT%>
 </div><!-- / RIGHT CONTENT BLOCK -->
 </td>
EOF;
}
else
{
$IPBHTML .= <<<EOF
	<td width='100%' valign='top' id='rightblock'>
	 <div><!-- RIGHT CONTENT BLOCK -->
	 <%HELP%>
	 <%MSG%>
	 <%SECTIONCONTENT%>
	 </div><!-- / RIGHT CONTENT BLOCK -->
	 </td>
EOF;
}
$IPBHTML .= <<<EOF
</tr>
</table>
</div><!-- / OUTERDIV -->
<%QUERIES%>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Top TABS
//===========================================================================
function global_tabs($onoff="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='{$onoff['dashboard']}'><img src='{$this->ipsclass->skin_acp_url}/images/tabs_main/dashboard.png' style='vertical-align:middle' /> <a href='{$this->ipsclass->base_url}&section=dashboard'>HOME</a></div>
<div class='{$onoff['content']}'><img src='{$this->ipsclass->skin_acp_url}/images/tabs_main/system.png' style='vertical-align:middle' /> <a href='{$this->ipsclass->base_url}&section=content'>MANAGEMENT</a></div>
<div class='{$onoff['lookandfeel']}'><img src='{$this->ipsclass->skin_acp_url}/images/tabs_main/lookfeel.png' style='vertical-align:middle' /> <a href='{$this->ipsclass->base_url}&section=lookandfeel'>LOOK &amp; FEEL</a></div>
<div class='{$onoff['tools']}'><img src='{$this->ipsclass->skin_acp_url}/images/tabs_main/tools.png' style='vertical-align:middle' /> <a href='{$this->ipsclass->base_url}&section=tools'>TOOLS &amp; SETTINGS</a></div>
<div class='{$onoff['components']}'><img src='{$this->ipsclass->skin_acp_url}/images/tabs_main/components.png' style='vertical-align:middle' /> <a href='{$this->ipsclass->base_url}&section=components'>COMPONENTS</a></div>
<div class='{$onoff['admin']}'><img src='{$this->ipsclass->skin_acp_url}/images/tabs_main/admin.png' style='vertical-align:middle' /> <a href='{$this->ipsclass->base_url}&section=admin'>ADMIN</a></div>
<div class='{$onoff['help']}'><img src='{$this->ipsclass->skin_acp_url}/images/tabs_main/help.png' style='vertical-align:middle' /> <a href='{$this->ipsclass->base_url}&section=help'>SUPPORT</a></div>

EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Query HTML
//===========================================================================
function global_query_output($queries="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br /><br />
<div align='center' style='margin-left:auto;margin-right:auto'>
<div class='tableborder' style='vertical-align:bottom;text-align:left;width:75%;color:#555'>
 <div style='padding:5px'><b>Queries</b></div>
 <div class='tablerow1' style='padding:6px;color:#555;font-size:10px'>$queries</div>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}



//===========================================================================
// Log in form
//===========================================================================
function log_in_form($query_string="", $message="", $name="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<div align='center'>
<div style='width:500px'>
<div class='outerdiv' id='global-outerdiv'><!-- OUTERDIV -->
<table cellpadding='0' cellspacing='8' width='100%' id='tablewrap'>
<tr>
 <td id='rightblock'>
 <div>
 <form id='loginform' action='{$this->ipsclass->base_url}&amp;act=login&amp;code=login-complete' method='post'>
 <input type='hidden' name='qstring' value='$query_string' />
  <table width='100%' cellpadding='0' cellspacing='0' border='0'>
  <tr>
   <td width='200' class='tablerow1' valign='top' style='border:0px;width:200px'>
   <div style='text-align:center;padding-top:20px'>
   	<img src='{$this->ipsclass->skin_acp_url}/images/acp-login-lock.gif' alt='IPB' border='0' />
   </div>
   <br />
   <div class='desctext' style='font-size:10px'>
   <div align='center'><strong>Welcome to IP.Board</strong></div>
   <br />
  	<div style='font-size:9px;color:gray'>&copy; Invision Power Services, Inc.
	This program is protected by international copyright laws as described in the license agreement.</div>
   </div>
   </td>
   <td width='300' style='width:300px' valign='top'>
	 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
	 <tr>
	  <td colspan='2' align='center'>
		 <br /><img src='{$this->ipsclass->skin_acp_url}/images/acp-login-logo.gif' alt='IPB' border='0' />
		 <div style='font-weight:bold;color:red'>$message</div>
	  </td>
	 </tr>
	 <tr>
EOF;
if ( $this->ipsclass->vars['ipbli_usertype'] == 'username' )
{
$IPBHTML .= <<<EOF
		<td align='right'><strong>User Name</strong></td>
EOF;
}
else
{
$IPBHTML .= <<<EOF
		<td align='right'><strong>Email</strong></td>
EOF;
}
$IPBHTML .= <<<EOF
	  <td><input style='border:1px solid #AAA' type='text' size='20' name='username' id='namefield' value='$name' /></td>
	 </tr>
	 <tr>
	  <td align='right'><strong>Password</strong></td>
	  <td><input style='border:1px solid #AAA' type='password' size='20' name='password' value='' /></td>
	 </tr>
	 <tr>
	  <td colspan='2' align='center'><input type='submit' style='border:1px solid #AAA' value='Log In' /></td>
	 </tr>
	 <tr>
	  <td colspan='2'><br />
		  
	  </td>
	 </tr>
	</table>
   </td>
  </tr>
  </table>
 </form>
 
 </div>
 </td>
</tr>
</table>
</div><!-- / OUTERDIV -->

</div>
</div>
<script type='text/javascript'>
<!--
  if (top.location != self.location) { top.location = self.location }
  
  try
  {
  	window.onload = function() { document.getElementById('namefield').focus(); }
  }
  catch(error)
  {
  	alert(error);
  }
  
//-->
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Log in redirect
//===========================================================================
function global_redirect_hit($url, $text="", $time=1) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<link rel="stylesheet" type="text/css" media="all" href="{$this->ipsclass->skin_acp_url}/acp_css.css" />
<script type="text/javascript">
<!--
 var ipb_var_st            = "{$this->ipsclass->input['st']}";
 var ipb_lang_tpl_q1       = "{$this->ipsclass->lang['tpl_q1']}";
 var ipb_var_phpext        = "{$this->ipsclass->vars['php_ext']}";
 var ipb_var_base_url      = "{$this->ipsclass->base_url}";
 var ipb_var_cookieid      = "{$this->ipsclass->vars['cookie_id']}";
 var ipb_var_cookie_domain = "{$this->ipsclass->vars['cookie_domain']}";
 var ipb_var_cookie_path   = "{$this->ipsclass->vars['cookie_path']}";
 var ipb_skin_url          = "{$this->ipsclass->skin_acp_url}";
 var ipb_var_image_url     = "{$this->ipsclass->skin_acp_url}/images";
 var menu_ids              = "<!--{IDS}-->";
  var ipb_is_acp            = 1;
 //-->
</script>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<meta http-equiv='refresh' content='{$time}; url=$url' />
<div style='width:500px;margin-left:auto;margin-right:auto;'>
<div class='tableborder'>
 <div class='alttitle'>
  <div style='float:right'><img src='{$this->ipsclass->skin_acp_url}/images/acp_logo.gif' alt='IPB' border='0' /></div>
  <div style='padding-left:3px;padding-top:14px;font-size:16px;'>Redirecting....</div>
 </div>
 <div style='background-color:#FFF;padding:10px;'>
 <strong>{$text}</strong>
 <br />
 <br />
 <a href='$url'>( Click here if you do not wish to wait )</a>
 </div>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Log in redirect
//===========================================================================
function global_redirect_done($text='Complete!') {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<link rel="stylesheet" type="text/css" media="all" href="{$this->ipsclass->skin_acp_url}/acp_css.css" />
<script type="text/javascript">
<!--
 var ipb_var_st            = "{$this->ipsclass->input['st']}";
 var ipb_lang_tpl_q1       = "{$this->ipsclass->lang['tpl_q1']}";
 var ipb_var_phpext        = "{$this->ipsclass->vars['php_ext']}";
 var ipb_var_base_url      = "{$this->ipsclass->base_url}";
 var ipb_var_cookieid      = "{$this->ipsclass->vars['cookie_id']}";
 var ipb_var_cookie_domain = "{$this->ipsclass->vars['cookie_domain']}";
 var ipb_var_cookie_path   = "{$this->ipsclass->vars['cookie_path']}";
 var ipb_skin_url          = "{$this->ipsclass->skin_acp_url}";
 var ipb_var_image_url	   = "{$this->ipsclass->skin_acp_url}/images";
 var menu_ids              = "<!--{IDS}-->";
  var ipb_is_acp            = 1;
 //-->
</script>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<div style='width:500px;margin-left:auto;margin-right:auto;'>
<div class='tableborder'>
 <div class='alttitle'>
  <div style='float:right'><img src='{$this->ipsclass->skin_acp_url}/images/acp_logo.gif' alt='IPB' border='0' /></div>
  <div style='padding-left:3px;padding-top:14px;font-size:16px;'>Function Complete</div>
 </div>
 <div style='background-color:#FFF;padding:10px;'>
 <strong>{$text}</strong>
 </div>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}



//===========================================================================
// Log in redirect
//===========================================================================
function global_redirect($url, $time=2, $text="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<meta http-equiv='refresh' content='{$time}; url=$url' />
<div align='center'>
<div style='width:500px'>
<div class='outerdiv' id='global-outerdiv'>
<table cellpadding='0' cellspacing='8' width='100%' id='tablewrap'>
<tr>
 <td id='rightblock'>
 <div>
  <table width='100%' cellpadding='0' cellspacing='0' border='0'>
  <tr>
   <td width='200' class='tablerow1' valign='top' style='border:0px;width:200px'>
   <div style='text-align:center;padding-top:20px'>
   	<img src='{$this->ipsclass->skin_acp_url}/images/acp-redirect.gif' alt='IPB' border='0' />
   </div>
   <br />
   <div class='desctext' style='font-size:10px'>
   <div align='center'><strong>Page Redirecting...</strong></div>
   <br />
  	<div style='font-size:9px;color:gray'>Please stand-by as we redirect you.</div>
   </div>
   </td>
   <td width='300' style='width:300px' valign='top'>
	 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
	 <tr>
	  <td colspan='2' align='center'>
		 <br /><img src='{$this->ipsclass->skin_acp_url}/images/acp-login-logo.gif' alt='IPDynamic' border='0' />
		 <br />
		 <br />
		 <strong>{$text}</strong>
		 <br />
		 <br />
		 <a href='$url'>( Click here if you do not wish to wait )</a>
	  </td>
	 </tr>
	 </table>
   </td>
  </tr>
  </table>
 </div>
 </td>
</tr>
</table>
</div>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Mini header
//===========================================================================

function global_mini_header( $extra="style='background-color:#D6D6D6' onload='passfocus()'")
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset={$this->ipsclass->vars['gb_char_set']}" /> 
<title><%TITLE%></title>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="Mon, 06 May 1996 04:57:00 GMT" />
<link rel="shortcut icon" href="favicon.ico" />
<style type='text/css' media="all">
@import url( "{$this->ipsclass->skin_acp_url}/acp_css.css" );
</style>
 <script type="text/javascript">
 <!--
  var ipb_var_st            = "{$this->ipsclass->input['st']}";
  var ipb_lang_tpl_q1       = "{$this->ipsclass->lang['tpl_q1']}";
  var ipb_var_phpext        = "{$this->ipsclass->vars['php_ext']}";
  var ipb_var_base_url      = "{$this->ipsclass->base_url}";
  var ipb_var_cookieid      = "{$this->ipsclass->vars['cookie_id']}";
  var ipb_var_cookie_domain = "{$this->ipsclass->vars['cookie_domain']}";
  var ipb_var_cookie_path   = "{$this->ipsclass->vars['cookie_path']}";
  var ipb_skin_url          = "{$this->ipsclass->skin_acp_url}";
  var ipb_var_image_url		= "{$this->ipsclass->skin_acp_url}/images";
  var ipb_md5_check         = "{$this->ipsclass->md5_check}";
  var use_enhanced_js       = {$this->ipsclass->can_use_fancy_js};
  var ipb_is_acp            = 1;
  //-->
 </script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_ipsclass.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_global.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_menu.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js_skin/ips_menu_html.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_xmlhttprequest.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/dom-drag.js'></script>
 <script type="text/javascript">
 //<![CDATA[
 var ipsclass = new ipsclass();
 ipsclass.init();
 //]]>
 </script>
</head>
<body $extra>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Mini header
//===========================================================================

function global_mini_footer()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
</body>
</html>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// GLOBAL MENU CAT LINK
//===========================================================================

function global_menu_cat_link( $cid, $pid, $icon, $theurl, $url, $extra_css, $name ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='menulinkwrap'>&nbsp;{$icon}&nbsp;<a href='{$theurl}$url' style='text-decoration:none{$extra_css}'>$name</a></div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL WRAP NAV
//===========================================================================

function global_menu_cat_wrap($name="", $links="", $id = "", $desc) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='menuouterwrap'>
  <div class='menucatwrap'><img src='{$this->ipsclass->skin_acp_url}/images/menu_title_bullet.gif' style='vertical-align:bottom' border='0' /> $name</div>
  $links
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL WRAP NAV
//===========================================================================

function global_wrap_nav($links="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='navwrap'>$links</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL FRAMESET
//===========================================================================

/*function global_frame_set($extra_query="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<html>
  <head><title>Invision Power Board Administration Center</title></head>
	<frameset cols='185, *' frameborder='no' border='0' framespacing='0'>
	 <frame name='menu' noresize scrolling='auto' src='{$this->ipsclass->base_url}&act=menu'>
	 <frame name='body' noresize scrolling='auto' src='{$this->ipsclass->base_url}&{$extra_query}'>
	</frameset>
</html>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// GLOBAL MAIN ACP HEADER
//===========================================================================

function global_menu_header() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<html>
  <head><title>Menu</title>
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Cache-Control" content="no-cache" />
  <meta http-equiv="Expires" content="Mon, 06 May 1996 04:57:00 GMT" />
  <link rel="stylesheet" type="text/css" media="all" href="{$this->ipsclass->skin_acp_url}/acp_css.css" />
  <script type="text/javascript">
  <!--
   var ipb_var_st            = "{$this->ipsclass->input['st']}";
   var ipb_lang_tpl_q1       = "{$this->ipsclass->lang['tpl_q1']}";
   var ipb_var_phpext        = "{$this->ipsclass->vars['php_ext']}";
   var ipb_var_base_url      = "{$this->ipsclass->base_url}";
   var ipb_var_cookieid      = "{$this->ipsclass->vars['cookie_id']}";
   var ipb_var_cookie_domain = "{$this->ipsclass->vars['cookie_domain']}";
   var ipb_var_cookie_path   = "{$this->ipsclass->vars['cookie_path']}";
   var ipb_skin_url          = "{$this->ipsclass->skin_acp_url}";
   var menu_ids              = "<!--{IDS}-->";
   //-->
  </script>
  <script type="text/javascript" src='jscripts/ipb_global.js'></script>
  <script type="text/javascript" src='jscripts/ips_menu.js'></script>
  <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js.js'></script>
  <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js_skin/ips_menu_html.js'></script>
  </head>
  
 <body>
 <div align='center' id='logostrip'><img src='{$this->ipsclass->skin_acp_url}/images/logo4.gif' border='0'></div>
 <div class='tableborder'>
  <div class='menulinkwrap'>
   &nbsp;<img src='{$this->ipsclass->skin_acp_url}/images/item.gif' border='0' alt='' valign='absmiddle'>&nbsp;<a href='javascript:expandmenu();'>Expand</a> &middot; <a href='javascript:collapsemenu();'>Collapse</a> Menu
   <br />&nbsp;<img src='{$this->ipsclass->skin_acp_url}/images/item.gif' border='0' alt='' valign='absmiddle'>&nbsp;<a href='{$this->ipsclass->base_url}&act=index' target='body'>ACP</a> &middot; <a href='{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}' target='body'>Board</a> Home
   <br />&nbsp;<img src='{$this->ipsclass->skin_acp_url}/images/item.gif' border='0' alt='' valign='absmiddle'>&nbsp;<a href='{$this->ipsclass->base_url}&act=ips&code=docs' target='body' style='text-decoration:none'>IPB Documentation</a>
   <br />&nbsp;<img src='{$this->ipsclass->skin_acp_url}/images/item.gif' border='0' alt='' valign='absmiddle'>&nbsp;<a href='{$this->ipsclass->base_url}&act=op&code=phpinfo' style='text-decoration:none' target='body'>PHP Info</a>
  </div>
 </div>
 <br />
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL MAIN ACP MENU FOOTER
//===========================================================================

function global_menu_footer() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
 </body>
</html>
EOF;

//--endhtml--//
return $IPBHTML;
}*/

//===========================================================================
// GLOBAL MAIN ACP FOOTER
//===========================================================================

function global_footer($date="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br />
 <div align='right' id='jwrap'><strong>Quick Jump</strong> <!--JUMP--></div>
 <!-- <div class='copy' align='center'>Invision Power Board &copy $date IPS, Inc.</div>-->
</div><!-- / IPDWRAPPER -->
<script type="text/javascript">
menu_do_global_init();
//if ( is_ie )
//{
//	ie_fix_png();
//}
</script>
</body>
</html>
EOF;

//--endhtml--//
return $IPBHTML;
}
	
//===========================================================================
// GLOBAL MAIN ACP HEADER
//===========================================================================

function global_header() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset={$this->ipsclass->vars['gb_char_set']}" /> 
<title><%TITLE%></title>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="Mon, 06 May 1996 04:57:00 GMT" />
<link rel="shortcut icon" href="favicon.ico" />
<style type='text/css' media="all">
@import url( "{$this->ipsclass->skin_acp_url}/acp_css.css" );
</style>
 <script type="text/javascript">
 <!--
  var ipb_var_st            = "{$this->ipsclass->input['st']}";
  var ipb_lang_tpl_q1       = "{$this->ipsclass->lang['tpl_q1']}";
  var ipb_var_phpext        = "{$this->ipsclass->vars['php_ext']}";
  var ipb_var_base_url      = "{$this->ipsclass->base_url}";
  var ipb_var_cookieid      = "{$this->ipsclass->vars['cookie_id']}";
  var ipb_var_cookie_domain = "{$this->ipsclass->vars['cookie_domain']}";
  var ipb_var_cookie_path   = "{$this->ipsclass->vars['cookie_path']}";
  var ipb_skin_url          = "{$this->ipsclass->skin_acp_url}";
  var ipb_var_image_url		= "{$this->ipsclass->skin_acp_url}/images";
  var ipb_md5_check         = "{$this->ipsclass->md5_check}";
  var use_enhanced_js       = {$this->ipsclass->can_use_fancy_js};
  var ipb_is_acp            = 1;
  //-->
 </script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_ipsclass.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_global.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_menu.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_js_skin/ips_menu_html.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_xmlhttprequest.js'></script>
 <script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/dom-drag.js'></script>
 <script type="text/javascript">
 //<![CDATA[
 var ipsclass = new ipsclass();
 ipsclass.init();
 // Validate form to be overwritten
 function ValidateForm() { }
 //]]>
 </script>
</head>
<body>
<div id='loading-layer' style='display:none'>
	<div id='loading-layer-shadow'>
	   <div id='loading-layer-inner' >
		   <img src='{$this->ipsclass->skin_acp_url}/images/loading_anim.gif' style='vertical-align:middle' border='0' alt='Loading...' />
		   <span style='font-weight:bold' id='loading-layer-text'>Loading Data. Please Wait...</span>
	   </div>
	</div>
</div>
<div id='ipdwrapper'><!-- IPDWRAPPER -->
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Pagination Wrapper
//===========================================================================
function pagination_compile($start="",$previous_link="",$start_dots="",$pages="",$end_dots="",$next_link="",$total_pages="",$per_page="",$base_link="") {
$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
{$start}{$start_dots}{$previous_link}{$pages}{$next_link}{$end_dots}
<script type="text/javascript">
//<![CDATA[
ipb_pages_shown++;
var pgjmp = document.getElementById( 'page-jump' );
pgjmp.id  = 'page-jump-'+ipb_pages_shown;
ipb_pages_array[ ipb_pages_shown ] = new Array( '{$base_link}', $per_page, $total_pages );

// Change out CSS
css_mainwrap = 'popupmenu-pagelinks';

menu_build_menu(
	pgjmp.id,
	"<div onmouseover='pages_st_focus("+ipb_pages_shown+")' align='center'>{$this->ipsclass->lang['global_page_jump']}</div><input type='hidden' id='st-type-"+ipb_pages_shown+"' value='{$st}' /><input type='text' size='5' name='st' id='st-"+ipb_pages_shown+"' /> <input type='button' class='button' onclick='do_multi_page_jump("+ipb_pages_shown+");' value='{$this->ipsclass->lang['jmp_go']}' />",
	1 );
	
css_mainwrap = 'popupmenu';
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Pagination: Current Page
//===========================================================================
function pagination_current_page($page="") {
$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
&nbsp;<span class="pagecurrent">{$page}</span>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Pagination: End Dots
//===========================================================================
function pagination_end_dots($url="") {
$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
&nbsp;<span class="pagelinklast"><a href="$url" title="Go to last">&raquo;</a></span>&nbsp;
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Pagination: Make jump menu
//===========================================================================
function pagination_make_jump($pages=1) {
$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<span class="pagelink" id='page-jump'>$pages {$this->ipsclass->lang['tpl_pages']} <img src='{$this->ipsclass->skin_acp_url}/images/menu_action_down.gif' alt='V' title='{$this->ipsclass->lang['global_open_menu']}' border='0' /></span>&nbsp;
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Pagination: Next Link
//===========================================================================
function pagination_next_link($url="") {
$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
&nbsp;<span class="pagelink"><a href="$url" title="Next">&gt;</a></span>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Pagination: Regular Link
//===========================================================================
function pagination_page_link($url="",$page="") {
$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
&nbsp;<span class="pagelink"><a href="$url" title="$page">$page</a></span>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Pagination: Previous Link
//===========================================================================
function pagination_previous_link($url="") {
$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<span class="pagelink"><a href="$url" title="Previous">&lt;</a></span>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Pagination: Start Dots
//===========================================================================
function pagination_start_dots($url="") {
$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<span class="pagelinklast"><a href="$url" title="Go to first">&laquo;</a></span>&nbsp;
EOF;

//--endhtml--//
return $IPBHTML;
}

	
}

?>