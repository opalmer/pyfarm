<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD           */
/* CACHE FILE: Skin set id: 3                     */
/* CACHE FILE: Generated: Wed, 12 Nov 2008 04:54:08 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_blog_xml_3 {

 var $ipsclass;
//===========================================================================
// <ips:blog_tabs_xml:desc::trigger:>
//===========================================================================
function blog_tabs_xml($tabs="",$blog="",$error="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<div class='mya-back'>
<div class='pp-tabwrap' style='margin-left:1px'>
	<div class='{$tabs['main']}'><a href='javascript:xml_myblogsettings_init(\"main\")'>{$this->ipsclass->lang['blogset_main']}</a></div>
	<div class='{$tabs['settings']}'><a href='javascript:xml_myblogsettings_init(\"settings\")'>{$this->ipsclass->lang['blogset_settings']}</a></div>
	<div class='{$tabs['tbpings']}'><a href='javascript:xml_myblogsettings_init(\"tbpings\")'>{$this->ipsclass->lang['blogset_tbpings']}</a></div>
	<div class='{$tabs['look']}'><a href='javascript:xml_myblogsettings_init(\"look\")'>{$this->ipsclass->lang['blogset_look']}</a></div>
	<div class='{$tabs['categories']}'><a href='javascript:xml_myblogsettings_init(\"categories\")'>{$this->ipsclass->lang['blogset_categories']}</a></div>
" . (($this->ipsclass->member['id'] == $blog['member_id'] and $this->ipsclass->member['g_blog_allowprivclub']==1) ? ("
	<div class='{$tabs['privateclub']}'><a href='javascript:xml_myblogsettings_init(\"privateclub\")'>{$this->ipsclass->lang['blogset_privateclub']}</a></div>
") : ("")) . "
" . (($this->ipsclass->member['id'] == $blog['member_id'] and $this->ipsclass->member['g_blog_alloweditors']==1) ? ("
	<div class='{$tabs['editors']}'><a href='javascript:xml_myblogsettings_init(\"editors\")'>{$this->ipsclass->lang['blogset_editors']}</a></div>
") : ("")) . "
</div>
<div class='pp-contentbox-back'>
	<div class='pp-contentbox-entry-noheight'>
		<div id='myblogset-info'>{$error}</div>
<!--IBF.BLOGTABCONTENT-->
	</div>
</div>";
//--endhtml--//
return $IPBHTML;
}



}

/*--------------------------------------------------*/
/*<changed bits>
blog_tabs_xml
</changed bits>*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>