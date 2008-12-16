<?php
/**
 * Invision Power Board
 * Template Controller for installer framework
 */

class install_template
{
	var $page_title   = '';
	var $page_content = '';
	var $page_current = '';
	var $message	  = '';
	var $hide_next    = 0;	
	var $in_error	  = 0;

	var $install_pages = array();
	
	var $ipsclass;
	
	/**
	 * install_template::install_template
	 * 
	 * CONSTRUCTOR
	 *
	 */	
	function install_template( &$ipsclass )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_pages         =  array();
		$this->ipsclass =& $ipsclass;
		
		//-----------------------------------------
		// Grab XML file and check
		//-----------------------------------------
		
		if ( file_exists( INS_ROOT_PATH . 'installfiles/sequence.xml' ) )
		{
			$config = implode( '', file( INS_ROOT_PATH . 'installfiles/sequence.xml' ) );
			$xml = new class_xml();
	
			$config = $xml->xml_parse_document( $config );
			
			//-----------------------------------------
			// Loop through and sort out settings...
			//-----------------------------------------

			foreach( $xml->xml_array['installdata']['action'] as $id => $entry )
			{
				$_pages[ $entry['position']['VALUE'] ] = array( 'file' => $entry['file']['VALUE'],
															    'menu' => $entry['menu']['VALUE'] );
			}
			
			ksort( $_pages );
			
			foreach( $_pages as $position => $data )
			{
				$this->install_pages[ $data['file'] ] = $data['menu'];
			}
		}
		
		$this->install_pages['done'] = 'Finish';
	   
		/* Set Current Page */
		$this->page_current = ( $this->ipsclass->input['p'] ) ? $this->ipsclass->input['p'] : 'login';
		
