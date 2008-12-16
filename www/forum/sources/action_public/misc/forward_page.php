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
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Forward topic to a friend module
|   > Module written by Matt Mecham
|   > Date started: 21st March 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Mon 24th May 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class forwardpage
{
	var $ipsclass;
    var $output    = "";
    var $base_url  = "";
    var $html      = "";

    var $forum     = array();
    var $topic     = array();
    var $category  = array();

    
    /*-------------------------------------------------------------------------*/
	//
	// Our constructor, load words, load skin, print the topic listing
	//
	/*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
		//-----------------------------------------
		// Compile the language file
		//-----------------------------------------
		
        $this->ipsclass->load_language('lang_emails');
        $this->ipsclass->load_template('skin_emails');
        
        //-----------------------------------------
        // Check the input
        //-----------------------------------------
        
        $this->ipsclass->input['t'] = intval($this->ipsclass->input['t']);
        $this->ipsclass->input['f'] = intval($this->ipsclass->input['f']);
        
        if ( !$this->ipsclass->input['t'] )
        {
            $this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        //-----------------------------------------
        // Get the topic details
        //-----------------------------------------
        
        $this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topics', 'where' => "tid=".$this->ipsclass->input['t'] ) );
		$this->ipsclass->DB->simple_exec();
        
        $this->topic = $this->ipsclass->DB->fetch_row();
        
        $this->forum = $this->ipsclass->forums->forum_by_id[ $this->topic['forum_id'] ];
        
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if ( ! $this->forum['id'] )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        //-----------------------------------------
        // Error out if we can not find the topic
        //-----------------------------------------
        
        if (!$this->topic['tid'])
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        $this->base_url    = $this->ipsclass->base_url;
        
        $this->base_url_NS = "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}";
		
        //-----------------------------------------
        // Check viewing permissions, private forums,
        // password forums, etc
        //-----------------------------------------
        
        if (! $this->ipsclass->member['id'] )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'no_guests') );
        }
        
        $this->ipsclass->forums->forums_check_access( $this->forum['id'] );
        
        //-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		if ($this->ipsclass->input['CODE'] == '01')
		{
			$this->send_email();
		}
		else
		{
			$this->show_form();
		}
	}
	

	function send_email()
	{
		require ROOT_PATH."sources/classes/class_email.php";
		
		$this->email = new emailer();
		$this->email->ipsclass =& $this->ipsclass;
		$this->email->email_init();
		
		$lang_to_use = 'en';
		
		foreach( $this->ipsclass->cache['languages'] as $l )
		{
			if ($this->ipsclass->input['lang'] == $l['ldir'])
			{
				$lang_to_use = $l['ldir'];
			}
		}
		
		$check_array = array ( 'to_name'   =>  'stf_no_name',
							   'to_email'  =>  'stf_no_email',
							   'message'   =>  'stf_no_msg',
							   'subject'   =>  'stf_no_subject'
							 );
							 
		foreach ($check_array as $input => $msg)
		{
			if (empty($this->ipsclass->input[$input]))
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => $msg) );
			}
		}
		
		$to_email = $this->ipsclass->clean_email($this->ipsclass->input['to_email']);
		
		if (! $to_email )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_email' ) );
		}
		
		$this->email->get_template("forward_page", $lang_to_use);
			
		$this->email->build_message( array(
											'THE_MESSAGE'     => str_replace( "<br />", "\n", $this->ipsclass->input['message'] ),
											'TO_NAME'         => $this->ipsclass->input['to_name'],
											'FROM_NAME'       => $this->ipsclass->member['members_display_name'],
										  )
									);
									
		$this->email->subject = $this->ipsclass->input['subject'];
		$this->email->to      = $this->ipsclass->input['to_email'];
		$this->email->from    = $this->ipsclass->member['email'];
		$this->email->send_mail();
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['redirect'], "showtopic=".$this->topic['tid']."&amp;st=".$this->ipsclass->input['st'] );
	
	}
	
	
	function show_form()
	{
		require ROOT_PATH."cache/lang_cache/".$this->ipsclass->lang_id."/lang_email_content.php";
		
		$this->ipsclass->lang['send_text'] = $lang['send_text'];
		
		$lang_select = "<select name='lang' class='forminput'>\n";
		
		foreach( $this->ipsclass->cache['languages'] as $l )
		{
			$lang_select .= $l['ldir'] == $this->ipsclass->member['language'] ? "<option value='{$l['ldir']}' selected>{$l['lname']}</option>"
																		: "<option value='{$l['ldir']}'>{$l['lname']}</option>";
		}
 		
 		$lang_select .= "</select>";
		
		$this->ipsclass->lang['send_text'] = str_replace( "<#THE LINK#>" , $this->base_url_NS."?showtopic=".$this->topic['tid'], $this->ipsclass->lang['send_text'] );
		$this->ipsclass->lang['send_text'] = str_replace( "<#USER NAME#>", $this->ipsclass->member['members_display_name'], $this->ipsclass->lang['send_text'] );
		
		$this->output = $this->ipsclass->compiled_templates['skin_emails']->forward_form( $this->topic['title'], $this->ipsclass->lang['send_text'], $lang_select  );
		
		$this->page_title  = $this->ipsclass->lang['title'];
		
		$this->nav         = array ( "<a href='{$this->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>",  "<a href='".$this->base_url."showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>", $this->ipsclass->lang['title'] );
		
		$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
		
	}

}

?>