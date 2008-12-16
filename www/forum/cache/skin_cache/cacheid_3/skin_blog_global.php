<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD           */
/* CACHE FILE: Skin set id: 3                     */
/* CACHE FILE: Generated: Wed, 12 Nov 2008 04:54:08 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_blog_global_3 {

 var $ipsclass;
//===========================================================================
// <ips:blog_header:desc::trigger:>
//===========================================================================
function blog_header($blog="",$msg="",$cblock_js="",$component_links="",$toggle_draft="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<script type=\"text/javascript\">
//<![CDATA[
    var ipb_var_blog_id = \"{$blog['blog_id']}\";
    var ipb_var_blog_url = \"{$blog['blog_url']}\";
	var ipb_lang_blog_sure_delcblock = \"{$this->ipsclass->lang['blog_sure_delcblock']}\";
	function delete_entry(theURL)
	{
		if (confirm( \"{$this->ipsclass->lang['sure_delentry']}\" ))
		{
			window.location.href=theURL;
		}
		else
		{
			alert ( \"{$this->ipsclass->lang['del_no_action']}\" );
		}
	}
	function delete_comment(theURL)
	{
		if (confirm( \"{$this->ipsclass->lang['sure_delcomment']}\" ))
		{
			window.location.href=theURL;
		}
		else
		{
			alert ( \"{$this->ipsclass->lang['del_no_action']}\" );
		}
	}
	function sendtrackback_pop(eid)
	{
		ShowHide(\"modmenuopen_\"+eid, \"modmenuclosed_\"+eid);
		window.open(\"{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?automodule=blog&req=sendtrackback&eid=\"+eid+\"&s={$this->ipsclass->session_id}\",\"SendTrackback\",\"width=600,height=300,resizable=yes,scrollbars=yes\");
	}
	function permalink_to_entry(eid){
		temp = prompt( \"{$this->ipsclass->lang['permalink_prompt']}\", \"{$this->ipsclass->base_url}automodule=blog&blogid={$blog['blog_id']}&showentry=\"+eid );
		return false;
	}
	function emo_pop( formobj )
	{
		emoticon = function( ecode, eobj, eurl ){
			document.getElementById( formobj ).value += ' ' + ecode + ' ';
		}
		window.open(\"{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?act=legends&CODE=emoticons&s={$this->ipsclass->session_id}\",\"Legends\",\"width=250,height=500,resizable=yes,scrollbars=yes\");
	}
	function bbc_pop()
	{
		window.open(\"{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?act=legends&CODE=bbcode&s={$this->ipsclass->session_id}\",\"Legends\",\"width=700,height=500,resizable=yes,scrollbars=yes\");
	}
//]]>
</script>
" . (($this->ipsclass->vars['blog_enable_dheader'] == 1) ? ("
	<div id=\"logostrip\"><a href='{$this->ipsclass->vars['blog_url']}'><!--ipb.logo.start--><img src='{$blog['header_image']}' style='vertical-align:top' alt='{$blog['blog_name']}' border='0' /><!--ipb.logo.end--></a></div>
") : ("")) . "
" . (($this->ipsclass->vars['blog_enable_dheader'] == 0) ? ("
	<div id=\"logostrip\"><a href='{$this->ipsclass->vars['blog_url']}'><!--ipb.logo.start--><div style=\"float:left;\"><img src='{$this->ipsclass->vars['img_url']}/logo_blog.gif' style='vertical-align:top' alt='{$blog['blog_name']}' border='0' /></div><!--ipb.logo.end--><div style=\"padding: 15px 0px 0px 0px; color:#FFFFFF; text-decoration:none; width:auto; position:absolute; left:70px; \"><b><span style=\"font-size:24px;\">{$blog['blog_name']}</span><br /><br />{$blog['blog_desc']}</b></div></a></div>
") : ("")) . "
	<div id=\"submenu\">
		
		<div id='submenu_left'>
		<!--ipb.leftlinks.start-->
			<a href=\"{$this->ipsclass->vars['blog_url']}\">{$blog['blog_name']}</a>
		<!--ipb.leftlinks.end-->
		</div>
		
		<div id='submenu_right'>
		<!--ipb.rightlinks.start-->
			<a href=\"{$this->ipsclass->base_url}act=Help\">{$this->ipsclass->lang['tb_help']}</a>
			<a href=\"{$this->ipsclass->base_url}act=Search&amp;f={$this->ipsclass->input['f']}\" id=\"ipb-tl-search\">{$this->ipsclass->lang['tb_search']}</a>
			<a href=\"{$this->ipsclass->base_url}act=Members\">{$this->ipsclass->lang['tb_mlist']}</a>
			<a href=\"{$this->ipsclass->base_url}act=calendar\">{$this->ipsclass->lang['tb_calendar']}</a>
			" . (($component_links != "") ? ("
				{$component_links}
			") : ("")) . "
			<a href='{$this->ipsclass->base_url}'>{$this->ipsclass->lang['forums']}</a>
			<div class='popupmenu-new' id='ipb-tl-search_menu' style='display:none;width:210px'>
				<form action=\"{$this->ipsclass->base_url}act=Search&amp;CODE=01&amp;forums=all\" method=\"post\">
					<input type=\"text\" size=\"20\" name=\"keywords\" id='ipb-tl-search-box' />
					<input class=\"button\" type=\"image\" style='border:0px' src=\"{$this->ipsclass->vars['img_url']}/login-button.gif\" />
				</form>
				<div style='padding:4px'>
					<a href='{$this->ipsclass->base_url}act=Search'>{$this->ipsclass->lang['gbl_more_search']}</a>
				</div>
			</div>
			<script type=\"text/javascript\">
				ipsmenu.register( \"ipb-tl-search\", 'document.getElementById(\"ipb-tl-search-box\").focus();' );
			</script>
			<!--ipb.rightlinks.end-->
		</div>
	</div>
<!--BLOG.TEMPLATE.MEMBERBAR-->
<div id=\"ipbwrapper\">
<table width=\"100%\" cellspacing=\"2\" cellpadding=\"0\">
<tr><td width='99%'>
<!--BLOG.TEMPLATE.NAVIGATION-->
</td>
	" . (($this->ipsclass->member['id'] == $blog['member_id']) ? ("
		<td width='1%'>
			<div class='popmenubutton'><a href='javascript:blogsettings_pop();'>{$this->ipsclass->lang['blog_settings_link']}</a></div>
		</td>
		<td width='1%'>
			<div class='popmenubutton' id='cblock-options'><a href='#cblockoptions'>{$this->ipsclass->lang['cblocks_menu']}</a> <img src='{$this->ipsclass->vars['img_url']}/menu_action_down.gif' alt='V' title='{$this->ipsclass->lang['global_open_menu']}' border='0' /></div>
		</td>
<script type='text/javascript'>
//<![CDATA[
{$cblock_js}
//]]>
</script>
") : ("")) . "
	" . (($blog['allow_entry']) ? ("
		<td width='1%'>
			<div class='popmenubutton' id='entry-menu'><a href='{$this->ipsclass->vars['blog_url']}req=postblog'>{$this->ipsclass->lang['entries_menu']}</a> <img src='{$this->ipsclass->vars['img_url']}/menu_action_down.gif' alt='V' title='{$this->ipsclass->lang['global_open_menu']}' border='0' /></div>
<script type='text/javascript'>
//<![CDATA[
menu_build_menu(
  \"entry-menu\",
  new Array( img_item + \" <a href='{$this->ipsclass->vars['blog_url']}req=postblog'>{$this->ipsclass->lang['add_entry']}</a>\",
             img_item + \" {$toggle_draft}\" ) );
//]]>
</script>
		</td>
	") : ("")) . "
	" . (($this->ipsclass->vars['blog_enable_rating']) ? ("
		<td width='1%'>
		" . (($blog['_allow_rating']) ? ("
			<div class='popmenubutton' id='blog-rating'>
				<a href='#blograting'>{$this->ipsclass->lang['blog_rating']}</a>
				{$blog['_blog_rate_img']} <img src='{$this->ipsclass->vars['img_url']}/menu_action_down.gif' alt='V' title='{$this->ipsclass->lang['global_open_menu']}' border='0' />
			</div>
		") : ("
			<div class='popmenubutton' id='blog-rating'>
				{$this->ipsclass->lang['blog_rating']} {$blog['_blog_rate_img']}
			</div>
		")) . "
		" . (($blog['_allow_rating']) ? ("
			<div id='blog-rating_menu' class='popupmenu-new' style='display:none;width:140px'>
				<div class='popupmenu-item'>
					<div id='blog-rating-wrapper'></div>
				</div>
				<div class='popupmenu-item'>
					{$this->ipsclass->lang['you_have_rated_x']} <span id='blog-rating-my-rating'>{$blog['current_rating']}</span>
				</div>
				<div class='popupmenu-item-last'>
					{$this->ipsclass->lang['total_ratings']} <span id='blog-rating-hits'>{$blog['blog_rating_count']}</span>
					" . (($this->ipsclass->vars['blog_rating_treshhold'] > 0 AND $blog['blog_rating_count'] < $this->ipsclass->vars['blog_rating_treshhold']) ? ("
						<br />{$this->ipsclass->lang['blog_rating_treshhold']} {$this->ipsclass->vars['blog_rating_treshhold']}
					") : ("")) . "
				</div>
			</div>
			<script type='text/javascript'>
				ipsmenu.register( \"blog-rating\" );
			</script>
		") : ("")) . "
		</td>
	") : ("")) . "
</tr>
</table>
" . (($this->ipsclass->member['id']) ? ("
<script type=\"text/javascript\">
//<![CDATA[
blog_rate.settings['allow_rating']       = parseInt(\"{$blog['_allow_rating']}\");
blog_rate.settings['default_rating']     = parseInt(\"{$blog['_rate_int']}\");
blog_rate.settings['img_base_url']       = ipb_var_image_url + '/folder_topic_view';
blog_rate.settings['div_rating_wrapper'] = 'blog-rating-wrapper';
blog_rate.settings['text_rating_image']  = 'blog-rating-img-';
blog_rate.languages['img_alt_rate']      = \"{$this->ipsclass->lang['blog_img_alt_rate']}\";
blog_rate.languages['rate_me']           = \"{$this->ipsclass->lang['blog_rate_me']}\";
blog_rate.init_rating_images();
//]]>
</script>
") : ("")) . "";
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// <ips:blog_wrapper:desc::trigger:>
//===========================================================================
function blog_wrapper($html="",$title="",$inline_settings="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xml:lang=\"en\" lang=\"en\" xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
<meta http-equiv=\"content-type\" content=\"text/html; charset=<!--BLOG.CHARSET-->\" />
<title>$title</title>
<!--BLOG.TEMPLATE.RSS-->
<!--BLOG.TEMPLATE.CSS-->
<script type=\"text/javascript\">
//<![CDATA[
 var ipb_var_st            = \"{$this->ipsclass->input['st']}\";
 var ipb_lang_tpl_q1       = \"{$this->ipsclass->lang['tpl_q1']}\";
 var ipb_var_s             = \"{$this->ipsclass->session_id}\";
 var ipb_var_phpext        = \"{$this->ipsclass->vars['php_ext']}\";
 var ipb_var_base_url      = \"{$this->ipsclass->js_base_url}\";
 var ipb_var_image_url     = \"{$this->ipsclass->vars['img_url']}\";
 var ipb_var_cookieid      = \"{$this->ipsclass->vars['cookie_id']}\";
 var ipb_var_cookie_domain = \"{$this->ipsclass->vars['cookie_domain']}\";
 var ipb_var_cookie_path   = \"{$this->ipsclass->vars['cookie_path']}\";
 var ipb_md5_check         = \"{$this->ipsclass->md5_check}\";
 var ipb_new_msgs          = {$this->ipsclass->member['new_msg']};
 var use_enhanced_js	   = {$this->ipsclass->can_use_fancy_js};
 var use_charset           = \"{$this->ipsclass->vars['gb_char_set']}\";
 var ipb_myass_chars_lang  = \"{$this->ipsclass->lang['myass_chars']}\";
 var ajax_load_msg		   = \"{$this->ipsclass->lang['ajax_loading_msg_new']}\";
 var ipb_var_settings_changed = 0;
 var ipb_var_settings_close = 0;
//]]>
</script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_ipsclass.js'></script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_global.js'></script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_menu.js'></script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['img_url']}/folder_js_skin/ips_menu_html.js'></script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/cache/lang_cache/{$this->ipsclass->lang_id}/lang_javascript.js'></script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_blog.js'></script>
<script type=\"text/javascript\">
//<![CDATA[
var ipsclass = new ipsclass();
ipsclass.init();
ipsclass.settings['do_linked_resize'] = parseInt( \"{$this->ipsclass->vars['blog_resize_linked_img']}\" );
ipsclass.settings['resize_percent']   = parseInt( \"{$this->ipsclass->vars['blog_resize_img_percent']}\" );
var blog_rate = new blog_rate();
//]]>
</script>
" . (($inline_settings) ? ("
<script type=\"text/javascript\">
//<![CDATA[
 var ipb_lang_delcat_sure = \"{$this->ipsclass->lang['delcat_sure']}\";
//]]>
</script>
") : ("")) . "
</head>
<body>
" . (($this->ipsclass->can_use_fancy_js != 0) ? ("
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/jscripts/ips_xmlhttprequest.js'></script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_global_xmlenhanced.js'></script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/jscripts/dom-drag.js'></script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_xhr_findnames.js'></script>
<script type=\"text/javascript\" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_blogxml.js'></script>
<div id='get-myassistant' style='display:none;width:400px;text-align:left;'>
<div class=\"borderwrap\">
 <div class='maintitle' id='myass-drag' title='{$this->ipsclass->lang['myass_drag']}'>
  <div style='float:right'><a href='#' onclick='document.getElementById(\"get-myassistant\").style.display=\"none\"'>[X]</a></div>
  <div>{$this->ipsclass->lang['myass_title']}</div>
 </div>
 <div id='myass-content' style='overflow-x:auto;'></div>
 </div>
</div>
<!-- Loading Layer -->
<div id='loading-layer' style='display:none'>
	<div id='loading-layer-shadow'>
	   <div id='loading-layer-inner'>
	 	<img src='style_images/<#IMG_DIR#>/loading_anim.gif' border='0' />
		<span style='font-weight:bold' id='loading-layer-text'>{$this->ipsclass->lang['ajax_loading_msg']}</span>
	    </div>
	</div>
</div>
<!-- / Loading Layer -->
<!-- Msg Layer -->
<div id='ipd-msg-wrapper'>
	<div id='ipd-msg-title'>
		<a href='#' onclick='document.getElementById(\"ipd-msg-wrapper\").style.display=\"none\"; return false;'><img src='style_images/<#IMG_DIR#>/close.png' alt='X' title='Close Window' class='ipd'></a> &nbsp; <strong>{$this->ipsclass->lang['gbl_sitemsg_header']}</strong>
	</div>
	<div id='ipd-msg-inner'><span style='font-weight:bold' id='ipd-msg-text'></span><div class='pp-tiny-text'>{$this->ipsclass->lang['gbl_auto_close']}</div></div>
</div>
<!-- Msg Layer -->
") : ("")) . "
" . (($inline_settings) ? ("
<div id='get-myblogsettings' style='display:none;width:700px;text-align:left'>
<div class=\"borderwrap\">
 <div class='maintitle' id='myblogset-drag' title='Click and hold to drag this window'>
  <div style='float:right'><a href='#' onclick='close_set_window()'>[X]</a></div>
  <div>{$this->ipsclass->lang['blog_settings_link']}</div>
 </div>
 <div id='myblogset-content'></div>
 </div>
</div>
<div id='ipb-get-members' style='border:1px solid #000; background:#FFF; padding:2px;position:absolute;width:210px;display:none;z-index:51'></div>
") : ("")) . "
<!--BLOG.TEMPLATE.HEADER-->
$html
<!--BLOG.TEMPLATE.QUICKSTATS-->
<!--BLOG.TEMPLATE.DEBUG-->
" . (($inline_settings) ? ("
<script type='text/javascript'>
cblock_init_dragdrop_vars();
</script>
") : ("")) . "
" . (($this->ipsclass->member['id']) ? ("
<script type='text/javascript'>
//<![CDATA[
menu_do_global_init();
//]]>
</script>
") : ("")) . "
<!--BLOG.TEMPLATE.COPYRIGHT-->
</div>
</body>
</html>";
//--endhtml--//
return $IPBHTML;
}



}

/*--------------------------------------------------*/
/*<changed bits>
blog_header,blog_wrapper
</changed bits>*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>