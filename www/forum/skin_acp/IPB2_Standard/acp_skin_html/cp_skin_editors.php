<?php

class cp_skin_editors {

var $ipsclass;

//===========================================================================
// <ips:ips_editor:desc::trigger:>
//===========================================================================
function ips_editor($form_field="",$initial_content="",$images_path="",$inc_path="",$rte_mode=0,$editor_id='ed-0',$smilies='', $bbcode='') {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<!--top-->
EOF;
if ( $editor_id == 'ed-0' )
{
$IPBHTML .= <<<EOF
<script type="text/javascript" src="{$this->ipsclass->vars['board_url']}/jscripts/ips_text_editor.js"></script>
<script type="text/javascript" src="{$this->ipsclass->vars['board_url']}/jscripts/ips_text_editor_func.js"></script>
<script type="text/javascript" src='{$this->ipsclass->vars['board_url']}/cache/lang_cache/{$this->ipsclass->lang_id}/lang_javascript.js'></script>
<style type='text/css'>
@import url( "{$images_path}css_rte.css" );
</style>
EOF;
}
$IPBHTML .= <<<EOF
<input type='hidden' name='{$editor_id}_wysiwyg_used' id='{$editor_id}_wysiwyg_used' value='{$rte_mode}' />
<input type='hidden' name='editor_ids[]' value='{$editor_id}' />
<!-- Extended options wrapper -->
<div id='{$editor_id}_options-bar' class="rte-option-panel"></div>
<!-- Main editor wrapper -->
<div id='{$editor_id}_main-bar' class="rte-buttonbar">
    <div id='{$editor_id}_controls' style='display:none;padding:6px;text-align:left'>
        <table cellpadding="2" cellspacing="0" width='100%'>
        <tr>
        <td width='1%' align='left' nowrap='nowrap'>
          <table cellpadding='0' cellspacing='0' width='100%'>
           <tr>
            <td><div class='rte-normal' id="{$editor_id}_cmd_removeformat"><img src="{$images_path}rte-remove-formatting.gif" alt="{$this->ipsclass->lang['js_tt_noformat']}" title="{$this->ipsclass->lang['js_tt_noformat']}" ></div></td>
            <td><div class='rte-normal' id="{$editor_id}_cmd_togglesource"><img src="{$images_path}rte-toggle-html.gif" alt="{$this->ipsclass->lang['js_tt_htmlsource']}" title="{$this->ipsclass->lang['js_tt_htmlsource']}" ></div></td>
			<td><img class="rteVertSep" src="{$images_path}" width="1" height="20" border="0" alt=""></td> 
			<td>
				<div class='rte-menu-button' id='{$editor_id}_popup_special' title='{$this->ipsclass->lang['js_tt_insert_item']}' style='margin-right:2px;width:148px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_special' style='width:130px'>{$this->ipsclass->lang['js_tt_insert_item']}</div></td>
						<td align='right'><img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt=""></td>
					</tr>
					</table>
				</div>
			</td>
			<td>
				<div class='rte-menu-button' id='{$editor_id}_popup_fontname' title='{$this->ipsclass->lang['box_font']}' style='margin-right:2px;width:148px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_fontname' style='width:130px'>&nbsp;</div></td>
						<td align='right'><img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt=""></td>
					</tr>
					</table>
				</div>
			</td>
            <td>
				<div class='rte-menu-button' id='{$editor_id}_popup_fontsize' title='{$this->ipsclass->lang['box_size']}' style='width:78px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_fontsize' style='width:60px'>&nbsp;</div></td>
						<td align='right'><img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt=""></td>
					</tr>
					</table>
				</div>
			</td>
           </tr>
          </table>
         </td>
         <td width='98%'>&nbsp;</td>
         <td width='1%' nowrap='nowrap' align='right'>
          <table cellpadding='0' cellspacing='0' width='100%'>
           <tr>
               <td><div class="rte-normal" id="{$editor_id}_cmd_spellcheck"><img src="{$images_path}spellcheck.gif" width="25" height="24" alt="{$this->ipsclass->lang['js_tt_spellcheck']}" title="{$this->ipsclass->lang['js_tt_spellcheck']}" ></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_undo" style='padding:5px'><img src="{$images_path}rte-undo.png" style="width:16px;height:16px" alt="{$this->ipsclass->lang['js_tt_undo']}" title="{$this->ipsclass->lang['js_tt_undo']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_redo" style='padding:5px'><img src="{$images_path}rte-redo.png" style="width:16px;height:16px" alt="{$this->ipsclass->lang['js_tt_redo']}" title="{$this->ipsclass->lang['js_tt_redo']}" ></div></td>
			   <td>
				   <div class="rte-normal" style='padding:0px;margin:0px;' id="{$editor_id}_cmd_resize_up"><img src="{$images_path}rte-resize-up.gif"  alt="{$this->ipsclass->lang['js_tt_smaller']}" title="{$this->ipsclass->lang['js_tt_smaller']}"></div>
				   <div class="rte-normal" style='padding:0px;margin:0px;padding-top:1px' id="{$editor_id}_cmd_resize_down"><img src="{$images_path}rte-resize-down.gif"  alt="{$this->ipsclass->lang['js_tt_larger']}" title="{$this->ipsclass->lang['js_tt_larger']}"></div>
			   </td>
			   <td><div class="rte-normal" id="{$editor_id}_cmd_switcheditor"><img src="{$images_path}rte-switch-editor.png" alt="{$this->ipsclass->lang['js_tt_switcheditor']}" title="{$this->ipsclass->lang['js_tt_switcheditor']}" ></div></td>
           </tr>
          </table>
         </td>
        </tr>
        </table>
        <table cellpadding="2" cellspacing="0" id="Buttons2" width='100%'>
        <tr>
         <td width='1%' align='left' nowrap='nowrap'>
          <table cellpadding='0' cellspacing='0' width='100%'>
           <tr>
             <td><div class="rte-normal" id="{$editor_id}_cmd_bold"><img src="{$images_path}rte-bold.png"  alt="{$this->ipsclass->lang['js_tt_bold']}" title="{$this->ipsclass->lang['js_tt_bold']}"></div></td>
             <td><div class="rte-normal" id="{$editor_id}_cmd_italic"><img src="{$images_path}rte-italic.png"  alt="{$this->ipsclass->lang['js_tt_italic']}" title="{$this->ipsclass->lang['js_tt_italic']}" ></div></td>
             <td><div class="rte-normal" id="{$editor_id}_cmd_underline"><img src="{$images_path}rte-underlined.png" alt="{$this->ipsclass->lang['js_tt_underline']}" title="{$this->ipsclass->lang['js_tt_underline']}" ></div></td>
			 <td>
             	<div class='rte-menu-button' id='{$editor_id}_popup_format' title='{$this->ipsclass->lang['js_text_formatting']}' style='margin-right:4px;margin-left:3px;width:41px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_format'>&nbsp;</div></td>
						<td nowrap="nowrap"><img class="ipd" src="{$images_path}rte-extra.png" alt="{$this->ipsclass->lang['js_extra_formatting']}" title="{$this->ipsclass->lang['js_extra_formatting']}" >
							 <img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt="">
						</td>
					</tr>
					</table>
				</div>
			 </td>
             <td>
				<div class='rte-menu-button' id='{$editor_id}_popup_forecolor' title='{$this->ipsclass->lang['js_tt_font_col']}' style='margin-right:4px;width:41px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_color'>&nbsp;</div></td>
						<td nowrap="nowrap">
							<img class='ipd' src="{$images_path}rte-textcolor.gif" alt="{$this->ipsclass->lang['js_tt_font_col']}" title="{$this->ipsclass->lang['js_tt_font_col']}" >
							<img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt="">
						</td>
					</tr>
					</table>
				</div>
			</td>
             <td>
				<div class='rte-menu-button' id='{$editor_id}_popup_backcolor' title='{$this->ipsclass->lang['js_tt_back_col']}' style='margin-right:4px;width:41px;display:none'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_bgcolor'>&nbsp;</div></td>
						<td nowrap="nowrap">
							<img class='ipd' src="{$images_path}rte-bgcolor.gif" alt="Text Color" title="{$this->ipsclass->lang['js_tt_back_col']}" >
							<img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt="">
						</td>
					</tr>
					</table>
				</div>
			</td>
			<td>
				<div class='rte-menu-button' id='{$editor_id}_popup_emoticons' title='{$this->ipsclass->lang['js_tt_emoticons']}' style='margin-right:4px;width:41px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_emoticons'>&nbsp;</div></td>
						<td nowrap="nowrap">
							<img class='ipd' src="{$images_path}rte-emoticon.gif" alt="Emoticons" title="{$this->ipsclass->lang['js_tt_back_col']}" >
							<img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt="">
						</td>
					</tr>
					</table>
				</div>
			</td>
			<td><div class="rte-normal" id="{$editor_id}_cmd_createlink"><img src="{$images_path}rte-link-button.png"  alt="{$this->ipsclass->lang['js_rte_lite_link']}" title="{$this->ipsclass->lang['js_rte_lite_link']}"></div></td>
			<td><div class="rte-normal" id="{$editor_id}_cmd_insertimage"><img src="{$images_path}rte-image-button.png"  alt="{$this->ipsclass->lang['js_rte_lite_img']}" title="{$this->ipsclass->lang['js_rte_lite_img']}"></div></td>
			<td><div class="rte-normal" id="{$editor_id}_cmd_insertemail"><img src="{$images_path}rte-email-button.png"  alt="{$this->ipsclass->lang['js_rte_lite_email']}" title="{$this->ipsclass->lang['js_rte_lite_email']}"></div></td>
           </tr>
          </table>
         </td>
         <td width='98%'>&nbsp;</td>
         <td width='1%' nowrap='nowrap' align='right'>
          <table cellpadding='0' cellspacing='0' width='100%'>
           <tr>
           	   <td><div class="rte-normal" id="{$editor_id}_cmd_outdent"><img src="{$images_path}rte-outdent.gif" alt="{$this->ipsclass->lang['js_tt_outdent']}" title="{$this->ipsclass->lang['js_tt_outdent']}" ></div></td>
           	   <td><div class="rte-normal" id="{$editor_id}_cmd_indent"><img src="{$images_path}rte-indent.gif" alt="{$this->ipsclass->lang['js_tt_outdent']}" title="{$this->ipsclass->lang['js_tt_outdent']}" ></div></td>
			   <td><img class="rteVertSep" src="{$images_path}rte_dots.gif" width="1" height="20" border="0" alt=""></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_justifyleft"><img src="{$images_path}rte-align-left.png" alt="{$this->ipsclass->lang['js_tt_left']}" title="{$this->ipsclass->lang['js_tt_left']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_justifycenter"><img src="{$images_path}rte-align-center.png" alt="{$this->ipsclass->lang['js_tt_center']}" title="{$this->ipsclass->lang['js_tt_center']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_justifyright"><img src="{$images_path}rte-align-right.png" alt="{$this->ipsclass->lang['js_tt_right']}" title="{$this->ipsclass->lang['js_tt_right']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_justifyfull"><img src="{$images_path}rte-justify.png" alt="{$this->ipsclass->lang['js_tt_jfull']}" title="{$this->ipsclass->lang['js_tt_jfull']}" ></div></td>
               <td><img class="rteVertSep" src="{$images_path}rte_dots.gif" width="1" height="20" border="0" alt=""></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_insertorderedlist"><img src="{$images_path}rte-list-numbered.gif" alt="{$this->ipsclass->lang['js_tt_list']}" title="{$this->ipsclass->lang['js_tt_list']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_insertunorderedlist"><img src="{$images_path}rte-list.gif" alt="{$this->ipsclass->lang['js_tt_list']}" title="{$this->ipsclass->lang['js_tt_list']}" ></div></td>
           </tr>
          </table>
         </td>
        </tr>
        </table>
    </div>
	<div style='padding:6px'>
    	<textarea name="{$form_field}" class="rte-iframe" id="{$editor_id}_textarea" rows="10" cols="60" style="width:98%; height:250px" tabindex="1">$initial_content</textarea>
	</div>
	<div style='float:right;padding:1px 8px 4px 8px'>
		<div class='rte-menu-button' style='width:100px;font-size:10px;padding-right:8px;'><a href="javascript:bbc_pop()" style='text-decoration:none;text-align:left;'><img src="{$images_path}rte-bbcode-help-sm.png" style='vertical-align:middle' alt='Help' border='0' /> {$this->ipsclass->lang['bbc_help']}</a></div>
	</div>
	<div align='left' style='padding:1px 8px 4px 8px; height:28px'>
		<div id='rte-toggle-side-panel-button' class='rte-menu-button' style='width:130px;font-size:10px;padding-right:8px'><a href='#' onclick="return show_options_panel('{$editor_id}')" style='text-decoration:none'><img src="{$images_path}rte-toggle-options.png" alt='Help' border='0' /> Toggle Side Panel</a></div>
	</div>
	
</div>
<br />
<script type="text/javascript">
//<![CDATA[
var MessageMax          = parseInt("{$this->ipsclass->lang['the_max_length']}");
var Override            = "{$this->ipsclass->lang['override']}";
// Update URL
ipb_var_base_url        = "{$this->ipsclass->vars['board_url']}/index.php?";
// Global paths and data
var global_rte_remove_side_panel = parseInt("{$this->ipsclass->vars['_remove_side_panel']}");
var global_rte_side_panel_open   = parseInt("{$this->ipsclass->vars['rte_side_panel_open']}");
var global_rte_images_url        = '$images_path';
var global_rte_includes_url      = '$inc_path';
var global_rte_emoticons_url     = '{$this->ipsclass->vars['EMOTICONS_URL']}';
var global_rte_char_set          = '{$this->ipsclass->vars['gb_char_set']}';
// Lang array
ips_language_array =
{
	'js_rte_link'          : "{$this->ipsclass->lang['js_rte_link']}",
	'js_rte_unlink'        : "{$this->ipsclass->lang['js_rte_unlink']}",
	'js_rte_image'         : "{$this->ipsclass->lang['js_rte_image']}",
	'js_rte_email'         : "{$this->ipsclass->lang['js_rte_email']}",
    'js_rte_erroriespell'  : "{$this->ipsclass->lang['js_rte_erroriespell']}",
	'js_rte_errorliespell' : "{$this->ipsclass->lang['js_rte_errorliespell']}",
	'js_rte_code'          : "{$this->ipsclass->lang['js_rte_code']}",
	'js_rte_quote'         : "{$this->ipsclass->lang['js_rte_quote']}"
};
// Smilies array
ips_smilie_items =
{
	$smilies
};
// BBcode items
ips_bbcode_items =
{
	$bbcode
};
// INIT item
IPS_editor['{$editor_id}'] = new ips_text_editor( '{$editor_id}', parseInt($rte_mode), parseInt($rte_mode) == 1 ? 0 : 1 );
// Set up defaults
IPS_editor['{$editor_id}'].allow_advanced        = 0;
IPS_editor['{$editor_id}'].forum_fix_ie_newlines = 1;
// Remove items from the RTE list
IPS_editor['{$editor_id}'].module_remove_item( 'table' );
IPS_editor['{$editor_id}'].module_remove_item( 'div' );
// Add items
IPS_editor['{$editor_id}'].module_add_item( 'ipbcode' , "{$this->ipsclass->lang['js_rte_code']}" , 'rte-ipb-code.png', "IPS_editor['{$editor_id}'].wrap_tags_lite(  '[code]' , '[/code]', 0)" );
IPS_editor['{$editor_id}'].module_add_item( 'ipbquote', "{$this->ipsclass->lang['js_rte_quote']}", 'rte-ipb-quote.png', "IPS_editor['{$editor_id}'].wrap_tags_lite( '[quote]', '[/quote]', 0)" );
// Check side panel
check_side_panel( '{$editor_id}' );

//]]>
</script>
EOF;
//--endhtml--//
return $IPBHTML;
}
//===========================================================================
// RTE: INIT
//===========================================================================
/*function ips_editor( $form_field, $initial_content, $images_path="", $inc_path="", $rte_mode=0, $editor_id='ed-0', $smilies='' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<!--top-->
EOF;
//startif
if ( $editor_id == 'ed-0' )
{
$IPBHTML .= <<<EOF
<script type="text/javascript" src="jscripts/ips_text_editor.js"></script>
<style type='text/css'>
@import url( "{$images_path}css_rte.css" );
</style>
EOF;
}//endif
$IPBHTML .= <<<EOF
<input type='hidden' name='{$editor_id}_wysiwyg_used' id='{$editor_id}_wysiwyg_used' value='{$rte_mode}' />
<input type='hidden' name='editor_ids[]' value='{$editor_id}' />
<div id='{$editor_id}_main-bar' class="rte-buttonbar">
    <div id='{$editor_id}_controls' style='display:none;padding:6px;text-align:left'>
        <table cellpadding="2" cellspacing="0" width='100%'>
        <tr>
        <td width='1%' align='left' nowrap='nowrap'>
          <table cellpadding='0' cellspacing='0' width='100%'>
           <tr>
            <td><div class='rte-normal' id="{$editor_id}_cmd_removeformat"><img src="{$images_path}rte-remove-formatting.gif" alt="{$this->ipsclass->lang['js_tt_noformat']}" title="{$this->ipsclass->lang['js_tt_noformat']}" ></div></td>
            <td><div class='rte-normal' id="{$editor_id}_cmd_togglesource"><img src="{$images_path}rte-toggle-html.gif" alt="{$this->ipsclass->lang['js_tt_htmlsource']}" title="{$this->ipsclass->lang['js_tt_htmlsource']}" ></div></td>
			<td><img class="rteVertSep" src="{$images_path}" width="1" height="20" border="0" alt=""></td> 
			<td>
				<div class='rte-menu-button' id='{$editor_id}_popup_special' title='{$this->ipsclass->lang['js_tt_insert_item']}' style='margin-right:2px;width:148px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_special' style='width:130px'>{$this->ipsclass->lang['js_tt_insert_item']}</div></td>
						<td align='right'><img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt=""></td>
					</tr>
					</table>
				</div>
			</td>
			<td>
				<div class='rte-menu-button' id='{$editor_id}_popup_fontname' title='{$this->ipsclass->lang['box_font']}' style='margin-right:2px;width:148px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_fontname' style='width:130px'>&nbsp;</div></td>
						<td align='right'><img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt=""></td>
					</tr>
					</table>
				</div>
			</td>
            <td>
				<div class='rte-menu-button' id='{$editor_id}_popup_fontsize' title='{$this->ipsclass->lang['box_size']}' style='width:78px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_fontsize' style='width:60px'>&nbsp;</div></td>
						<td align='right'><img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt=""></td>
					</tr>
					</table>
				</div>
			</td>
           </tr>
          </table>
         </td>
         <td width='98%'>&nbsp;</td>
         <td width='1%' nowrap='nowrap' align='right'>
          <table cellpadding='0' cellspacing='0' width='100%'>
           <tr>
               <td><div class="rte-normal" id="{$editor_id}_cmd_spellcheck"><img src="{$images_path}spellcheck.gif" width="25" height="24" alt="{$this->ipsclass->lang['js_tt_spellcheck']}" title="{$this->ipsclass->lang['js_tt_spellcheck']}" ></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_undo" style='padding:5px'><img src="{$images_path}rte-undo.png" style="width:16px;height:16px" alt="{$this->ipsclass->lang['js_tt_undo']}" title="{$this->ipsclass->lang['js_tt_undo']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_redo" style='padding:5px'><img src="{$images_path}rte-redo.png" style="width:16px;height:16px" alt="{$this->ipsclass->lang['js_tt_redo']}" title="{$this->ipsclass->lang['js_tt_redo']}" ></div></td>
			   <td>
				   <div class="rte-normal" style='padding:0px;margin:0px;' id="{$editor_id}_cmd_resize_up"><img src="{$images_path}rte-resize-up.gif"  alt="{$this->ipsclass->lang['js_tt_smaller']}" title="{$this->ipsclass->lang['js_tt_smaller']}"></div>
				   <div class="rte-normal" style='padding:0px;margin:0px;padding-top:1px' id="{$editor_id}_cmd_resize_down"><img src="{$images_path}rte-resize-down.gif"  alt="{$this->ipsclass->lang['js_tt_larger']}" title="{$this->ipsclass->lang['js_tt_larger']}"></div>
			   </td>
			   <td><div class="rte-normal" id="{$editor_id}_cmd_switcheditor"><img src="{$images_path}rte-switch-editor.png" alt="{$this->ipsclass->lang['js_tt_switcheditor']}" title="{$this->ipsclass->lang['js_tt_switcheditor']}" ></div></td>
           </tr>
          </table>
         </td>
        </tr>
        </table>
        <table cellpadding="2" cellspacing="0" id="Buttons2" width='100%'>
        <tr>
         <td width='1%' align='left' nowrap='nowrap'>
          <table cellpadding='0' cellspacing='0' width='100%'>
           <tr>
             <td><div class="rte-normal" id="{$editor_id}_cmd_bold"><img src="{$images_path}rte-bold.png"  alt="{$this->ipsclass->lang['js_tt_bold']}" title="{$this->ipsclass->lang['js_tt_bold']}"></div></td>
             <td><div class="rte-normal" id="{$editor_id}_cmd_italic"><img src="{$images_path}rte-italic.png"  alt="{$this->ipsclass->lang['js_tt_italic']}" title="{$this->ipsclass->lang['js_tt_italic']}" ></div></td>
             <td><div class="rte-normal" id="{$editor_id}_cmd_underline"><img src="{$images_path}rte-underlined.png" alt="{$this->ipsclass->lang['js_tt_underline']}" title="{$this->ipsclass->lang['js_tt_underline']}" ></div></td>
			 <td>
             	<div class='rte-menu-button' id='{$editor_id}_popup_format' title='{$this->ipsclass->lang['js_text_formatting']}' style='margin-right:4px;margin-left:3px;width:41px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_format'>&nbsp;</div></td>
						<td nowrap="nowrap"><img class="ipd" src="{$images_path}rte-extra.png" alt="{$this->ipsclass->lang['js_extra_formatting']}" title="{$this->ipsclass->lang['js_extra_formatting']}" >
							 <img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt="">
						</td>
					</tr>
					</table>
				</div>
			 </td>
             <td>
				<div class='rte-menu-button' id='{$editor_id}_popup_forecolor' title='{$this->ipsclass->lang['js_tt_font_col']}' style='margin-right:4px;width:41px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_color'>&nbsp;</div></td>
						<td nowrap="nowrap">
							<img class='ipd' src="{$images_path}rte-textcolor.gif" alt="{$this->ipsclass->lang['js_tt_font_col']}" title="{$this->ipsclass->lang['js_tt_font_col']}" >
							<img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt="">
						</td>
					</tr>
					</table>
				</div>
			</td>
             <td>
				<div class='rte-menu-button' id='{$editor_id}_popup_backcolor' title='{$this->ipsclass->lang['js_tt_back_col']}' style='margin-right:4px;width:41px;display:none'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_bgcolor'>&nbsp;</div></td>
						<td nowrap="nowrap">
							<img class='ipd' src="{$images_path}rte-bgcolor.gif" alt="Text Color" title="{$this->ipsclass->lang['js_tt_back_col']}" >
							<img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt="">
						</td>
					</tr>
					</table>
				</div>
			</td>
			<td>
				<div class='rte-menu-button' id='{$editor_id}_popup_emoticons' title='{$this->ipsclass->lang['js_tt_back_col']}' style='margin-right:4px;width:41px'>
					<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td><div id='{$editor_id}_out_emoticons'>&nbsp;</div></td>
						<td nowrap="nowrap">
							<img class='ipd' src="{$images_path}rte-emoticon.gif" alt="Emoticons" title="{$this->ipsclass->lang['js_tt_back_col']}" >
							<img class="ipd" src="{$images_path}icon_open.gif"  border="0" alt="">
						</td>
					</tr>
					</table>
				</div>
			</td>
           </tr>
          </table>
         </td>
         <td width='98%'>&nbsp;</td>
         <td width='1%' nowrap='nowrap' align='right'>
          <table cellpadding='0' cellspacing='0' width='100%'>
           <tr>
           	   <td><div class="rte-normal" id="{$editor_id}_cmd_outdent"><img src="{$images_path}rte-outdent.gif" alt="{$this->ipsclass->lang['js_tt_outdent']}" title="{$this->ipsclass->lang['js_tt_outdent']}" ></div></td>
           	   <td><div class="rte-normal" id="{$editor_id}_cmd_indent"><img src="{$images_path}rte-indent.gif" alt="{$this->ipsclass->lang['js_tt_outdent']}" title="{$this->ipsclass->lang['js_tt_outdent']}" ></div></td>
			   <td><img class="rteVertSep" src="{$images_path}rte_dots.gif" width="1" height="20" border="0" alt=""></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_justifyleft"><img src="{$images_path}rte-align-left.png" alt="{$this->ipsclass->lang['js_tt_left']}" title="{$this->ipsclass->lang['js_tt_left']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_justifycenter"><img src="{$images_path}rte-align-center.png" alt="{$this->ipsclass->lang['js_tt_center']}" title="{$this->ipsclass->lang['js_tt_center']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_justifyright"><img src="{$images_path}rte-align-right.png" alt="{$this->ipsclass->lang['js_tt_right']}" title="{$this->ipsclass->lang['js_tt_right']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_justifyfull"><img src="{$images_path}rte-justify.png" alt="{$this->ipsclass->lang['js_tt_jfull']}" title="{$this->ipsclass->lang['js_tt_jfull']}" ></div></td>
               <td><img class="rteVertSep" src="{$images_path}rte_dots.gif" width="1" height="20" border="0" alt=""></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_insertorderedlist"><img src="{$images_path}rte-list-numbered.gif" alt="{$this->ipsclass->lang['js_tt_list']}" title="{$this->ipsclass->lang['js_tt_list']}" ></div></td>
               <td><div class="rte-normal" id="{$editor_id}_cmd_insertunorderedlist"><img src="{$images_path}rte-list.gif" alt="{$this->ipsclass->lang['js_tt_list']}" title="{$this->ipsclass->lang['js_tt_list']}" ></div></td>
           </tr>
          </table>
         </td>
        </tr>
        </table>
    </div>
	<div style='padding:6px'>
    	<textarea name="{$form_field}" class="rte-iframe" id="{$editor_id}_textarea" rows="10" cols="60" style="width:98%; height:250px" tabindex="1">$initial_content</textarea>
	</div>
	<div align='right' style='padding:1px 8px 4px 8px'>
		<div class='rte-menu-button' style='width:100px;font-size:10px;padding-right:8px'><a href="javascript:bbc_pop()" style='text-decoration:none'><img src="{$images_path}rte-bbcode-help-sm.png" alt='Help' border='0' /> {$this->ipsclass->lang['bbc_help']}</a></div>
	</div>
</div>
<br />
<script type="text/javascript">
//<![CDATA[
var MessageMax          = parseInt("{$this->ipsclass->lang['the_max_length']}");
var Override            = "{$this->ipsclass->lang['override']}";
// Global paths and data
var global_rte_images_url    = '$images_path';
var global_rte_includes_url  = '$inc_path';
var global_rte_emoticons_url = '{$this->ipsclass->vars['EMOTICONS_URL']}';
var global_rte_char_set      = '{$this->ipsclass->vars['gb_char_set']}';
// Lang array
ips_language_array =
{
	'js_rte_link'          : "{$this->ipsclass->lang['js_rte_link']}",
	'js_rte_unlink'        : "{$this->ipsclass->lang['js_rte_unlink']}",
	'js_rte_image'         : "{$this->ipsclass->lang['js_rte_image']}",
	'js_rte_email'         : "{$this->ipsclass->lang['js_rte_email']}",
    'js_rte_erroriespell'  : "{$this->ipsclass->lang['js_rte_erroriespell']}",
	'js_rte_errorliespell' : "{$this->ipsclass->lang['js_rte_errorliespell']}",
	'js_rte_code'          : "{$this->ipsclass->lang['js_rte_code']}",
	'js_rte_quote'         : "{$this->ipsclass->lang['js_rte_quote']}"
};
// Smilies array
ips_smilie_items =
{
	$smilies
};
// INIT item
IPS_editor['{$editor_id}'] = new ips_text_editor( '{$editor_id}', parseInt($rte_mode), parseInt($rte_mode) == 1 ? 0 : 1 );
// Set up defaults
IPS_editor['{$editor_id}'].allow_advanced        = 0;
IPS_editor['{$editor_id}'].forum_fix_ie_newlines = 1;
// Remove items from the RTE list
IPS_editor['{$editor_id}'].module_remove_item( 'table' );
IPS_editor['{$editor_id}'].module_remove_item( 'div' );
// Add items
IPS_editor['{$editor_id}'].module_add_item( 'ipbcode' , "{$this->ipsclass->lang['js_rte_code']}" , 'rte-ipb-code.png', "IPS_editor['{$editor_id}'].wrap_tags_lite(  '[code]' , '[/code]', 0)" );
IPS_editor['{$editor_id}'].module_add_item( 'ipbquote', "{$this->ipsclass->lang['js_rte_quote']}", 'rte-ipb-quote.png', "IPS_editor['{$editor_id}'].wrap_tags_lite( '[quote]', '[/quote]', 0)" );
//]]>
</script>
EOF;
//startif
if ( $editor_id == 'ed-0' )
{
$IPBHTML .= <<<EOF
<script type="text/javascript" src="jscripts/ips_text_editor_func.js"></script>
EOF;
}

//--endhtml--//
return $IPBHTML;
}*/



}

?>