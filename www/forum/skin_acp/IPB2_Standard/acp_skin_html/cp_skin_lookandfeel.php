<?php

class cp_skin_lookandfeel {

var $ipsclass;


//===========================================================================
//  LOOK AND FEEL: SKIN DIFF
//===========================================================================
function skin_cache_settings( $form, $template ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform' action='{$this->ipsclass->base_url}&amp;act=rtempl&amp;code=cache_settings_save&amp;suid={$template['suid']}' method='POST'>
<div class='tableborder'>
 <div class='tableheaderalt'>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
  <td align='left' width='95%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;'>Cache Settings: {$template['func_name']}</td>
  <td align='right' width='5%' nowrap='nowrap'>
  &nbsp;
 </td>
 </tr>
</table>
 </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablerow1' width='60%' valign='top'><strong>Primary Cache File</strong><div class='desctext'>The primary cache file for this template bit</div></td>
  <td class='tablerow1' width='40%'><strong>{$form['_title']}</strong></td>
 </tr>
 <tr>
  <td class='tablerow1' width='60%' valign='top'><strong>Secondary Cache File(s)</strong><div class='desctext'>Select one or more secondary cache files for this template bit</div></td>
  <td class='tablerow1' width='40%'>{$form['group_names_secondary']}</td>
 </tr>
 </table>
 <div align='center' class='tablefooter'><input type='submit' value=' Save ' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function template_bits_bit_row_image( $id, $image ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img id='img-item-{$id}' src='{$this->ipsclass->skin_acp_url}/images/{$image}' border='0' alt='' />
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function template_bits_overview_row_normal( $group, $folder_blob, $count_string ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tablerow1' id='dv-{$group['group_name']}'>
 <div style='float:right'>
  ($count_string)&nbsp;{$group['easy_preview']}
 </div>
 <div align='left'>
   <img src='{$this->ipsclass->skin_acp_url}/images/folder.gif' alt='Template Group' style='vertical-align:middle' />
   {$folder_blob}&nbsp;<a style='font-size:11px' id='gn-{$group['group_name']}' onclick='template_load_bits("{$group['group_name']}", event)' title='{$group['easy_desc']}' href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=template-bits-list&id={$group['_id']}&p={$group['_p']}&group_name={$group['group_name']}&'>{$group['easy_name']}</a>
   <span id='match-{$group['group_name']}'></span>
 </div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function template_bits_bit_row( $sec, $custom_bit, $remove_button, $altered_image ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tablerow1' id='dvb-{$sec['func_name']}' title='Click this row and others to edit multiple templates at once' onclick='parent.template_toggle_bit_row("{$sec['func_name']}")' >
 <div style='float:right;width:auto;'>
  $remove_button
  <a style='text-decoration:none' title='Preview template bit as text' href='#' onclick='pop_win("act=rtempl&code=preview&suid={$sec['suid']}&type=text"); parent.template_cancel_bubble( event, true );'><img src='{$this->ipsclass->skin_acp_url}/images/te_text.gif' border='0' alt='Text Preview'></a>
  <a style='text-decoration:none' title='Preview template bit as HTML' href='#' onclick='pop_win("act=rtempl&code=preview&suid={$sec['suid']}&type=css");  parent.template_cancel_bubble( event, true );'><img src='{$this->ipsclass->skin_acp_url}/images/te_html.gif' border='0' alt='HTML Preview'>&nbsp;</a>
 </div>
 <div align='left'>
   <img src='{$this->ipsclass->skin_acp_url}/images/file.gif' title='Template Set:{$sec['set_id']}' alt='Template' style='vertical-align:middle' />
   {$altered_image}
   <a id='bn-{$sec['func_name']}' onclick='parent.template_load_editor("{$sec['func_name']}", event)' href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=template-edit-bit&bitname={$sec['func_name']}&p={$sec['_p']}&id={$sec['_id']}&group_name={$sec['group_name']}&type=single' title='template bit name: {$sec['func_name']}'>{$sec['easy_name']}</a>
   {$custom_bit}
 </div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function template_bits_bit_overview( $group, $content, $add_button ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tablerow3'>
 <div style='float:right;padding-top:3px'><strong>{$group['easy_name']}</strong></div>
 <div>
  <a href='#' onclick="parent.template_close_bits(); return false;" title='Close Window'><img src='{$this->ipsclass->skin_acp_url}/images/skineditor_close.gif' border='0' alt='Close' /></a>&nbsp;
  <!--<a href='#' onclick="toggleselectall(); return false;" title='Check/Uncheck all'><img src='{$this->ipsclass->skin_acp_url}/images/skineditor_tick.gif' border='0' alt='Check/Uncheck all' /></a>-->
 </div>
</div>
<div id='template-bits-container'>
{$content}
</div>
 <div style='background:#CCC'>
   <div align='left' style='padding:5px;margin-left:25px'>
   <div style='float:right'>$add_button</div>
   <div><input type='button' onclick='parent.template_load_bits_to_edit("{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=template-edit-bit&id={$this->ipsclass->input['id']}&p={$this->ipsclass->input['p']}&group_name={$group['group_name']}&type=multiple")' class='realbutton' value='Edit Selected' /></div>
 </div>
</div>
<script type="text/javascript">
//<![CDATA[
parent.template_bits_onload();
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
//  LOOK AND FEEL: TEMPLATES
//===========================================================================
function template_bits_overview($content, $javascript) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript">
//<![CDATA[
var lang_matches = "matches";
$javascript
//]]>
</script>
<script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_xhr_findnames.js'></script>
<script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_template.js'></script>
<div id='ipb-get-members' style='border:1px solid #000; background:#FFF; padding:2px;position:absolute;width:210px;display:none;z-index:10'></div>
<div class='tableborder'>
 <div class='tableheaderalt'>
  <table cellpadding='0' cellspacing='0' border='0' width='100%'>
  <tr>
  <td align='left' width='100%'>
   <div id='quick-search-box'>
    <form id='quick-search-form'>
     <input type='text' size='20' class='realwhitebutton' style='width:210px' name='searchkeywords' id='entered_template' autocomplete="off" value='' />&nbsp;<input type='button' onclick='template_find_bits(event)' class='realbutton' value='Find Template Bit' />
    </form>
   </div>
  </td>
  </tr>
  </table>
</div>
<div id='template-edit' style='height:0px;width:100%;display:none;z-index:1'><iframe id='te-iframe' name='te-iframe' onload='template_iframe_loaded( "te" )' style='width:0;height:0px;display:none' src='javascript:;'></iframe></div>
<table id='template-main-wrap' width='100%'>
	<tr>
   		<td valign='top' id='template-sections' style='width:100%;height:476px;max-height:476px;z-index:3;'>
    		<div style='margin:0px;padding:0px;width:100%;overflow:auto;height:476px;max-height:476px;'>
	 			$content
			</div>
   		</td>
   		
   		<td valign='top' id='template-bits' style='width:0%;display:none;height:476px;max-height:476px;'>
   			<iframe id='tb-iframe' name='tb-iframe' onload='template_iframe_loaded( "tb" )' style='width:0%;display:none;height:476px;max-height:476px;' src='javascript:;'></iframe>
  		</td>
 	</tr>
 	<tr>
 		<td colspan='2' align='center' class='tablefooter'>&nbsp;</td>
 	</tr>
</table>
<br clear='all' />
<br />
<div style='padding:4px;'><strong>Child skin set pop-up menu legend:</strong><br />
<img id='img-altered' src='{$this->ipsclass->skin_acp_url}/images/skin_item_altered.gif' border='0' alt='+' title='Altered from parent skin set' /> This item has been customized for this skin set.
<br /><img id='img-unaltered' src='{$this->ipsclass->skin_acp_url}/images/skin_item_unaltered.gif' border='0' alt='-' title='Unaltered from parent skin set' /> This item has not been customized from the master skin set.
<br /><img id='img-inherited' src='{$this->ipsclass->skin_acp_url}/images/skin_item_inherited.gif' border='0' alt='|' title='Inherited from parent skin set' /> This item has inherited customizations from the parent skin set.
</div>
<script type='text/javascript'>
//<![CDATA[
// INIT images
img_revert_blank = '{$this->ipsclass->skin_acp_url}/images/blank.gif';
img_revert_real  = '{$this->ipsclass->skin_acp_url}/images/te_revert.gif';
img_revert_width  = 44;
img_revert_height = 16;

template_init();
// INIT find names
init_js( 'quick-search-form', 'entered_template', 'get-template-names&id={$this->ipsclass->input['id']}' );
// Run main loop
setTimeout( 'main_loop()', 10 );
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
//  LOOK AND FEEL: TEMPLATES
//===========================================================================
function skin_sets_overview($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
  <td align='left' width='95%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;'>Skin Sets</td>
  <td align='right' width='5%' nowrap='nowrap'>
   <img id="menumainone" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /> &nbsp;
 </td>
 </tr>
</table>
 </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 $content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
<br />
<div><strong>Child skin set pop-up menu legend:</strong><br />
<img src='{$this->ipsclass->skin_acp_url}/images/skin_item_altered.gif' border='0' alt='+' title='Altered from parent skin set' /> This item has been customized for this skin set.
<br /><img src='{$this->ipsclass->skin_acp_url}/images/skin_item_unaltered.gif' border='0' alt='-' title='Unaltered from parent skin set' /> This item has not been customized from the master skin set.
<br /><img src='{$this->ipsclass->skin_acp_url}/images/skin_item_inherited.gif' border='0' alt='|' title='Inherited from parent skin set' /> This item has inherited customizations from the parent skin set.
</div>
<div id='menumainone_menu' style='display:none' class='popupmenu'>
	<form action='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=addset&id=-1' method='POST'>
	<div align='center'><strong>Create New Skin Set</strong></div>
	<div align='center'><input type='text' name='set_name' size='20' value='Enter skin set name' onfocus='this.value=""'></center></div>
	<div align='center'><input type='submit' value='Go' class='realdarkbutton' /></div>
	</form>
</div>
<script type="text/javascript">
	ipsmenu.register( "menumainone" );
</script>
EOF;
if ( IN_DEV == 1 )
{
$IPBHTML .= <<<EOF
<br />
<div align='center'>
  DEV: <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=exportmaster'>Export Master HTML</a>
  &middot; <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=exportbitschoose'>Export Template Bits</a>
  &middot; <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=exportmacro'>Export Master Macros</a>
</div>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function skin_sets_overview_row( $r, $forums, $hidden, $default, $menulist, $i_sets, $no_sets, $folder_icon, $line_image, $css_extra ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
   <!--$i_sets,$no_sets-->{$line_image}<!--ID:{$r['set_skin_set_id']}--><img src='{$this->ipsclass->skin_acp_url}/images/{$folder_icon}' border='0' alt='Skin' style='vertical-align:middle' />
   <strong style='{$css_extra}'>{$r['set_name']}</strong>
 </td>
 <td class='tablerow1' width='5%' nowrap='nowrap' align='center'>{$forums} {$hidden} {$default}</td>
 <td class='tablerow1' width='5%'><img id="menu{$r['set_skin_set_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$r['set_skin_set_id']}",
  new Array(
			$menulist
  		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function skin_sets_overview_row_menulist( $r ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
img_edit   + " <!--ALTERED.wrappper--><a href='{$this->ipsclass->base_url}&section={$this->ipsclass->section_code}&act=wrap&code=edit&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit Board Header & Footer Wrapper</a>",
img_edit   + " <!--ALTERED.templates--><a href='{$this->ipsclass->base_url}&section={$this->ipsclass->section_code}&act=templ&code=template-sections-list&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit Template HTML</a>",
img_edit   + " <!--ALTERED.css--><a href='{$this->ipsclass->base_url}&section={$this->ipsclass->section_code}&act=style&code=edit&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit Stylesheet (CSS Advanced Mode)</a>",
img_edit   + " <!--ALTERED.css--><a href='{$this->ipsclass->base_url}&section={$this->ipsclass->section_code}&act=style&code=colouredit&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit Colours (CSS Easy Mode)</a>",
img_edit   + " <!--ALTERED.macro--><a href='{$this->ipsclass->base_url}&section={$this->ipsclass->section_code}&act=image&code=edit&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit Replacement Macros</a>",
img_edit   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=edit&id={$r['set_skin_set_id']}'>Edit Settings...</a>",
EOF;
if ( $r['set_skin_set_id'] != 1 )
{
$IPBHTML .= <<<EOF

img_item   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=revertallform&id={$r['set_skin_set_id']}'>Revert All Skin Customizations...</a>",
img_export   + " <a href='{$this->ipsclass->base_url}&section={$this->ipsclass->section_code}&act=import&code=showexportpage&id={$r['set_skin_set_id']}'>Export Skin Set...</a>",
img_view   + " <a href='{$this->ipsclass->base_url}&section={$this->ipsclass->section_code}&act=skindiff&code=skin_diff_from_skin&skin_id={$r['set_skin_set_id']}'>Generate HTML Differences Report...</a>",

img_delete   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=remove&id={$r['set_skin_set_id']}'>Remove Skin Set...</a>",
EOF;
}
if ( $r['set_skin_set_id'] != 1 AND ! $r['set_skin_set_parent'] )
{
$IPBHTML .= <<<EOF

img_add   + " <a  href='#' onclick=\"addnewpop('{$r['set_skin_set_id']}','menu_{$r['set_skin_set_id']}')\">Add New Child Skin Set...</a>",
EOF;
}

/* 
This line will give you a link to run CSS diff reports

img_view   + " <a href='#' onclick='ipsclass.pop_up_window(\"{$this->ipsclass->base_url}&act=rtempl&code=css_diff&id={$r['set_skin_set_id']}\", 800, 600 )'>Generate CSS Differences Report</a>",

However this is a very resource intensive operation, and should only be done on a development server
*/

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// SKIN REMAP FORM
//===========================================================================
function skin_remap_form( $form, $title, $formcode, $button, $remap ) {

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
//]]>
</script>
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=$formcode&amp;map_id={$remap['map_id']}' id='mainform' method='POST'>
<div class='tabwrap'>
	<div id='tabtab-1' class='taboff'>General Settings</div>
</div>
<div class='tabclear'>$title</div>
<div class='tableborder'>
<div id='tabpane-1' class='formmain-background'>
	<table cellpadding='0' cellspacing='0' border='0' width='100%'>
	 <tr>
	   <td>
		<fieldset class='formmain-fieldset'>
		    <legend><strong>General Settings</strong></legend>
		     <table cellpadding='0' cellspacing='0' border='0' width='100%'>
			  <tr>
			   <td width='40%' class='tablerow1'><strong>Title</strong><div class='desctext'>The title is for your information only so you can distinguish between items.</div></td>
			   <td width='60%' class='tablerow2'>{$form['map_title']}</td>
			  </tr>
			  <tr>
			   <td width='40%' class='tablerow1'><strong>Type</strong><div class='desctext'>"Contains" will match the string entered below if it occurs anywhere in the query string. "Is Exactly" will look to match the string entered below with the entire query string.</div></td>
			   <td width='60%' class='tablerow2'>{$form['map_match_type']}</td>
			  </tr>
			  <tr>
			   <td width='40%' class='tablerow1'><strong>URL</strong><div class='desctext'>Enter the string to match in the query string.</div></td>
			   <td width='60%' class='tablerow2'>{$form['map_url']}</td>
			  </tr>
			  <tr>
			   <td width='40%' class='tablerow1'><strong>Skin Set</strong><div class='desctext'>Which skin set to map to.</div></td>
			   <td width='60%' class='tablerow2'><select name='map_skin_set_id'>{$form['skin_list']}</select></td>
			  </tr>
			 </table>
		 </fieldset>
		</td>
	</tr>
	</table>
</div>
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

//===========================================================================
//  LOOK AND FEEL: SKIN REMAP: MAIN
//===========================================================================
function skin_remap_overview($remaps=array()) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_template.js'></script>
<div class='tableborder'>
 <div class='tableheaderalt'>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
  <td align='left' width='95%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;'>Skin Remapping</td>
 </tr>
</table>
 </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
  <td class='tablesubheader' width='40%'>Title</td>
  <td class='tablesubheader' width='20%'>Skin Set</a>
  <td class='tablesubheader' width='30%'>Added</td>
  <td class='tablesubheader' width='5%'><img id="menumainone" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></a>
 </tr>
EOF;
if ( count( $remaps ) )
{
	foreach( $remaps as $data )
	{
$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'><img src='{$this->ipsclass->skin_acp_url}/images/folder_components/skinremap/remap_row.png' border='0' class='ipd' /></td>
 <td class='tablerow1'><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=remap_edit&amp;map_id={$data['map_id']}'><strong>{$data['map_title']}</strong></a></td>
 <td class='tablerow1'>{$data['_name']}</td>
 <td class='tablerow1' nowrap='nowrap'>{$data['_date']}</td>
 <td class='tablerow1' width='5%'><img id="menu{$data['map_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$data['map_id']}",
  new Array(
			img_view     + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=remap_edit&amp;map_id={$data['map_id']}'>Edit Mapping...</a>",
			img_delete   + " <a href='#' onclick=\"checkdelete('{$this->ipsclass->form_code}&code=remap_remove&map_id={$data['map_id']}')\">REMOVE Mapping...</a>"
			  		    ) );
 </script>
EOF;
	}
}
else
{
$IPBHTML .= <<<EOF
	<tr>
	 <td class='tablerow1' colspan='5'><em>There are no skin set URL remappings set up currently.</em></td>
	</tr>
EOF;
}
$IPBHTML .= <<<EOF
 </table>
 <div align='right' class='tablefooter'>&nbsp;</div>
</div>
<script type="text/javascript">
  menu_build_menu(
  "menumainone",
  new Array( 
  			 img_add + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=remap_add'>Add new URL remap...</a>"
           ) );
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
//  LOOK AND FEEL: SKIN DIFF: MAIN
//===========================================================================
function skin_diff_main_overview($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_template.js'></script>
<div class='tableborder'>
 <div class='tableheaderalt'>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
  <td align='left' width='95%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;'>Skin Differences Reports</td>
 </tr>
</table>
 </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='90%'><strong>Difference Title</strong></td>
  <td class='tablesubheader' width='5%'>Created</a>
  <td class='tablesubheader' width='5%'>&nbsp;</a>
 </tr>
 $content
 </table>
 <div align='right' class='tablefooter'>&nbsp;</div>
</div>
<br />
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=skin_diff' enctype='multipart/form-data' method='POST'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>Create New Skin Difference Report</div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
  <td class='tablerow1'><strong>Enter a new skin difference title</strong><div class='desctext'>This title can be anything and will be used in the list above when the comparison is completed</div></td>
  <td class='tablerow2'><input class='textinput' type='text' size='30' name='diff_session_title' /></td>
 </tr>
 <tr>
  <td class='tablerow1'><strong>Skip all new/missing template bits?</strong><div class='desctext'>If you are comparing an old ipb_templates.xml file from an older IPB release, then you may wish to disable this. If you are comparing an XML file from a customized skin set, you should enable this.</div></td>
  <td class='tablerow2'><input class='textinput' type='checkbox' value='1' name='diff_session_ignore_missing' /></td>
 </tr>
 <tr>
  <td class='tablerow1'><strong>Select a valid XML skin export file from your computer.</strong><div class='desctext'>This will be compared against your master HTML templates - so make sure they're up-to-date before running this tool</div></td>
  <td class='tablerow2'><input class='textinput' type='file' size='30' name='FILE_UPLOAD' /></td>
 </tr>
 </table>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='Import' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function skin_diff_main_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'> <strong>{$data['diff_session_title']}</strong></td>
 <td class='tablerow1' width='5%' nowrap='nowrap' align='center'>{$data['_date']}</td>
 <td class='tablerow1' width='5%'><img id="menu{$data['diff_session_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$data['diff_session_id']}",
  new Array(
			img_view   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=skin_diff_view&amp;diff_session_id={$data['diff_session_id']}'>View Difference Results...</a>",
			img_delete   + " <a href='#' onclick=\"checkdelete('{$this->ipsclass->form_code}&code=skin_diff_remove&diff_session_id={$data['diff_session_id']}')\">REMOVE Difference Results...</a>",
			img_export   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=skin_diff_export&amp;diff_session_id={$data['diff_session_id']}'>Create HTML Export...</a>"
  		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
//  LOOK AND FEEL: SKIN DIFF
//===========================================================================
function skin_diff_overview($content, $missing, $changed) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->ipsclass->skin_acp_url}/acp_template.js'></script>
<div class='tableborder'>
 <div class='tableheaderalt'>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
  <td align='left' width='95%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;'>Skin Differences</td>
  <td align='right' width='5%' nowrap='nowrap'>
  &nbsp;
 </td>
 </tr>
</table>
 </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='90%'><strong>Template Bit Name</strong></td>
  <td class='tablesubheader' width='5%'>Difference</a>
  <td class='tablesubheader' width='5%'>Size</a>
  <td class='tablesubheader' width='5%'>&nbsp;</a>
 </tr>
 $content
 </table>
 <div align='right' class='tablefooter'>$missing new template bits and $changed changed template bits</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES: NEW GROUP
//===========================================================================
function skin_diff_row_newgroup( $group_name ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td colspan='4' class='tablerow3'>
   <strong>{$group_name}</strong>
 </td>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function skin_diff_row( $template_bit_name, $template_bit_size, $template_bit_id, $diff_is, $template_bit_id_safe ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
   <strong>{$template_bit_name}</strong>
 </td>
 <td class='tablerow1' width='5%' nowrap='nowrap' align='center'>{$diff_is}</td>
 <td class='tablerow1' width='5%' nowrap='nowrap' align='center'>{$template_bit_size}</td>
 <td class='tablerow1' width='5%'><img id="menu{$template_bit_id_safe}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$template_bit_id_safe}",
  new Array(
			img_view   + " <a href='#' onclick=\"return template_view_diff('$template_bit_id')\">View Differences...</a>"
  		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function skin_css_view_bit( $diff ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
$diff
</div>
<div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
 <span class='diffred'>Removed HTML</span> &middot; <span class='diffgreen'>Added HTML</span>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function skin_diff_view_bit( $template_bit_name, $template_group, $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
<strong>$template_group &gt; $template_bit_name</strong>
</div>
<div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
$content
</div>
<div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
 <span class='diffred'>Removed HTML</span> &middot; <span class='diffgreen'>Added HTML</span>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function skin_diff_export_row( $func_name, $func_group, $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
<h2>$func_group <span style='color:green'>&gt;</span> $func_name</h2>
<hr>
$content
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// LOOK AND FEEL: TEMPLATES
//===========================================================================
function skin_diff_export_overview( $content, $missing, $changed, $title, $date ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<html>
 <head>
  <title>$title export</title>
  <style type="text/css">
   BODY
   {
   	font-family: verdana;
   	font-size:11px;
   	color: #000;
   	background-color: #CCC;
   }
   
   del,
   .diffred
   {
	   background-color: #D7BBC8;
	   text-decoration:none;
   }
   
   ins,
   .diffgreen
   {
	   background-color: #BBD0C8;
	   text-decoration:none;
   }
   
   h1
   {
   	font-size: 18px;
   }
   
   h2
   {
   	font-size: 18px;
   }
  </style>
 </head>
<body>
  <div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
  <h1>$title (Exported: $date)</h1>
  <strong>$missing new template bits $changed changed template bits</strong>
  </div>
  <br />
  $content
  <br />
  <div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
   <span class='diffred'>Removed HTML</span> &middot; <span class='diffgreen'>Added HTML</span>
  </div>
</body>
<html>
EOF;

//--endhtml--//
return $IPBHTML;
}


function emoticon_overview_wrapper_addform( )
{
	
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
function addfolder()
{
	document.macroform.emoset.value       = '';
	document.macroform.code.value         = 'emo_setadd';
	document.macroform.submitbutton.value = 'Add Folder';
	scroll(0,0);
	togglediv( 'popbox', 1 );
	return false;
}

function editfolder(id)
{
	document.macroform.submitbutton.value = 'Edit Folder Name';
	document.macroform.id.value     = id;
	document.macroform.code.value   = 'emo_setedit';
	document.macroform.emoset.value = id;
	scroll(0,0);
	togglediv( 'popbox', 1 );
	return false;
}
</script>
<div align='center' style='position:absolute;display:none;text-align:center' id='popbox'>
 <form name='macroform' action='{$this->ipsclass->base_url}' method='post'>
 <input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
 <input type='hidden' name='act'     value='emoticons' />
 <input type='hidden' name='section' value='lookandfeel' />
 <input type='hidden' name='code'    value='emo_setadd' />
 <input type='hidden' name='id' value='' />

 <table cellspacing='0' width='500' align='center' cellpadding='6' style='background:#EEE;border:2px outset #555;'>
 <tr>
  <td width='1%' nowrap='nowrap' valign='top' align='center'>
   <b>Folder name (alphanumerics only)</b><br><input class='textinput' name='emoset' type='text' size='40' />
   <br /><br />
   <center><input type='submit' class='realbutton' value='Add Folder' name='submitbutton' /> <input type='button' class='realdarkbutton' value='Close' onclick="togglediv('popbox');" /></center>
  </td>
 </tr>

 </table>
 </form>
</div>

EOF;

return $IPBHTML;
}


function emoticon_overview_wrapper( $content )
{
	
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->ipsclass->base_url}' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<input type='hidden' name='code' value='emo_upload'>
<input type='hidden' name='act' value='emoticons'>
<input type='hidden' name='MAX_FILE_SIZE' value='10000000000'>
<input type='hidden' name='dir_default' value='1'>
<input type='hidden' name='section' value='lookandfeel'>
<div class='tableborder'>
<div class='tableheaderalt'>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
  <td align='left' width='95%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;'>Current Emoticon Folders</td>
  <td align='right' width='5%' nowrap='nowrap'>
   <img id="menumainone" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /> &nbsp;
 </td>
 </tr>
</table>
</div>

<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'><tr>
<td class='tablesubheader' width='50%' align='center'>Emoticon Folder</td>

<td class='tablesubheader' width='5%' align='center'>Upload</td>
<td class='tablesubheader' width='20%' align='center'># Disk Folder</td>
<td class='tablesubheader' width='20%' align='center'># Emo. Group</td>
<td class='tablesubheader' width='5%' align='center'>Options</td>
</tr>

{$content}

</table>
</div>
<br />

EOF;

if( SAFE_MODE_ON )
{
$IPBHTML .= <<<EOF
	</form>
EOF;
}
else
{
$IPBHTML .= <<<EOF
<div class='tableborder'>
	 <div class='tableheaderalt'>Upload Emoticons</div>
	 <table width='100%' border='0' cellpadding='4' cellspacing='0'>
	 <tr>
	  <td width='50%' class='tablerow1' align='center'><input type='file' value='' class='realbutton' name='upload_1' size='30' /></td>

	  <td width='50%' class='tablerow2' align='center'><input type='file' class='realbutton' name='upload_2' size='30' /></td>
	 </tr>
	 <tr>
	  <td width='50%' class='tablerow1' align='center'><input type='file' class='realbutton' name='upload_3' size='30' /></td>
	  <td width='50%' class='tablerow2' align='center'><input type='file' class='realbutton' name='upload_4' size='30' /></td>
	 </tr>
	 </table>
	 <div class='tablesubheader' align='center'><input type='submit' value='Upload emoticons into checked folders' class='realdarkbutton' /></form></div>
</div>
EOF;
}

$IPBHTML .= <<<EOF

<script type="text/javascript">
  menu_build_menu(
  "menumainone",
  new Array( 
  			 img_add + " <a href='#' onclick='addfolder(); return false;' style='color:#000;'><strong>Create New Folder</strong></a>"
           ) );
</script>
EOF;

return $IPBHTML;
}


function emoticon_overview_row( $data=array() )
{
	
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<tr>
	 
	 <td class='tablerow2' valign='middle'>
	 	<div style='width:auto;float:right;'><img src='{$this->ipsclass->skin_acp_url}/images/{$data['icon']}' title='{$data['title']}' alt='{$data['icon']}' /></div>
	 	{$data['line_image']}<img src='{$this->ipsclass->skin_acp_url}/images/emoticon_folder.gif' border='0'>&nbsp;<a href='{$this->ipsclass->base_url}&section=lookandfeel&amp;act=emoticons&code=emo_manage&id={$data['dir']}' title='Manage this emoticon set'><b>{$data['dir']}</b></a>
	 </td>

	 <td class='tablerow1' valign='middle'><center>{$data['checkbox']}</center></td>
	 <td class='tablerow2' valign='middle'><center>{$data['count']}</center></td>
	 <td class='tablerow1' valign='middle'><center>{$data['dir_count']}</center></td>
	 <td class='tablerow2' valign='middle'><center><img id="menu{$data['dir']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
	</tr>
	
<script type="text/javascript">
  menu_build_menu(
  "menu{$data['dir']}",
  new Array(
			img_item   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=emo_manage&id={$data['dir']}'>{$data['link_text']}...</a>",
EOF;

if( $data['dir'] != 'default' OR IN_DEV == 1 )
{
$IPBHTML .= <<<EOF
  			img_edit   + " <a href='#' onclick=\"editfolder('{$data['dir']}')\">Edit Folder Name...</a>",
  			img_delete   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=emo_setremove&id={$data['dir']}'>Delete Folder...</a>"
EOF;
}
else
{
$IPBHTML .= <<<EOF
	img_delete + " <i>The default emoticon set cannot be deleted</i>"
EOF;
}

$IPBHTML .= <<<EOF
  		    ) );
 </script>
 
EOF;

return $IPBHTML;
}

}


?>