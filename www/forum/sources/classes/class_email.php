<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2007-09-24 16:32:11 -0400 (Mon, 24 Sep 2007) $
|   > $Revision: 1110 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Sending email module
|   > Module written by Matt Mecham
|   > Date started: 26th February 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
+--------------------------------------------------------------------------
|
|   QUOTE OF THE MODULE: (Taken from "Shrek" (c) Dreamworks Pictures)
|   --------------------
|	DONKEY: We can stay up late, swap manly stories and in the morning,  
|           I'm making waffles!
|
+-------------------------------------------------------------------------- 
*/

// This module is fairly basic, more functionality is expected in future
// versions (such as MIME attachments, SMTP stuff, etc)

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class emailer {

	# global
	var $ipsclass;
	
	var $from         = "";
	var $to           = "";
	var $subject      = "";
	var $lang_subject = "";
	var $message      = "";
	var $header       = "";
	var $footer       = "";
	var $template     = "";
	var $error        = "";
	var $parts        = array();
	var $bcc          = array();
	var $mail_headers = array();
	var $rfc_headers  = "";
	var $multipart    = "";
	var $boundry      = "";
	var $extra_opts   = "";
	
	var $html_email   = 0;
	var $char_set     = 'iso-8859-1';
	
	// RFC specifies \r\n as eol HOWEVER it fails on many mail servers/clients
	// whereas \n seems to work in 99% of cases, so we'll stick with that
	var $header_eol	  = "\n";	
	
	var $smtp_fp      = FALSE;
	var $smtp_msg     = "";
	var $smtp_port    = "";
	var $smtp_host    = "localhost";
	var $smtp_user    = "";
	var $smtp_pass    = "";
	var $smtp_code    = "";
	
	var $wrap_brackets = 0;
	
	var $mail_method  = 'mail';
	
	var $temp_dump    = 0;
	var $root_path    = './';
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function emailer($ROOT_PATH="")
	{
		if ( $ROOT_PATH )
		{
			$this->root_path = $ROOT_PATH;
		}
		
		if ( ! defined( 'ROOT_PATH') )
		{
			define( 'ROOT_PATH', $this->root_path );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Email init
	/*-------------------------------------------------------------------------*/
	
	function email_init()
	{
		//-----------------------------------------
		// Set up SMTP if we're using it
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['mail_method'] == 'smtp' )
		{ 
			$this->mail_method = 'smtp';
			$this->smtp_port   = ( intval($this->ipsclass->vars['smtp_port']) != "" ) ? intval($this->ipsclass->vars['smtp_port']) : 25;
			$this->smtp_host   = (     $this->ipsclass->vars['smtp_host'] != ""     ) ?     $this->ipsclass->vars['smtp_host']     : 'localhost';
			$this->smtp_user   = $this->ipsclass->vars['smtp_user'];
			$this->smtp_pass   = $this->ipsclass->vars['smtp_pass'];
		}
		
		//-----------------------------------------
		// Assign $from as the admin out email address, this can be
		// over-riden at any time.
		//-----------------------------------------
		
		$this->from          = $this->ipsclass->vars['email_out'];
		$this->temp_dump     = isset($this->ipsclass->vars['fake_mail']) ? $this->ipsclass->vars['fake_mail'] : '';
		$this->wrap_brackets = $this->ipsclass->vars['mail_wrap_brackets'];
		$this->extra_opts    = $this->ipsclass->vars['php_mail_extra'];
		
		//-----------------------------------------
		// Temporarily assign $header and $footer, this can be over-riden
		// also
		//-----------------------------------------
		
		$this->header   = isset($this->ipsclass->vars['email_header']) ? $this->ipsclass->vars['email_header'] : '';
		$this->footer   = isset($this->ipsclass->vars['email_footer']) ? $this->ipsclass->vars['email_footer'] : '';
		$this->boundry  = "----=_NextPart_000_0022_01C1BD6C.D0C0F9F0";  //"b".md5(uniqid(time()));
		
		$this->char_set = $this->ipsclass->vars['gb_char_set'];
		 
		$this->ipsclass->vars['board_name'] = $this->clean_message($this->ipsclass->vars['board_name']);
	}
	
	/*-------------------------------------------------------------------------*/
	// ADD ATTACHMENT
	/*-------------------------------------------------------------------------*/
	
	function add_attachment($data = "", $name = "", $ctype='application/octet-stream')
	{
		$this->parts[] = array( 'ctype'  => $ctype,
								'data'   => $data,
								'encode' => 'base64',
								'name'   => $name
							  );
	}
	
	/*-------------------------------------------------------------------------*/
	// BUILD HEADERS
	/*-------------------------------------------------------------------------*/
	
	function build_headers()
	{
		$extra_headers		= array();
		$extra_headers_rfc	= "";
		
		//-----------------------------------------
		// Start mail headers
		//-----------------------------------------
		
		$this->mail_headers['MIME-Version'] = "1.0";
		$this->mail_headers['Date'] 		= date( "r" );
		
		$this->mail_headers['From'] = '"'.$this->ipsclass->vars['board_name'].'" <'.$this->from.'>';
		
		if ( $this->mail_method != 'smtp' )
		{
			if ( count( $this->bcc ) > 1 )
			{
				$this->mail_headers['Bcc'] = implode( "," , $this->bcc );
			}
		}
		else
		{
			if ( $this->to )
			{
				$this->mail_headers['To'] = $this->to;
			}

			$this->mail_headers['Subject'] = $this->subject;
		}
		
		//-----------------------------------------
		// we're not spam, really!
		//-----------------------------------------
		
		$this->mail_headers['Return-Path'] 	= $this->from;
		$this->mail_headers['X-Priority'] 	= "3";
		$this->mail_headers['X-Mailer'] 	= "IPB PHP Mailer";
		
		//-----------------------------------------
		// Count.. oh you get the idea
		//-----------------------------------------
		
		if ( count ($this->parts) > 0 )
		{
			if ( ! $this->html )
			{
				$extra_headers[0]['Content-Type'] = "multipart/mixed;\n\tboundary=\"".$this->boundry."\"";
				
				$extra_headers[0]['notencode']	  = "\n\nThis is a MIME encoded message.\n\n--".$this->boundry."\n";
				
				$extra_headers[1]['Content-Type'] = "text/plain;\n\tcharset=\"".$this->char_set."\"";
				$extra_headers[1]['Content-Transfer-Encoding'] = "quoted-printable";
				
				$extra_headers[1]['notencode'] = "\n\n".$this->message."\n\n--".$this->boundry;
			}
			else
			{
				$extra_headers[0]['Content-Type'] = "multipart/mixed;\n\tboundary=\"".$this->boundry."\"";
				
				$extra_headers[0]['notencode']	  = "\n\nThis is a MIME encoded message.\n\n--".$this->boundry."\n";
				
				$extra_headers[1]['Content-Type'] = "text/html; charset=\"".$this->char_set."\"";
				$extra_headers[1]['Content-Transfer-Encoding'] = "quoted-printable";
				
				$extra_headers[1]['notencode'] = "\n\n".$this->message."\n\n--".$this->boundry;
			}
			
			$extra_headers[2]['notencode'] = $this->build_multipart();
			
			reset($extra_headers);
			
			foreach( $extra_headers as $subset => $the_header )
			{
				foreach( $the_header as $k => $v )
				{
					if( $k == 'notencode' )
					{
						$extra_headers_rfc .= $v;
					}
					else
					{
						$v = $this->encode_headers( array( 'v' => $v ) );
						
						$extra_headers_rfc .= $k.': '.$v['v'].$this->header_eol;
					}
				}
			}
			
			$this->message = "";
		}
		else
		{
			//-----------------------------------------
			// HTML (hitmuhl) ?
			//-----------------------------------------
			
			if ( $this->html_email )
			{
				$this->mail_headers['Content-type'] = 'text/html; charset="'.$this->char_set.'"';
			}
			else
			{
				$this->mail_headers['Content-type'] = 'text/plain; charset="'.$this->char_set.'"';
			}
		}
	
		$this->encode_headers();
		
		foreach( $this->mail_headers as $k => $v )
		{
			$this->rfc_headers .= $k.": ".$v.$this->header_eol;
		}
		
		//-----------------------------------------
		// Attachments extra?
		//-----------------------------------------
		
		if( $extra_headers_rfc )
		{
			$this->rfc_headers .= $extra_headers_rfc;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// ENCODE ATTACHMENT
	/*-------------------------------------------------------------------------*/
	
	function encode_attachment($part)
	{
		$msg = chunk_split(base64_encode($part['data']));
		
		$headers 	= array();
		$header_str	= "";

		$headers['Content-Type'] 				= $part['ctype']. ($part['name'] ? "; name =\"".$part['name']."\"" : "");
		$headers['Content-Transfer-Encoding'] 	= $part['encode'];
		$headers['Content-Disposition']			= "attachment; filename=\"".$part['name']."\"";
		
		$headers = $this->encode_headers( $headers );
		
		foreach( $headers as $k => $v )
		{
			$header_str .= $k.': '.$v.$this->header_eol;
		}
		
		$header_str .= "\n\n".$msg."\n";
		
		return $header_str;
	}
	
	/*-------------------------------------------------------------------------*/
	// ENCODE HEADERS - RFC2047
	/*-------------------------------------------------------------------------*/
	
	function encode_headers( $headers = array() )
	{	
		$enc_headers = count($headers) ? $headers : $this->mail_headers;
		
        foreach( $enc_headers as $header => $value) 
        {
	        $orig_value = $value;
	        
            preg_match_all( '/(\w*[\x80-\xFF]+\w*)/', $value, $matches );

            foreach ($matches[1] as $match_value)
            {
		        if( $header == 'From' OR $header == 'Content-Type' OR $header == 'Content-Disposition' )
		        {
			        // Either sendmail or the email servers don't like 'From' encoded...let's remove the board name
			        // 	and just move along, as email address cannot contain nasty characters themselves
			        
			        $this->mail_headers[ $header ] = $orig_value;//$this->from;
			        $enc_headers[ $header ] = $orig_value;//$this->from;
			        
			        continue 2;
		        }
	        	            
                $replacement = preg_replace_callback( '/([=_\?\x00-\x1F\x80-\xFF])/', create_function( '$match', 'return "=" . strtoupper( dechex( ord( "$match[1]" ) ) );' ), $match_value );
                
               	$value = str_replace( $match_value, $replacement, $value );
            }
            
            if( $orig_value != $value )
            {
	            $value = '=?' . $this->char_set . '?Q?' . str_replace( " ", "=20", $value ) . '?=';
            }

            if( !count($headers) )
            {
            	$this->mail_headers[ $header ] = $value;
        	}
        	else
        	{
	        	$enc_headers[ $header ] = $value;
        	}
        }
        
        return $enc_headers;
    }	
	
	/*-------------------------------------------------------------------------*/
	// BUILD MULTIPART
	/*-------------------------------------------------------------------------*/
	
	function build_multipart() 
	{
		$multipart = "";
		
		for ($i = sizeof($this->parts) - 1 ; $i >= 0 ; $i--)
		{
			$multipart .= $this->header_eol.$this->encode_attachment($this->parts[$i]) . "--".$this->boundry;
		}
		
		return $multipart . "--\n";
		
	}
	
	
	/*-------------------------------------------------------------------------*/
	// send_mail:
	// Physically sends the email
	/*-------------------------------------------------------------------------*/
	
	function send_mail()
	{
		//-----------------------------------------
		// Wipe ya face
		//-----------------------------------------
		
		$this->to   = preg_replace( "/[ \t]+/" , ""  , $this->to   );
		$this->from = preg_replace( "/[ \t]+/" , ""  , $this->from );
		
		$this->to   = preg_replace( "/,,/"     , ","  , $this->to );
		$this->from = preg_replace( "/,,/"     , ","  , $this->from );
		
		$this->to     = preg_replace( "#\#\[\]'\"\(\):;/\$!£%\^&\*\{\}#" , "", $this->to  );
		$this->from   = preg_replace( "#\#\[\]'\"\(\):;/\$!£%\^&\*\{\}#" , "", $this->from);
		
		$this->subject = ( trim($this->lang_subject) != "" ) ? $this->lang_subject : $this->subject;
		
		$this->subject = $this->clean_message($this->subject);
		
		if ( $this->subject )
		{
			$this->subject .= " ( ".$this->ipsclass->vars['board_name']." )";
		}
		
		//-----------------------------------------
		// Build headers
		//-----------------------------------------
		
		$this->build_headers();
		
		if( $this->mail_method != 'smtp' )
		{
			$subject = $this->encode_headers( array( 'Subject' => $this->subject ) );

			$this->subject = $subject['Subject'];
		}
		
		//-----------------------------------------
		// Lets go..
		//-----------------------------------------
		
		if ( ($this->from) and ($this->subject) )
		{
			//-----------------------------------------
			// Tmp dump? (Testing)
			//-----------------------------------------
			
			if ($this->temp_dump == 1)
			{
				$blah = $this->subject."\n------------\n".$this->rfc_headers."\n\n".$this->message;
				
				$pathy = $this->root_path.'_mail/'.date("M-j-Y,hi-A").str_replace( '@', '+', $this->to ).".txt";
				$fh = fopen ($pathy, 'w');
				fputs ($fh, $blah, strlen($blah) );
				fclose($fh);
			}
			else
			{
				//-----------------------------------------
				// PHP MAIL()
				//-----------------------------------------
				
				if ($this->mail_method != 'smtp')
				{
					if ( ! @mail( $this->to, $this->subject, $this->message, $this->rfc_headers, $this->extra_opts ) )
					{
						# Try without args for safe mode peeps
						if ( ! @mail( $this->to, $this->subject, $this->message, $this->rfc_headers ) )
						{
							$this->fatal_error("Could not send the email", "Failed at 'mail' command");
						}
					}
				}
				//-----------------------------------------
				// SMTP
				//-----------------------------------------
				else
				{
					$this->smtp_send_mail();
				}
			}
		}
		else
		{
			$this->fatal_error("From or subject empty");
			return FALSE;
		}
		
		$this->to           = "";
		$this->from         = "";
		$this->message      = "";
		$this->subject      = "";
		$this->mail_headers = array();
		$this->rfc_headers	= "";
		
		$this->email_init();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// get_template:
	// Queries the database, and stores the template we wish to use in memory
	/*-------------------------------------------------------------------------*/

	function get_template($name="", $language="")
	{
		global $lang;
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ($name == "")
		{
			$this->error++;
			$this->fatal_error("A valid email template ID was not passed to the email library during template parsing", "");
		}
		
		//-----------------------------------------
		// Default?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['default_language'] == "")
		{
			$this->ipsclass->vars['default_language'] = 'en';
		}
		
		if ($language == "")
		{
			$language = $this->ipsclass->vars['default_language'];
		}
		
		//-----------------------------------------
		// Check and get
		//-----------------------------------------
		
		if ( ! file_exists($this->root_path."cache/lang_cache/$language/lang_email_content.php") )
		{
			if ( ! is_array( $lang ) )
			{
				$lang = array();
			}

			require_once( $this->root_path."cache/lang_cache/".$this->ipsclass->vars['default_language']."/lang_email_content.php" );
		}
		else
		{
			if ( ! is_array( $lang ) )
			{
				$lang = array();
			}
			
			require_once( $this->root_path."cache/lang_cache/$language/lang_email_content.php" );
		}
		
		//-----------------------------------------
		// Stored KEY?
		//-----------------------------------------
		
		if ( ! isset($lang[ $name ]) )
		{
			if ( $language == $this->ipsclass->vars['default_language'] )
			{
				$this->fatal_error("Could not find an email template with an ID of '$name'", "");
			}
			else
			{
				require_once( $this->root_path."cache/lang_cache/".$this->ipsclass->vars['default_language']."/lang_email_content.php" );
				
				if ( ! isset($lang[ $name ]) )
				{
					$this->fatal_error("Could not find an email template with an ID of '$name'", "");
				}
			}
		}
		
		//-----------------------------------------
		// Subject?
		//-----------------------------------------
		
		if ( isset( $lang[ 'subject__'. $name ] ) )
		{
			$this->lang_subject = stripslashes($lang[ 'subject__'. $name ]);
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->template = stripslashes($lang['header']) . stripslashes($lang[ $name ]) . stripslashes($lang['footer']);
	}
		
	/*-------------------------------------------------------------------------*/
	// build_message:
	// Swops template tags into the corresponding string held in $words array.
	// Also joins header and footer to message and cleans the message for sending
	/*-------------------------------------------------------------------------*/
		
	function build_message( $words )
	{
		if ( $this->template == "" )
		{
			$this->error++;
			$this->fatal_error("Could not build the email message, no template assigned", "Make sure a template is assigned first.");
		}
		
		$this->message = $this->template;
		
		//-----------------------------------------
		// Add some default words
		//-----------------------------------------
		
		$words['BOARD_ADDRESS'] = $this->ipsclass->vars['board_url'] . '/index.' . $this->ipsclass->vars['php_ext'];
		$words['WEB_ADDRESS']   = $this->ipsclass->vars['home_url'];
		$words['BOARD_NAME']    = $this->ipsclass->vars['board_name'];
		$words['SIGNATURE']     = isset($this->ipsclass->vars['signature']) ? $this->ipsclass->vars['signature'] : '';
		
		//-----------------------------------------
		// Swap the words
		//-----------------------------------------
		
		$this->_words = $words;
		
		$this->message = preg_replace_callback( "/<#(.+?)#>/", array( &$this, '_swap_words' ), $this->message );
		
		$this->_words = array();
		
		$this->message = $this->clean_message( $this->message );
	}
	
	/*-------------------------------------------------------------------------*/
	// Swap words.
	/*-------------------------------------------------------------------------*/
	
	function _swap_words( $matches )
	{
		return $this->_words[ $matches[1] ];
	}
	
	/*-------------------------------------------------------------------------*/
	// clean_message: (Mainly used internally)
	// Ensures that \n and <br> are converted into CRLF (\r\n)
	// Also unconverts some BBCode
	/*-------------------------------------------------------------------------*/
	
	function clean_message($message = "" ) {
	
		$message = preg_replace( "/^(\r|\n)+?(.*)$/", "\\2", $message );
	
		$message = preg_replace( "#<b>(.+?)</b>#" , "\\1", $message );
		$message = preg_replace( "#<i>(.+?)</i>#" , "\\1", $message );
		$message = preg_replace( "#<s>(.+?)</s>#" , "--\\1--", $message );
		$message = preg_replace( "#<u>(.+?)</u>#" , "-\\1-"  , $message );
		
		$message = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $message );
		$message = preg_replace( "#<img.+?emoid=\"(.+?)\".+?".">#is", "\\1"  , $message );
		   
		$message = preg_replace( "#<!--c1-->(.+?)<!--ec1-->#", "\n\n------------ CODE SAMPLE ----------\n"  , $message );
		$message = preg_replace( "#<!--c2-->(.+?)<!--ec2-->#", "\n-----------------------------------\n\n"  , $message );
		
		$message = preg_replace( "#<!--QuoteBegin-->(.+?)<!--QuoteEBegin-->#"                       , "\n\n------------ QUOTE ----------\n" , $message );
		$message = preg_replace( "#<!--QuoteBegin--(.+?)\+(.+?)-->(.+?)<!--QuoteEBegin-->#"         , "\n\n------------ QUOTE ----------\n" , $message );
		$message = preg_replace( "#<!--QuoteEnd-->(.+?)<!--QuoteEEnd-->#"                           , "\n-----------------------------\n\n" , $message );
		
		$message = preg_replace( "#<!--Flash (.+?)-->.+?<!--End Flash-->#"                         , "(FLASH MOVIE)" , $message );
		$message = preg_replace( "#<img src=[\"'](\S+?)['\"].+?".">#"                                  , "(IMAGE: \\1)"   , $message );
		$message = preg_replace( "#<a href=[\"'](http|https|ftp|news)://(\S+?)['\"].+?".">(.+?)</a>#"  , "\\1://\\2 (\\3)"     , $message );
		$message = preg_replace( "#<a href=[\"']mailto:(.+?)['\"]>(.+?)</a>#"                       , "(EMAIL: \\2)"   , $message );
		
		$message = preg_replace( "#<!--sql-->(.+?)<!--sql1-->(.+?)<!--sql2-->(.+?)<!--sql3-->#i"    , "\n\n--------------- SQL -----------\n\\2\n----------------\n\n", $message);
		$message = preg_replace( "#<!--html-->(.+?)<!--html1-->(.+?)<!--html2-->(.+?)<!--html3-->#i", "\n\n-------------- HTML -----------\n\\2\n----------------\n\n", $message);
		
		$message = preg_replace( "#<!--EDIT\|.+?\|.+?-->#" , "" , $message );
		
		//-----------------------------------------
		// Bear with me...
		//-----------------------------------------
		
		$message = str_replace( "\n"          , "<br />", $message );
		$message = str_replace( "\r"          , ""      , $message );
		
		$message = str_replace( "<br>"        , "\r\n", $message );
		$message = str_replace( "<br />"      , "\r\n", $message );
		$message = preg_replace( "#<.+?".">#" , ""    , $message );
		
		$message = str_replace( "&quot;", "\"", $message );
		$message = str_replace( "&#092;", "\\", $message );
		$message = str_replace( "&#036;", "\$", $message );
		$message = str_replace( "&#33;" , "!" , $message );
		$message = str_replace( "&#34;" , '"' , $message );
		$message = str_replace( "&#39;" , "'" , $message );
		$message = str_replace( "&#40;" , "(" , $message );
		$message = str_replace( "&#41;" , ")" , $message );
		$message = str_replace( "&lt;"  , "<" , $message );
		$message = str_replace( "&gt;"  , ">" , $message );
		$message = str_replace( "&#124;", '|' , $message );
		$message = str_replace( "&amp;" , "&" , $message );
		$message = str_replace( "&#58;" , ":" , $message );
		$message = str_replace( "&#91;" , "[" , $message );
		$message = str_replace( "&#93;" , "]" , $message );
		$message = str_replace( "&#064;", '@' , $message );
		$message = str_replace( "&#60;" , '<' , $message );
		$message = str_replace( "&#62;" , '>' , $message );
		$message = str_replace( "&nbsp;" , ' ' , $message );
		
		return $message;
	}
	
	/*-------------------------------------------------------------------------*/
	// FATAL ERROR : LOG AND RETURN
	/*-------------------------------------------------------------------------*/
	
	function fatal_error($msg, $help="")
	{
		$this->ipsclass->DB->do_insert( 'mail_error_logs',
										array(
												'mlog_date'     => time(),
												'mlog_to'       => $this->to,
												'mlog_from'     => $this->from,
												'mlog_subject'  => $this->subject,
												'mlog_content'  => substr( $this->message, 0, 200 ),
												'mlog_msg'      => $msg,
												'mlog_code'     => $this->smtp_code,
												'mlog_smtp_msg' => $this->smtp_msg
											 )
									  );
		
		return;
	}
	
	
	/*-------------------------------------------------------------------------*/
	//
	// SMTP methods
	//
	/*-------------------------------------------------------------------------*/
	
	//-----------------------------------------
	//| get_line()
	//|
	//| Reads a line from the socket and returns
	//| CODE and message from SMTP server
	//|
	//-----------------------------------------
	
	function smtp_get_line()
	{
		$this->smtp_msg = "";
		
		while ( $line = fgets( $this->smtp_fp, 515 ) )
		{
			$this->smtp_msg .= $line;
			
			if ( substr($line, 3, 1) == " " )
			{
				break;
			}
		}
	}
	
	//-----------------------------------------
	//| send_cmd()
	//|
	//| Sends a command to the SMTP server
	//| Returns TRUE if response, FALSE if not
	//|
	//-----------------------------------------
	
	function smtp_send_cmd($cmd)
	{
		$this->smtp_msg  = "";
		$this->smtp_code = "";
		
		fputs( $this->smtp_fp, $cmd."\r\n" );
		
		$this->smtp_get_line();
		
		$this->smtp_code = substr( $this->smtp_msg, 0, 3 );
		
		return $this->smtp_code == "" ? FALSE : TRUE;
	}
	
	//-----------------------------------------
	//| error()
	//|
	//| Returns SMTP error to our global
	//| handler
	//|
	//-----------------------------------------
	
	function smtp_error($err = "")
	{
		$this->smtp_msg = $err;
		$this->fatal_error( $err );
		return;
	}
	
	//-----------------------------------------
	//| crlf_encode()
	//|
	//| RFC 788 specifies line endings in
	//| \r\n format with no periods on a 
	//| new line
	//-----------------------------------------
	
	function smtp_crlf_encode($data)
	{
		$data .= "\n";
		$data  = str_replace( "\n", "\r\n", str_replace( "\r", "", $data ) );
		$data  = str_replace( "\n.\r\n" , "\n. \r\n", $data );
		
		return $data;
	}
	
	//-----------------------------------------
	//| send_mail
	//|
	//| Does the bulk of the email sending
	//-----------------------------------------
	
	//$this->to, $this->subject, $this->message, $this->mail_headers
	
	function smtp_send_mail()
	{
		$this->smtp_fp = @fsockopen( $this->smtp_host, intval($this->smtp_port), $errno, $errstr, 30 );
		
		if ( ! $this->smtp_fp )
		{
			$this->smtp_error("Could not open a socket to the SMTP server");
			return;
		}
		
		$this->smtp_get_line();
		
		$this->smtp_code = substr( $this->smtp_msg, 0, 3 );
		
		if ( $this->smtp_code == 220 )
		{
			$data = $this->smtp_crlf_encode( $this->rfc_headers."\n" . $this->message);
			
			//-----------------------------------------
			// HELO!, er... HELLO!
			//-----------------------------------------
			
			$this->smtp_send_cmd("HELO ".$this->smtp_host);
			
			if ( $this->smtp_code != 250 )
			{
				$this->smtp_error("HELO");
				return;
			}
			
			//-----------------------------------------
			// Do you like my user!
			//-----------------------------------------
			
			if ($this->smtp_user and $this->smtp_pass)
			{
				$this->smtp_send_cmd("AUTH LOGIN");
				
				if ( $this->smtp_code == 334 )
				{
					$this->smtp_send_cmd( base64_encode($this->smtp_user) );
					
					if ( $this->smtp_code != 334  )
					{
						$this->smtp_error("Username not accepted from the server");
						return;
					}
					
					$this->smtp_send_cmd( base64_encode($this->smtp_pass) );
					
					if ( $this->smtp_code != 235 )
					{
						$this->smtp_error("Password not accepted from the server");
						return;
					}
				}
				else
				{
					$this->smtp_error("This server does not support authorisation");
					return;
				}
			}
			
			//-----------------------------------------
			// We're from MARS!
			//-----------------------------------------
			
			if ( $this->wrap_brackets )
			{
				if ( ! preg_match( "/^</", $this->from ) )
				{
					$this->from = "<".$this->from.">";
				}
			}
			
			$this->smtp_send_cmd("MAIL FROM:".$this->from);
			
			if ( $this->smtp_code != 250 )
			{
				$this->smtp_error();
				return;
			}
			
			$to_arry = array( $this->to );
			
			if ( count( $this->bcc ) > 0 )
			{
				foreach ($this->bcc as $bcc)
				{
					if ( preg_match( "/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/", str_replace( " ", "", $bcc ) ) )
					{
						$to_arry[] = $bcc;
					}
				}
			}
			
			//-----------------------------------------
			// You are from VENUS!
			//-----------------------------------------
			
			foreach( $to_arry as $to_email )
			{
				if ( $this->wrap_brackets )
				{
						$this->smtp_send_cmd("RCPT TO:<".$to_email.">");
				}
				else
				{
					$this->smtp_send_cmd("RCPT TO:".$to_email);
				}
				
				if ( $this->smtp_code != 250 )
				{
					$this->smtp_error("Incorrect email address: $to_email");
					return;
					break;
				}
			}
			
			//-----------------------------------------
			// SEND MAIL!
			//-----------------------------------------
			
			$this->smtp_send_cmd("DATA");
			
			if ( $this->smtp_code == 354 )
			{
				//$this->smtp_send_cmd( $data );
				fputs( $this->smtp_fp, $data."\r\n" );
			}
			else
			{
				$this->smtp_error("Error on write to SMTP server");
				return;
			}
			
			//-----------------------------------------
			// GO ON, NAFF OFF!
			//-----------------------------------------
			
			$this->smtp_send_cmd(".");
			
			if ( $this->smtp_code != 250 )
			{
				$this->smtp_error();
				return;
			}
			
			$this->smtp_send_cmd("quit");
			
			if ( $this->smtp_code != 221 )
			{
				$this->smtp_error();
				return;
			}
			
			//-----------------------------------------
			// Tubby-bye-bye!
			//-----------------------------------------
			
			@fclose( $this->smtp_fp );
		}
		else
		{
			$this->smtp_error();
			return;
		}
	}

}

?>