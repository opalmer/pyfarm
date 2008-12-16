<?php

class cp_skin_rss {

var $ipsclass;


//===========================================================================
// RSS
//===========================================================================
function rss_export_overview($content, $page_links) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>RSS Export Streams</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='90%'>RSS Title</td>
  <td class='tablesubheader' width='5%' align='center'>Enabled</td>
  <td class='tablesubheader' width='5%'><img id="menumainone" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
 </tr>
EOF;

if( $page_links != "" )
{
$IPBHTML .= <<<EOF
 <tr>
  <td class='tablesubheader' colspan='3' align='right'>
  	{$page_links}
  </td>
 </tr>
EOF;
}

$IPBHTML .= <<<EOF
 {$content}
EOF;

if( $page_links != "" )
{
$IPBHTML .= <<<EOF
 <tr>
  <td class='tablesubheader' colspan='3' align='right'>
  	{$page_links}
  </td>
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
  new Array( img_add   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssexport_add'>Create New RSS Export Stream...</a>",
  			 img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssexport_recache&amp;rss_export_id=all'>Update All RSS Export Caches...</a>"
           ) );
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// RSS
//===========================================================================
function rss_export_overview_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
   <a target='_blank' href='{$this->ipsclass->vars['board_url']}/index.php?act=rssout&amp;id={$data['rss_export_id']}'><img src='{$this->ipsclass->skin_acp_url}/images/rss.png' border='0' alt='RSS' style='vertical-align:top' /></a>
   <strong>{$data['rss_export_title']}</strong>
 </td>
 <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$data['_enabled_img']}' border='0' alt='YN' class='ipd' /></td>
 <td class='tablerow1'><img id="menu{$data['rss_export_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$data['rss_export_id']}",
  new Array(
			img_edit   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssexport_edit&amp;rss_export_id={$data['rss_export_id']}'>Edit RSS Export Stream...</a>",
  			img_delete + " <a href='#' onclick='maincheckdelete(\"{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssexport_delete&amp;rss_export_id={$data['rss_export_id']}\");'>Delete RSS Export Stream...</a>",
  			img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssexport_recache&amp;rss_export_id={$data['rss_export_id']}'>Recache RSS Export Stream...</a>"
  		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// RSS FORM
//===========================================================================
function rss_export_form($form, $title, $formcode, $button, $rssstream) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=$formcode&amp;rss_export_id={$rssstream['rss_export_id']}' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>$title</div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
   <td width='40%' class='tablerow1'><strong>RSS Export Stream (Channel) Title</strong></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_title']}</td>
 </tr>
<tr>
   <td width='40%' class='tablerow1'><strong>RSS Export Stream (Channel) Description</strong></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_desc']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>RSS Export Stream Image</strong><div class='desctext'>Used to show an image in RSS readers</div></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_image']} <span class='desctext'>* Optional</span></td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>RSS Export Enabled</strong></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_enabled']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>RSS Export Includes First Post of Topic</strong></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_include_post']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>RSS Export No. Items</strong><div class='desctext'>Exports <em>n</em> number of topics</div></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_count']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>RSS Export Order By Field</strong></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_order']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>RSS Export Sort By</strong></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_sort']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Export Topics From Forum(s)</strong><div class='desctext'>IMPORTANT: No permission checks are done. Topics will be exported from chosen forums regardless of the viewer's permissions.</div></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_forums']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>RSS Export Cache Frequency</strong><div class='desctext'>Updates the RSS cache every <em>n</em> minutes</div></td>
   <td width='60%' class='tablerow2'>{$form['rss_export_cache_time']} <span class='desctext'>minutes</span></td>
 </tr>
 </table>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='$button' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// RSS FORM
