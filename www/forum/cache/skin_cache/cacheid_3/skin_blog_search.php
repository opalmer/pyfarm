<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD           */
/* CACHE FILE: Skin set id: 3                     */
/* CACHE FILE: Generated: Wed, 12 Nov 2008 04:54:08 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_blog_search_3 {

 var $ipsclass;
//===========================================================================
// <ips:blog_show_entry:desc::trigger:>
//===========================================================================
function blog_show_entry($entry="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<div class=\"borderwrap\">
	<div class='maintitle'>
		<p class=\"expand\"><a href=\"{$entry['url']}\">{$entry['blog_name']}</a></p>
		<p><<a href=\"{$entry['url']}showentry={$entry['entry_id']}\"><b>{$entry['entry_name']}</b></a></p>
	</div>
	<div class='post2' style='padding:4px 4px 4px 4px;height:200px;overflow:auto'><div class='postcolor'>
" . (($entry['hide_private']) ? ("<span style=\"color:#992A2A;\">{$this->ipsclass->lang['blog_private_entry']}<br /><br />
<a href=\"{$entry['url']}&amp;showprivate=1\">{$this->ipsclass->lang['blog_show_privateentry']}</a></span>
") : ("
{$entry['entry']} <!--IBF.ATTACHMENT_{$entry['entry_id']}-->
")) . "
</div></div>
	<table cellspacing=\"0\" class=\"ipbtable\">
		<tr>
			<td class=\"formbuttonrow\" nowrap=\"nowrap\">
				<div style='text-align:left'>
					<span style=\"float:right;\">
						<a href=\"{$entry['url']}showentry={$entry['entry_id']}\">{$this->ipsclass->lang['entry_comments']} {$entry['entry_num_comments']}</a>
					</span>
					{$this->ipsclass->lang['entry_posted_on']} {$entry['entry_date']}
				</div>
			</td>
		</tr>
	</table>
	<div class=\"catend\"><!-- no content --></div>
</div>
<br />";
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// <ips:search_error_page:desc::trigger:>
//===========================================================================
function search_error_page($keywords="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<div class=\"borderwrap\">
	<div class=\"maintitle\">{$this->ipsclass->lang['search_results']}</div>
	<table cellspacing=\"0\" class=\"ipbtable\">
		<tr>
			<th><b>{$this->ipsclass->lang['you_search_for']}</b> {$keywords}</th>
		</tr>
		<tr>
			<td class=\"row2\">{$this->ipsclass->lang['no_results_so_there_ha_ha']}<br /><br /><b><a href=\"{$this->ipsclass->base_url}automodule=blog\">{$this->ipsclass->lang['return_to_blog']}</a></b></td>
		</tr>
		<tr>
			<td class=\"catend\"><!-- no content --></td>
		</tr>
	</table>
</div>";
//--endhtml--//
return $IPBHTML;
}



}

/*--------------------------------------------------*/
/*<changed bits>
blog_show_entry,search_error_page
</changed bits>*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>