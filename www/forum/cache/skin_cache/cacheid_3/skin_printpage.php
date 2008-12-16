<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD           */
/* CACHE FILE: Skin set id: 3                     */
/* CACHE FILE: Generated: Wed, 12 Nov 2008 04:54:08 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_printpage_3 {

 var $ipsclass;
//===========================================================================
// <ips:choose_form:desc::trigger:>
//===========================================================================
function choose_form($fid="",$tid="",$title="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<div class=\"borderwrap\">
	<div class=\"maintitle\"><{CAT_IMG}>&nbsp;{$this->ipsclass->lang['tvo_title']}&nbsp;$title</div>
	<table class='ipbtable' cellspacing=\"1\">
		<tr>
			<th>{$this->ipsclass->lang['please_choose']}</th>
		</tr>
		<tr>
			<td class=\"row1\"><b><a href=\"{$this->ipsclass->base_url}act=Print&amp;client=printer&amp;f=$fid&amp;t=$tid\">{$this->ipsclass->lang['o_print_title']}</a></b><br />{$this->ipsclass->lang['o_print_desc']}</td>
		</tr>
		<tr>
			<td class=\"row2\"><b><a href=\"{$this->ipsclass->base_url}act=Print&amp;client=html&amp;f=$fid&amp;t=$tid\">{$this->ipsclass->lang['o_html_title']}</a></b><br />{$this->ipsclass->lang['o_html_desc']}</td>
		</tr>
		<tr>
			<td class=\"row1\"><b><a href=\"{$this->ipsclass->base_url}act=Print&amp;client=wordr&amp;f=$fid&amp;t=$tid\">{$this->ipsclass->lang['o_word_title']}</a></b><br />{$this->ipsclass->lang['o_word_desc']}</td>
		</tr>
		<tr>
			<td class=\"formbuttonrow\"><a href=\"{$this->ipsclass->base_url}showtopic=$tid\">{$this->ipsclass->lang['back_topic']}</a></td>
		</tr>
		<tr>
			<td class=\"catend\"><!-- no content --></td>
		</tr>
	</table>
</div>";
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// <ips:pp_end:desc::trigger:>
//===========================================================================
function pp_end() {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<!--Copyright-->
" . (($this->ipsclass->vars['ipb_copy_number'] == '') ? ("
<p class=\"printcopy\">{$this->ipsclass->lang['powered_by']}Invision Power Board (http://www.invisionboard.com)<br />&copy; Invision Power Services (http://www.invisionpower.com)</p>
") : ("")) . "
	</div>
</body>
</html>";
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// <ips:pp_header:desc::trigger:>
//===========================================================================
function pp_header($forum_name="",$topic_title="",$topic_starter="",$fid="",$tid="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xml:lang=\"en\" lang=\"en\" xmlns=\"http://www.w3.org/1999/xhtml\">
	<head>
		<meta http-equiv=\"content-type\" content=\"text/html; charset={$this->ipsclass->vars['gb_char_set']}\" />
		<title>{$this->ipsclass->vars['board_name']} [{$this->ipsclass->lang['powered_by']}Invision Power Board]</title>
		<!--IPB.CSS-->
	</head>
	<body>
	<div id=\"print\">
		<h1>{$this->ipsclass->lang['title']}</h1>
		<h2><a href=\"{$this->ipsclass->base_url}showtopic=$tid\" title=\"{$this->ipsclass->lang['click_toview']}\">{$this->ipsclass->lang['topic_here']}</a></h2>
		<h3>{$this->ipsclass->vars['board_name']} _ $forum_name _ $topic_title</h3>";
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// <ips:pp_postentry:desc::trigger:>
//===========================================================================
function pp_postentry($poster="",$entry="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<div class=\"printpost\">
			<h4>{$this->ipsclass->lang['by']}: <b>{$entry['author_name']}</b> {$this->ipsclass->lang['on']} {$entry['post_date']}</h4>
			<p>{$entry['post']}
			<br />
			<!--IBF.ATTACHMENT_{$entry['pid']}-->
			</p>
		</div>";
//--endhtml--//
return $IPBHTML;
}



}

/*--------------------------------------------------*/
/*<changed bits>

</changed bits>*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>