//===========================================================================
function rss_import_remove_articles_form( $rssstream, $article_count ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssimport_remove_complete&amp;rss_import_id={$rssstream['rss_import_id']}' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>Remove Posted Topics From Stream: {$rssstream['rss_import_title']}</div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
   <td colspan='2' class='tablerow1'>You may remove topics that have been posted from an RSS import stream. This stream has created <strong>{$article_count}</strong> topic(s).</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Remove the last <em>n</em> imported topics</strong><div class='desctext'>Leave blank to remove them all</div></td>
   <td width='60%' class='tablerow2'><input type='text' name='remove_count' value='10' /></td>
 </tr>
 </table>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='Remove (No more confirmation screens)' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// RSS FORM
//===========================================================================
function rss_import_form($form, $title, $formcode, $button, $rssstream) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_xhr_findnames.js'></script>
<script type='text/javascript'>
<!--
function enable_auth_boxes()
{
	auth_req = document.getElementById('rss_import_auth_userinfo');
	if( auth_req.style.display == 'none' )
	{
		auth_req.style.display = '';
	}
	else
	{
		auth_req.style.display = 'none';
	}
}

function do_validate()
{
	formobj = document.getElementById('rssimport_validate');
	formobj.value = "1";
	document.getElementById('rssimport_form').submit();
}
	
-->
</script>
<form id='rssimport_form' action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=$formcode&amp;rss_import_id={$rssstream['rss_import_id']}' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<input id='rssimport_validate' type='hidden' name='rssimport_validate' value='0' />
<div class='tableborder'>
 <div class='tableheaderalt'>$title</div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
   <td class='tablerow1'>
      <fieldset>
       <legend><strong>RSS Import Basics</strong></legend>
 		<table cellpadding='0' cellspacing='0' border='0' width='100%'>
 		<tr>
   		  <td width='40%' class='tablerow1'><strong>RSS Import Stream Title</strong></td>
   		  <td width='60%' class='tablerow2'>{$form['rss_import_title']}</td>
 		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Stream URL</strong><div class='desctext'>This must be an RDF or RSS feed</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_url']}</td>
		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Stream Character Set</strong><div class='desctext'>Examples: ISO-8859-1, UTF-8. Use UTF-8 if in doubt.</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_charset']}</td>
		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Enabled</strong></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_enabled']}</td>
		</tr>
	   </table>
	 </fieldset>
  </td>
 </tr>
  <tr>
   <td class='tablerow1'>
      <fieldset>
       <legend><strong>RSS Import htaccess Authentication</strong></legend>
 		<table cellpadding='0' cellspacing='0' border='0' width='100%'>
 		<tr>
   		  <td width='40%' class='tablerow1'><strong>Does this stream require .htaccess Authentication?</strong><div class='desctext'>Most streams do not require authentication</div></td>
   		  <td width='60%' class='tablerow2'>{$form['rss_import_auth']}</td>
 		</tr>
		<tr>
		  <td colspan='2' width='100%' id='rss_import_auth_userinfo' {$form['rss_div_show']}>
		   <table cellpadding='0' cellspacing='0' border='0' width='100%'>
		    <tr>
		  		<td width='40%' class='tablerow1'><strong>RSS Import Stream Username</strong></td>
		 		<td width='60%' class='tablerow2'>{$form['rss_import_auth_user']}</td>
		 	</tr>
		 	<tr>
		  		<td width='40%' class='tablerow1'><strong>RSS Import Stream Password</strong></td>
		  		<td width='60%' class='tablerow2'>{$form['rss_import_auth_pass']}</td>
			</tr>
		  </table>
		 </td>
		</tr>
	   </table>
	 </fieldset>
  </td>
 </tr>
 <tr>
  <td class='tablerow1'>
  	<div id='ipb-get-members' style='border:1px solid #000; background:#FFF; padding:2px;position:absolute;width:170px;display:none;z-index:100'></div>
      <fieldset>
       <legend><strong>RSS Import Content</strong></legend>
 		<table cellpadding='0' cellspacing='0' border='0' width='100%'>
 		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Into Forum</strong><div class='desctext'>Choose a forum to import each RSS item as a new topic</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_forum_id']}</td>
		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Preserve HTML</strong><div class='desctext'>If yes, raw HTML is preserved along with any 'badwords' - you MUST have HTML enabled when editing imported posts or HTML will be removed upon edit. If no, HTML is converted to BBCode where possible.</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_allow_html']}</td>
		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Poster's Name</strong><div class='desctext'>This will post the RSS topic under this person's account (member display name)</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_mid']}</td>
		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Increment Poster's Post Count</strong><div class='desctext'>This will increment this poster's post count in forums where post counts are enabled</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_inc_pcount']}</td>
		</tr>
		 <tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Add Link To Post</strong><div class='desctext'>BBCode allowed: {url} = URL to article<br />If completed, this will add a link to the article source (if available) to the post body</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_showlink']} <div class='desctext'>*Leave blank to not include a link in the post</div></td>
		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Topic Open</strong><div class='desctext'>If 'yes' the topic will be posted as open. 'No', topic will be closed</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_topic_open']}</td>
		</tr>
	    <tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Topic Hidden</strong><div class='desctext'>If 'yes' the topic will be posted as invisible. 'No', topic will be visible</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_topic_hide']}</td>
		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Topic Prefix</strong><div class='desctext'>This prefix will be added to the beginning of the RSS item's title</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_topic_pre']}</td>
		</tr>
	   </table>
	 </fieldset>
	</td>
  </tr>
  <tr>
    <td class='tablerow1'>
      <fieldset>
       <legend><strong>RSS Import Settings</strong></legend>
 		<table cellpadding='0' cellspacing='0' border='0' width='100%'>
 		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Per Go</strong><div class='desctext'>Imports <em>n</em> articles per update. Importing is moderately resource intensive.</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_pergo']}</td>
		</tr>
		<tr>
		  <td width='40%' class='tablerow1'><strong>RSS Import Refresh</strong><div class='desctext'>Checks for new articles every <em>n</em> minutes. Minimum of 30 minutes, regardless of input.</div></td>
		  <td width='60%' class='tablerow2'>{$form['rss_import_time']}</td>
		</tr>
	   </table>
	 </fieldset>
	</td>
  </tr>
 </table>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='$button' /> &nbsp;&nbsp;&nbsp;
 										 <input type='button' class='realbutton' value='Validate Stream' onclick='do_validate();' /></div>
