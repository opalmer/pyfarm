<?php

class cp_skin_diagnostics {

var $ipsclass;

function dbchecker_javascript()
{
$IPBHTML = "";
$IPBHTML = <<<EOF

<script type='text/javascript'>
var all_queries = new Array();

function fix_all_dberrors()
{
	var url = ipb_var_base_url + '&section=help&act=diag&code=dbchecker';
	
	for( var i=0; i<all_queries.length; i++ )
	{
		url += '&query'+i+'='+all_queries[i];
	}
	
	window.location = url;
	return false;
}
</script>
EOF;

return $IPBHTML;
}


function dbindexer_javascript()
{
$IPBHTML = "";
$IPBHTML = <<<EOF

<script type='text/javascript'>
var all_queries = new Array();

function fix_all_dberrors()
{
	var url = ipb_var_base_url + '&section=help&act=diag&code=dbindex';
	
	for( var i=0; i<all_queries.length; i++ )
	{
		url += '&query'+i+'='+all_queries[i];
	}
	
	window.location = url;
	return false;
}
</script>
EOF;

return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_version_history_row( $r ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>{$r['upgrade_version_human']} ({$r['upgrade_version_id']})</td>
 <td class='tablerow2'>{$r['_date']}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Index
//===========================================================================
function acp_version_history_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<a name='versions'></a>
<div class='tableborder'>
 <div class='tableheaderalt'>Version History (Last 5)</div>
 <table width='100%' cellpadding='4' cellspacing='0'>
 $content
 </table>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

}


?>