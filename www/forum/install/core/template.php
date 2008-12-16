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
		$this->page_current = ( $this->ipsclass->input['p'] ) ? $this->ipsclass->input['p'] : 'requirements';
		
		if( ! $this->install_pages[$this->page_current] )
		{
			$this->page_current = 'requirements';	
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
		$this->saved_data = urlencode($this->saved_data);
		
echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>IPS Installer</title>
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

		 	    <h1><img src='images/package_icon.gif' align='absmiddle' /> Welcome to the IPS Product Installer</h1>
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
    		 	                        <h2>{$this->product_name} Installation</h2>
    		 	                        <strong>{$this->product_version}</strong>
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
		 	                    <input type='button' class='nav_button' value='Cancel Installation' onclick="window.location='index.php';return false;" />
		 	                </div>

		 	                <div style='float: right'>
EOF;

if( ! $this->hide_next )
{
if( $this->next_action == 'disabled' )
{
echo <<<EOF
		 	                    <input type='submit' class='nav_button' value='Install can not continue...' disabled='disabled' />
EOF;
}
else 
{
if( !$this->next_action )
{
	$back = $this->ipsclass->my_getenv('HTTP_REFERER');
	
echo <<<EOF
	<input type='button' class='nav_button' value='< Back' onclick="window.location='{$back}';return false;" />
EOF;
}
echo <<<EOF
		 	                    <input type='submit' class='nav_button' value='Next >' />
EOF;
}
}

$date = date("Y");

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
	// Requirements Page Template
	// ------------------------------------------------------------	
	function requirements_page( $php_version, $sql_version )
	{
return <<<EOF
        		 	            <div>

        		 	                <div style='float: left; margin-right: 7px; margin-left: 5px;'>
        		 	                    <img src='images/wizard.gif' align='absmiddle' />
        		 	                </div>
        		 	                <div>
        		 	                    Welcome to the installer for {$this->product_name}. This wizard will guide you through the installation process.
        		 	                </div>
        		 	            </div>
    <br/><br/>
    		 	            
    <h3>System Requirements</h3>

    <br />
    <strong>PHP:</strong> v{$php_version} or better<br />
    <strong>SQL:</strong> mySQL v$sql_version or better
    <br /><br />
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
	// Address Page Template
	// ------------------------------------------------------------	
	function address_page( $dir, $url )
	{
return <<<EOF
<div id='warn-message' style='display:none;'><center><div id='warn-message-content'></div></center></div>

        		 	            <fieldset>
        		 	                <legend><img src='images/addresses.gif' align='absmiddle' />&nbsp; Address details</legend>

        		 	                <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
            		 	                <tr>
            		 	                    <td width='30%' class='title'>Install Directory:</td>
            		 	                    <td width='70%' class='content'><input type='text' class='sql_form' name='install_dir' value='{$dir}'></td>
            		 	                </tr>

        		 	                	<tr>
            		 	                    <td width='30%' class='title'>Install Address:</td>
            		 	                    <td width='70%' class='content'><input type='text' class='sql_form' name='install_url' value='{$url}'></td>
            		 	                </tr>
            		 	            </table>
            		 	        </fieldset>
EOF;
	}
	
	// ------------------------------------------------------------
	// DB Check Page Template
	// ------------------------------------------------------------		
	function db_check_page( $drivers=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_drivers = '';
		
		foreach ($drivers as $k => $v)
		{
			$selected  = ($v == "mysql") ? " selected='selected'" : "";
			$_drivers .= "<option value='".$v."'".$selected.">".strtoupper($v)."</option>\n";
		}
		
return <<<EOF
<div class='info' style='margin-top: 4px;'>
        		 	                <div class='float_img'><img src='images/help.gif' /></div>

        		 	                <div>Please select which database engine you wish to use.</div>
        		 	            </div>
        		 	            
        		 	            <fieldset>
        		 	                <legend><img src='images/db.gif' align='absmiddle' />&nbsp; Database Engine</legend>
        		 	                <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
										<tr>
            		 	                    <td width='30%' class='title'>SQL Driver:</td>
            		 	                    <td width='70%' class='content'>
            		 	                    	<select name='sql_driver' class='sql_form'>$_drivers</select>
            		 	                    </td>
            		 	                </tr>
            		 	            </table>
            		 	        </fieldset>

EOF;
	}
	// ------------------------------------------------------------
	// DB Page Template
	// ------------------------------------------------------------		
	function db_page()
	{
		$prefix = $_REQUEST['db_pre'] ? $_REQUEST['db_pre'] : INS_DEFAULT_SQL_PREFIX;
		
return <<<EOF
<div class='info' style='margin-top: 4px;'>
    <div class='float_img'><img src='images/help.gif' /></div>

    <div>Ask your webhost if you are unsure about any of these settings. You must create the database before installing..</div>
</div>

<fieldset>
    <legend><img src='images/db.gif' align='absmiddle' />&nbsp; Database details</legend>
    <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
		<!--{TOP.SQL}-->
        <tr>
            <td width='30%' class='title'>SQL Host:</td>
            <td width='70%' class='content'>
            	<input type='text' class='sql_form' value='localhost' name='db_host'>
            </td>
        </tr>
        <tr>
            <td class='title'>Database Name:</td>
            <td class='content'>
            	<input type='text' class='sql_form' name='db_name' value='{$_REQUEST['db_name']}'>
            </td>
        </tr>
        <tr>
            <td class='title'>SQL Username:</td>
            <td class='content'>
            	<input type='text' class='sql_form' name='db_user' value='{$_REQUEST['db_user']}'>
            </td>
        </tr>
        <tr>
            <td class='title'>SQL Password:</td>
            <td class='content'>
            	<input type='password' class='sql_form' name='db_pass'>
            </td>
        </tr>
        <tr>
            <td class='title'>SQL Table Prefix:</td>
            <td class='content'>
            	<input type='text' class='sql_form' name='db_pre' value='$prefix'>
            </td>
        </tr>
<!--{EXTRA.SQL}-->
    </table>
</fieldset>

EOF;
	}
	
	// ------------------------------------------------------------
	// Admin Page Template
	// ------------------------------------------------------------		
	function admin_page()
	{
return <<<EOF
								<fieldset>
        		 	                <legend><img src='images/admin.gif' align='absmiddle' />&nbsp; Your administrative account</legend>
        		 	                <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
            		 	                <tr>
            		 	                    <td width='30%' class='title'>Username:</td>

            		 	                    <td width='70%' class='content'><input type='text' class='sql_form' name='username'></td>
            		 	                </tr>
            		 	                <tr>
            		 	                    <td class='title'>Password:</td>
            		 	                    <td class='content'><input type='password' class='sql_form' name='password'></td>
            		 	                </tr>
            		 	                <tr>
            		 	                    <td class='title'>Confirm Password:</td>

            		 	                    <td class='content'><input type='password' class='sql_form' name='confirm_password'></td>
            		 	                </tr>
            		 	                <tr>
            		 	                    <td class='title'>E-mail Address:</td>
            		 	                    <td class='content'><input type='text' class='sql_form' name='email'></td>
            		 	                </tr>
            		 	            </table>
            		 	        </fieldset>

EOF;
	}
	
	// ------------------------------------------------------------
	// Install Page SPlash Template
	// ------------------------------------------------------------		
	function install_page()
	{
return <<<EOF
The installer is now ready to complete the installation of your {$this->product_name}. Click <strong>Start</strong> to 
begin the automatic process!<br /><br />

        		 	            
        		 	            <div style='float: right'>
        		 	                <input type='submit' class='nav_button' value='Start installation...'>
        		 	            </div>
EOF;
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
	// Install Done Screen
	// ------------------------------------------------------------		
	function install_done( $url, $install_locked )
	{
		$extra = '';
		
		if ( ! $install_locked )
		{
			$extra = "<div class='warning'>
		        		<div style='float: left; margin-right: 7px; margin-left: 5px'><img src='images/warning.gif' /></div>
						<p>INSTALLER NOT LOCKED<br />Please disable or remove 'install/index.php' immediately!</p>
					  </div>";
		}
		
$HTML .= <<<EOF
        		 	            <br />

        		 	            <img src='images/install_done.gif' align='absmiddle' />&nbsp;&nbsp;<span class='done_text'>Installation complete!</span><Br /><Br />
        		 	            Congratulations, your <a href='$url'>{$this->product_name}</a> is now installed and ready to use! Below are some 
        		 	            links you may find useful.<br /><br /><br />
        		 	            $extra
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