</div>
</form>
<script type="text/javascript">
	// INIT find names
	init_js( 'rssimport_form', 'rss_import_mid');
	// Run main loop
	var tmp = setTimeout( 'main_loop()', 10 );
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// RSS
//===========================================================================
function rss_import_overview($content, $page_links) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Quick-Validate an RSS Stream?</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablerow1'><b>Enter the URL:</b></td>
  <td class='tablerow2'><form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssimport_validate' method='post'><input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' /><input type='text' size='50' name='rss_url' value='http://' /> <input type='submit' class='realbutton' value='Validate' /></form></td>
 </tr>
 </table>
</div>
<br />  
<div class='tableborder'>
 <div class='tableheaderalt'>RSS Import Streams</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='90%'>RSS Title</td>
  <td class='tablesubheader' width='5%' align='center'>Enabled</td>
  <td class='tablesubheader' width='5%'><img id="menumainone" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
 </tr>
EOF;

if( $page_links != "" )
{
$IPBHTML .= <<<EOF
 <tr>
  <td class='tablesubheader' colspan='3' align='right'>
  	{$page_links}
  </td>
 </tr>
EOF;
}

$IPBHTML .= <<<EOF
 {$content}
EOF;

if( $page_links != "" )
{
$IPBHTML .= <<<EOF
 <tr>
  <td class='tablesubheader' colspan='3' align='right'>
  	{$page_links}
  </td>
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
  new Array( img_add   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssimport_add'>Create New RSS Import Stream...</a>",
  			 img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssimport_recache&amp;rss_import_id=all'>Update All RSS Imports...</a>"
           ) );
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// RSS
//===========================================================================
function rss_validate_msg( $info ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
  <span class='{$info['class']}'>{$info['msg']}</span>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// RSS
//===========================================================================
function rss_import_overview_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
   <a target='_blank' href='{$data['rss_import_url']}'><img src='{$this->ipsclass->skin_acp_url}/images/rss.png' border='0' alt='RSS' style='vertical-align:top' /></a>
   <strong>{$data['rss_import_title']}</strong>
 </td>
 <td class='tablerow2' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/{$data['_enabled_img']}' border='0' alt='YN' class='ipd' /></td>
 <td class='tablerow1'><img id="menu{$data['rss_import_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$data['rss_import_id']}",
  new Array(
			img_edit   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssimport_edit&amp;rss_import_id={$data['rss_import_id']}'>Edit RSS Import Stream...</a>",
  			img_delete   + " <a href='#' onclick='maincheckdelete(\"{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssimport_delete&amp;rss_import_id={$data['rss_import_id']}\");'>Delete RSS Import Stream...</a>",
  			img_delete   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssimport_remove&amp;rss_import_id={$data['rss_import_id']}'>Remove RSS Articles...</a>",
  			img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssimport_recache&amp;rss_import_id={$data['rss_import_id']}'>Update RSS Import...</a>",
  			img_item   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=rssimport_validate&amp;rss_id={$data['rss_import_id']}'>Validate RSS Stream...</a>"
  		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}



}


?>