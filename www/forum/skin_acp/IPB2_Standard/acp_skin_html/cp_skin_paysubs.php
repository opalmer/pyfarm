<?php

class cp_skin_paysubs {

var $ipsclass;


//===========================================================================
// Gateways
//===========================================================================
function gateway_install_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Install a New Payment Gateway</div>
 <table cellpadding='4' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='90%'>Gateway Name</td>
  <td class='tablesubheader' width='10%' align='center'>&nbsp;</td>
 </tr>
 {$content}
 </table>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Gateway
//===========================================================================
function gateway_install_row( $gateway, $installed ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
  <td class='tablerow2'><strong>$gateway</strong></td>
EOF;
if ( $installed == 1 )
{
$IPBHTML .= <<<EOF
<td class='tablerow2' align='center'><em>Installed</em></td>
EOF;
}
else
{
$IPBHTML .= <<<EOF
<td class='tablerow2' align='center'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=install-gateway&name=$gateway'>Install</a></td>
EOF;
}
$IPBHTML .= <<<EOF
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Gateways
//===========================================================================
function gateways_menu_item($row) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
img_edit   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=edit_package_gateway_info&method=--methodid--&sub={$row['sub_id']}'>Edit Gateways Methods For: {$row['sub_title']}...</a>",
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Gateways
//===========================================================================
function tools_wrapper($form) {

$IPBHTML = "";
//--starthtml--//
		
$IPBHTML .= <<<EOF
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=find_transactions' method="POST">
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>Find / Edit Transactions</div>
 <table cellpadding='4' cellspacing='0' width='100%'>
 <tr>
  <td class='tablerow2' width='40%'><strong>Payment Status</strong></td>
  <td class='tablerow1' width='60%'>{$form['status']}</td>
 </tr>
 <tr>
  <td class='tablerow2'><strong>Subscription Package</strong></td>
  <td class='tablerow1'>{$form['package']}</td>
 </tr>
 <tr>
  <td class='tablerow2'><strong>Optional Query</strong></td>
  <td class='tablerow1'>{$form['searchtype']} contains... {$form['search']}</td>
 </tr>
 <tr>
  <td class='tablerow2'><strong>Subscription expires within <em>n</em> days</strong><div class='desctext'>Optional</div></td>
  <td class='tablerow1'>{$form['expiredays']}</td>
 </tr>
 </table>
 <div class='tablesubheader' align='center'><input type='submit' class='realbutton' value=' Go &gt;&gt; ' /></div>
</div>
</form>
<br />
<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=find_logs' method="POST">
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>Search Transaction Logs</div>
 <table cellpadding='4' cellspacing='0' width='100%'>
 <tr>
  <td class='tablerow2' width='40%'><strong>Search</strong></td>
  <td class='tablerow1' width='60%'>{$form['searchtype2']} contains... {$form['search2']}</td>
 </tr>
 </table>
 <div class='tablesubheader' align='center'><input type='submit' class='realbutton' value=' Go &gt;&gt; ' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Member: validating
//===========================================================================
function tools_trans_row( $row="" ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
  <td class='tablerow2'><strong>{$row['sub_title']}</strong><div class='desctext'>{$row['sub_desc']}</div></td>
  <td class='tablerow2' align='center'>{$row['_cost']}</td>
  <td class='tablerow2' align='center'>{$row['_duration']}</td>
  <td class='tablerow2' align='center'><span style='color:green'>{$row['_active']}</span></td>
  <td class='tablerow2' align='center'><span style='color:red'>{$row['_expired']}</span></td>															
  <td class='tablerow1' align='center'><img id="menu{$row['sub_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$row['sub_id']}",
  new Array( img_edit   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=editpackage&id={$row['sub_id']}'>Edit Package...</a>",
  			 img_delete   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=removepackage&id={$row['sub_id']}'>Remove Package...</a>",
  			 img_item   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=removemembers&type=all&id={$row['sub_id']}'>Unsubscribe All Members...</a>",
  			 img_item   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=removemembers&type=expired&id={$row['sub_id']}'>Unsubscribe Expired Members...</a>",
  			 img_view   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=dosearch&package={$row['sub_id']}'>Show Subscribed Members...</a>",
  			 img_view   + " <a href='#' onclick='pop_win(\"&{$this->ipsclass->form_code}&code=overview&package={$row['sub_id']}\", \"Overview\", 600,200)'>Launch Overview...</a>"
 		    ) );
 </script>
 
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Gateways
//===========================================================================
function packages_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Subscription Packages</div>
 <table cellpadding='4' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='28%'>Subscription Plan</td>
  <td class='tablesubheader' width='10%' align='center'>Cost</td>
  <td class='tablesubheader' width='10%' align='center'>Duration</td>
  <td class='tablesubheader' width='10%' align='center'>Active Members</td>
  <td class='tablesubheader' width='12%' align='center'>Expired Members</td>
  <td class='tablesubheader' width='1%' align='center'>&nbsp;</td>
 </tr>
 {$content}
 </table>
 <div class='tablefooter' align='center'><div class='fauxbutton-wrapper'><span class='fauxbutton'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=addpackage'>Add New Subscription Package</a></span></div></div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Member: validating
//===========================================================================
function packages_row( $row="" ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
  <td class='tablerow2'><strong>{$row['sub_title']}</strong><div class='desctext'>{$row['sub_desc']}</div></td>
  <td class='tablerow2' align='center'>{$row['_cost']}</td>
  <td class='tablerow2' align='center'>{$row['_duration']}</td>
  <td class='tablerow2' align='center'><span style='color:green'>{$row['_active']}</span></td>
  <td class='tablerow2' align='center'><span style='color:red'>{$row['_expired']}</span></td>															
  <td class='tablerow1' align='center'><img id="menu{$row['sub_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$row['sub_id']}",
  new Array( img_edit   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=editpackage&id={$row['sub_id']}'>Edit Package...</a>",
  			 img_delete   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=removepackage&id={$row['sub_id']}'>Remove Package...</a>",
  			 img_item   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=removemembers&type=all&id={$row['sub_id']}'>Unsubscribe All Members...</a>",
  			 img_item   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=removemembers&type=expired&id={$row['sub_id']}'>Unsubscribe Expired Members...</a>",
  			 img_view   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=find_transactions&package={$row['sub_id']}'>Show Subscribed Members...</a>"
  			 //img_item   + " <a href='#' onclick='pop_win(\"&{$this->ipsclass->form_code}&code=overview&package={$row['sub_id']}\", \"Overview\", 600,200)'>Launch Overview...</a>"
 		    ) );
 </script>
 
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Gateways
//===========================================================================
function gateways_wrapper($content, $totals) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Available Payment Gateways</div>
 <table cellpadding='4' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='20%'>Payment Gateway</td>
  <td class='tablesubheader' width='10%'>Active</td>
  <td class='tablesubheader' width='10%'>Transactions</td>
  <td class='tablesubheader' width='10%'>Current</td>
  <td class='tablesubheader' width='10%'>Pending</td>
  <td class='tablesubheader' width='10%'>Failed</td>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
 </tr>
 {$content}
 <tr>
  <td class='tablerow1' colspan='3' align='right'><strong>Cumulative Revenue: ({$totals['_culm']})</strong></td>
  <td class='tablerow1' align='center'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=find_transactions&status=paid'>{$totals['_paid']}</a></td>
  <td class='tablerow1' align='center'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=find_transactions&status=pending'>{$totals['_pending']}</a></td>
  <td class='tablerow1' align='center'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=find_transactions&status=failed'>{$totals['_failed']}</a></td>
  <td class='tablerow1'>&nbsp;</td>
 </tr>
 
 </table>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Member: validating
//===========================================================================
function gateways_row( $row="", $menu="" ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
  <td class='tablerow2'><strong>{$row['submethod_title']}</strong></td>
  <td class='tablerow2' align='center'>{$row['_active']}</td>
  <td class='tablerow2' align='center'>&nbsp;{$row['_total']}&nbsp;</td>
  <td class='tablerow2' align='center'><span style='color:green'>{$row['_trans']}</span></td>
  <td class='tablerow2' align='center'><span style='color:orange'>{$row['_pending']}</span></td>
  <td class='tablerow2' align='center'><span style='color:red'>{$row['_dead']}</span></td>															
  <td class='tablerow1' align='center'><img id="menu{$row['submethod_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$row['submethod_id']}",
  new Array( $menu
  		     img_edit   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=editmethod&id={$row['submethod_id']}'>Set Up Payment Gateway...</a>"
 		    ) );
 </script>
 
EOF;

//--endhtml--//
return $IPBHTML;
}


}

?>