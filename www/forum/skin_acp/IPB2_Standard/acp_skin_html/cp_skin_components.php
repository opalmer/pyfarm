<?php

class cp_skin_components {

var $ipsclass;

//===========================================================================
// Member: validating
//===========================================================================
function welcome_page() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tablesubheader'>Invision Power Board Information</div>
 <div class='tablerow1'>
 This section is reserved for any components, such as Invision Gallery, Invision Chat and Invision Blog
 </div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}



}

?>