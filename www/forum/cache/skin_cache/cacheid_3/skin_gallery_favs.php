<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD           */
/* CACHE FILE: Skin set id: 3                     */
/* CACHE FILE: Generated: Wed, 12 Nov 2008 04:54:08 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_gallery_favs_3 {

 var $ipsclass;
//===========================================================================
// <ips:fav_view_top:desc::trigger:>
//===========================================================================
function fav_view_top($info="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<div class=\"subtitle\">{$this->ipsclass->lang['your_favs']}</div>
	<table class=\"ipbtable\">
		<tr>
			<td style='padding-left:0px;' width=\"20%\" nowrap=\"nowrap\">{$info['SHOW_PAGES']}</td>
			<td style='padding-right:0px;' align=\"right\" width=\"80%\" nowrap=\"nowrap\">{$info['download_favs']}</td>
		</tr>
	</table>
	<table class=\"ipbtable\" cellspacing='0'>";
//--endhtml--//
return $IPBHTML;
}



}

/*--------------------------------------------------*/
/*<changed bits>
fav_view_top
</changed bits>*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>