		if( ! $this->install_pages[$this->page_current] )
		{
			$this->page_current = 'login';	
		}
	}
	
	/**
	 * install_template::set_title
	 * 
	 * Sets the title for the current page
	 *
	 * @var string $title
	 */
	function set_title( $title )
	{
		$this->page_title = $title;	
	}

	/**
	 * install_template::append
	 * 
	 * Adds to the main body output
	 *
	 * @var string $add
	 */
	
	function append( $add )
	{
		$this->page_content .= $add;	
	}
	

	/**
	 * install_template::output
	 * 
	 * Builds page and sends to browser
	 *
	 */	
	function output()
	{
		/* Build Side Bar */
		$curr_reached   = 0;
		$this->progress = array();
		
		foreach( $this->install_pages as $key => $page )
		{
			if( $key == $this->page_current )
			{
				$this->progress[] = array( 'step_doing', $page );
				$curr_reached = 1;
			}
			else if( $curr_reached )
			{
				$this->progress[] = array( 'step_notdone', $page );
			}
			else 
			{
				$this->progress[] = array( 'step_done', $page );
			}
			
		}
		
		$this->page_template();
	}
	
	/***************************************************************
	 *
	 * HTML TEMPLATE FUNCTIONS
	 *
	 **************************************************************/
	
	// ------------------------------------------------------------
	// Main Template
	// ------------------------------------------------------------
	function page_template()
	{
echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>IPS Upgrader</title>
		<style type='text/css' media='all'>
			@import url('install.css');
		</style>
		<script type='text/javascript'>
			//<![CDATA[
		  		if (top.location != self.location) { top.location = self.location }
				var use_enhanced_js = 1;
			//]]>
		</script>
		<script type="text/javascript" src='ips_xmlhttprequest.js'></script>	
	</head>
	<body>
		<p>&nbsp;</p>
		<p>&nbsp;</p>
		<p>&nbsp;</p>
		<form id='install-form' action='index.php{$this->next_action}' method='post'>
		<input type='hidden' name='saved_data' value='{$this->saved_data}'>
		
		<div id='ipswrapper'>
		    <div class='main_shell'>

		 	    <h1><img src='images/package_icon.gif' align='absmiddle' /> Welcome to the IPB Upgrader</h1>
		 	    <div class='content_shell'>
		 	        <div class='package'>
		 	            <div>
		 	                <div class='install_info'>
		 	                    <h3>{$this->install_pages[$this->page_current]}</h3>
		 	                    		 	                    
    		 	                <ul id='progress'>

EOF;

foreach( $this->progress as $p )
{
echo "<li class='{$p[0]}'>{$p[1]}</li>";
}

echo <<<EOF
    		 	                </ul>
    		 	            </div>
		 	            
    		 	            <div class='content_wrap'>
    		 	                <div style='border-bottom: 1px solid #939393; padding-bottom: 4px;'>
    		 	                    <div class='float_img'>
    		 	                        <img src='images/box.gif' />
    		 	                    </div>

    		 	                    <div style='vertical-align: middle'>
    		 	                        <h2>{$this->product_name} Upgrade</h2>
    		 	                        <!--<strong>{$this->product_version}</strong>-->
    		 	                    </div>
    		 	                </div>
    		 	                <div style='clear:both'></div>

        		 	            {$this->page_content}        		 	          
            		 	        <br />        		 	            
    		 	            </div>
		 	            </div>
		 	            <br clear='all' />
    
		 	            <div class='hr'></div>
		 	            <div style='padding-top: 17px; padding-right: 15px; padding-left: 15px'>
		 	                <div style='float: left'>
		 	                    <input type='button' class='nav_button' value='Cancel Upgrade' onclick="window.location='index.php';return false;">
		 	                </div>

		 	                <div style='float: right'>
EOF;

if( ! $this->hide_next )
{
if( $this->next_action == 'disabled' )
{
echo <<<EOF
		 	                    <input type='submit' class='nav_button' value='Upgrade can not continue...' disabled='disabled'>
EOF;
}
else if( $this->in_error == 1 )
{
echo <<<EOF
		 	                    <input type='submit' class='nav_button' value='Continue regardless?'>
EOF;
}
else 
{
echo <<<EOF
		 	                    <input type='submit' class='nav_button' value='Next >'>
EOF;
}
}

echo <<<EOF
						</div>
		 	            </div>
		 	            <div style='clear: both;'></div>
		 	            <div class='copyright'>
		 	                &copy; 
EOF;
echo date("Y");
echo <<<EOF
 Invision Power Services, Inc.
		 	            </div>
		 	        </div>

		 	    </div>
    		</div>
    	</div>
    	
		</form>
	
	</body>
</html>
EOF;
	}
	
	// ------------------------------------------------------------
	// Login Page Template
	// ------------------------------------------------------------	
	function login_page( $msg='' )
	{
		$output = "";
		if ( $msg )
		{
			$extra = "<div class='warning'>
		        		<div style='float: left; margin-right: 7px; margin-left: 5px'><img src='images/warning.gif' /></div>
						<p>{$msg}</p>
					  </div><br />";
		}


$output .= <<<EOF
        		 	            <br />
        		 	            <div>
        		 	                <div style='float: left; margin-right: 7px; margin-left: 5px;'>
        		 	                    <img src='images/wizard.gif' align='absmiddle' />
        		 	                </div>
        		 	                <div>
        		 	                    Welcome to the upgrade routine for {$this->product_name}. This wizard will guide you through the upgrade process.
        		 	                </div>
        		 	            </div>
    <br/>{$extra}
    <h3>Verification Required - Please Log In</h3>
    You must log in with your forums administrative log in details to access the upgrade system.<br />
    <br />
	<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
		<tr>
			<td width='40%'  valign='middle'>Your Forum 
EOF;

if( $this->ipsclass->login_type == 'username' )
{
$output .= <<<EOF
Username
EOF;
}
else
{
$output .= <<<EOF
Email Address
EOF;
}
$output .= <<<EOF
:</td>
			<td width='60%'  valign='middle'><input type='text' style='width:100%' name='username' value='' class='sql_form'></td>
		</tr>
		<tr>
			<td width='40%'  valign='middle'>Your Forum Password:</td>
			<td width='60%'  valign='middle'><input type='password' style='width:100%' name='password' value='' class='sql_form'></td>
		</tr>
	</table>
EOF;

	return $output;
	}
	
	// ------------------------------------------------------------
	// Overview Page Template
	// ------------------------------------------------------------	
	function overview_page( $current_version, $summary )
	{
return <<<EOF
        		 	            <br />
        		 	            <div>
        		 	                <div style='float: left; margin-right: 7px; margin-left: 5px;'>
        		 	                    <img src='images/wizard.gif' align='absmiddle' />
        		 	                </div>
        		 	                <div>
        		 	                    Welcome to the upgrader for {$this->product_name}. This wizard will guide you through the upgrade process.
        		 	                </div>
        		 	            </div>
    <br/>
    <h3>Upgrade summary</h3>
    Current version: $current_version.<br />
    This script will: $summary<br />
    <br />

EOF;
	}

	// ------------------------------------------------------------
	// EULA Page Template
	// ------------------------------------------------------------
	function eula_page( $eula )
	{
return <<<EOF

<script language='javascript'>

check_eula = function()
{
	if( document.getElementById( 'eula' ).checked == true )
	{
		return true;
	}
	else
	{
		alert( 'You must agree to the license before continuing' );
		return false;
	}
}

document.getElementById( 'install-form' ).onsubmit = check_eula;

</script>

Please read and agree to the End User License Agreement before continuing.<br /><br />

        		 	            
        		 	            <div class='eula'>
									$eula        		 	                
                                </div>
                                <input type='checkbox' name='eula' id='eula'><strong> I agree to the license agreement</strong>


EOF;
	}
	
	
	// ------------------------------------------------------------
	// Install Page Splash Template
	// ------------------------------------------------------------		
	function install_page( $show_manual=0 )
	{
		$output = "";
		
$output = <<<EOF
<br />
The upgrader is now ready to complete the upgrade of your {$this->product_name}. Click <strong>Start</strong> to 
begin the automatic process!<br /><br />
    <ul id='links'>
        <li><img src='images/link.gif' align='absmiddle' /> <input type='checkbox' name='helpfile' id='helpfile' value='1' checked='checked' /> Update my help files if changes are found</li>
EOF;

if( $show_manual == 1 )
{
$output .= <<<EOF
        <li><img src='images/link.gif' align='absmiddle' /> <input type='checkbox' name='man' id='man' value='1' /> Show me manual upgrade steps for SQL queries to prevent PHP page timeouts. <b>WARNING:</b> If you select this option, you will be shown SQL queries that you must run at your mysql command line.  If you are not comfortable doing this, please submit a ticket and our technicians will assist you, or contact your webhost for assistance.</li>
EOF;
}

$output .= <<<EOF
    </ul>

<br /><br />
        		 	            
        		 	            <div style='float: right'>
        		 	                <input type='submit' class='nav_button' value='Start upgrade...'>
        		 	            </div>
EOF;

		return $output;
	}
	
	// ------------------------------------------------------------
	// Install Page Refresh Template
	// ------------------------------------------------------------		
	function install_page_refresh( $output=array() )
	{
$HTML = <<<EOF
<script type='text/javascript'>
//<![CDATA[
setTimeout("form_redirect()",2000);

function form_redirect()
{
	document.getElementById( 'install-form' ).submit();
}
//]]>
</script>
    		 	                <ul id='auto_progress'>
EOF;

foreach( $output as $l )
{
$HTML .= <<<EOF
    		 	                    <li><img src='images/check.gif' align='absmiddle' /> $l</li>
EOF;
}

$HTML .= <<<EOF
    		 	                </ul>
								<br />
								<div style='float: right'>
									<input type='submit' class='nav_button' value='Click here if not forwarded' />
								</div>
EOF;

		return $HTML;
	}
	
	// ------------------------------------------------------------
	// Install Progress Screen
	// ------------------------------------------------------------		
	function install_progress( $line )
	{
$HTML = <<<EOF
    		 	                <ul id='auto_progress'>
EOF;

foreach( $line as $l )
{
$HTML .= <<<EOF
    		 	                    <li><img src='images/check.gif' align='absmiddle' /> $l</li>
EOF;
}

$HTML .= <<<EOF
    		 	                </ul>
EOF;

		return $HTML;
	}
	
	
	// ------------------------------------------------------------
	// Install Skin Revert
	// ------------------------------------------------------------		
	function install_template_skinrevert( $skin_name="" )
	{
$HTML = <<<EOF
		<br /><h3><b>Revert skin changes?</b></h3><br />
		During upgrades, there are often changes to the skin templates to fix bugs or add new features.<br /><br />
		If you do not revert changes you have made to your skin templates, you will not see the changes we have made, however if you have modified
		your skin templates reverting the skin changes will cause you to <i><b>lose</b></i> the customizations you have made.<br /><br />
		If you have not made many customizations to your skins, it is recommended that you choose to revert your skin changes.<br /><br />
		If you have custom skins installed, or have heavily modified your skin templates, it is recommended that you use the skin difference tool
		available in your admin control panel after the upgrade is complete to apply new changes to your templates instead.<br /><br />
		
		<h3>Do you wish to revert changes made to '<b>{$skin_name}</b>'?</h3>
            <ul id='links'>
                <li><img src='images/link.gif' align='absmiddle' /> <input type='radio' name='do' value='all' /> Revert changes to all of my skins</li>
                <li><img src='images/link.gif' align='absmiddle' /> <input type='radio' name='do' value='1' /> Revert changes to '{$skin_name}'</li>
                <li><img src='images/link.gif' align='absmiddle' /> <input type='radio' name='do' value='none' /> Do not revert changes to any of my skins</li>
                <li><img src='images/link.gif' align='absmiddle' /> <input type='radio' name='do' value='0' /> Do not revert changes to '{$skin_name}'</li>
            </ul>
EOF;

		return $HTML;
	}	
	
	// ------------------------------------------------------------
	// Install Done Screen
	// ------------------------------------------------------------		
	function install_done( $url )
	{
$HTML .= <<<EOF
        		 	            <br />
        		 	            <img src='images/install_done.gif' align='absmiddle' />&nbsp;&nbsp;<span class='done_text'>Upgrade complete!</span><br /><br />
        		 	            Congratulations, your <a href='$url'>{$this->product_name}</a> is now up to date and ready to use!<br /><br />
        		 	            You should now login to your admin control panel and run the 'Rebuild Post Content' tool and the 'Rebuild Attachment Data' tool under Tools &amp; Settings, Recount &amp; Rebuild.  You may
        		 	            also wish to run the 2.1 -&gt; 2.2 tools found under 'Clean Up Tools'.
        		 	            <br /><br />Below are some links you may find useful.<br /><br /><br />
        		 	            <h3>Useful Links</h3>
        		 	            <ul id='links'>
        		 	                <li><b>Nullified by Terabyte</b></li>
        		 	            </ul>
EOF;
		return $HTML;
	}
	
	// ------------------------------------------------------------
	// Warning Message Template
	// ------------------------------------------------------------	
	function warning( $messages )
	{
$HTML = <<<EOF
<br />
    <div class='warning'>
        <div style='float: left; margin-right: 7px; margin-left: 5px'><img src='images/warning.gif' /></div>
EOF;

foreach( $messages as $msg )
{
	$HTML .= "<p>$msg</p>";	
}

$HTML .= <<<EOF
    </div><br />
   
EOF;

		$this->append( $HTML );
	}
}

?>