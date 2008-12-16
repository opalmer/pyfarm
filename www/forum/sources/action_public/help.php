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
|   > $Date: 2007-05-02 17:29:12 -0400 (Wed, 02 May 2007) $
|   > $Revision: 959 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Access the help files
|   > Module written by Matt Mecham
|   > Date started: 24th February 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class help
{
	# Classes
	var $ipsclass;
	
	# Others
    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";

	/*-------------------------------------------------------------------------*/
	// Auto run
	/*-------------------------------------------------------------------------*/
	
    function auto_run()
    {
    	//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
		$this->ipsclass->load_language('lang_help');
    	$this->ipsclass->load_template('skin_help');
    	
    	$this->base_url  = $this->ipsclass->base_url;
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['CODE'])
    	{
    		case '01':
    			$this->show_section();
    			break;
    		case '02':
    			$this->do_search();
    			break;
    		default:
    			$this->show_titles();
    			break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->ipsclass->print->add_output("$this->output");
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Show Titles
 	/*-------------------------------------------------------------------------*/
 	
 	function show_titles()
 	{
 		$seen = array();
 		
 		$this->output = $this->ipsclass->compiled_templates['skin_help']->start( $this->ipsclass->lang['page_title'], $this->ipsclass->lang['help_txt'], $this->ipsclass->lang['choose_file'] );
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, title, description',
 									  					'from'   => 'faq',
 									  					'order'  => 'position ASC'
 							 				 )      );
 		$this->ipsclass->DB->simple_exec();
 								
 		$cnt = 0;
 		
 		while ($row = $this->ipsclass->DB->fetch_row() )
 		{
 		
 			if (isset($seen[ $row['title'] ]) )
 			{
 				continue;
 			}
 			else
 			{
 				$seen[ $row['title'] ] = 1;
 			}
 			
 			$row['CELL_COLOUR'] = $cnt % 2 ? 'row1' : 'row2';
 			
 			$cnt++;
 			
 			$this->output .= $this->ipsclass->compiled_templates['skin_help']->row($row);
 			
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_help']->help_end();
 		
 		$this->page_title = $this->ipsclass->lang['page_title'];
 		$this->nav        = array( $this->ipsclass->lang['page_title'] );
 	}
	 
	/*-------------------------------------------------------------------------*/
	// Show section
	/*-------------------------------------------------------------------------*/
	 
 	function show_section()
 	{
 		$id = isset($this->ipsclass->input['HID']) ? intval($this->ipsclass->input['HID']) : 0;
 		
 		if ( ! $id )
 		{
 			$this->show_titles();
 			return;
 		}
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, title, text',
 									  				  'from'   => 'faq',
 									  				  'where'  => 'ID='.$id
 							 				)      );
 		$this->ipsclass->DB->simple_exec();
 		
 		$topic = $this->ipsclass->DB->fetch_row();
 		
		if ( ! $topic['id'] )
		{
			$this->ipsclass->Error( array( 'MSG' => 'help_no_id' ) );
		}
		
 		$this->output  = $this->ipsclass->compiled_templates['skin_help']->start( $this->ipsclass->lang['help_topic'], $this->ipsclass->lang['topic_text'], $topic['title'] );
 		$this->output .= $this->ipsclass->compiled_templates['skin_help']->display( $this->ipsclass->text_tidy( $topic['text'] ) );

 		$this->output .= $this->ipsclass->compiled_templates['skin_help']->help_end();
 		
 		$this->page_title = $this->ipsclass->lang['help_topic'];
 		$this->nav        = array( "<a href='{$this->base_url}&amp;act=Help'>{$this->ipsclass->lang['help_topics']}</a>", $this->ipsclass->lang['help_topic'] );
 	}	    
    
    /*-------------------------------------------------------------------------*/
    // Do search
    /*-------------------------------------------------------------------------*/
    
 	function do_search()
 	{
 		if (empty( $this->ipsclass->input['search_q'] ) )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_help_file') );
 		}
 		
 		$search_string = strtolower( str_replace( "*" , "%", $this->ipsclass->input['search_q'] ) );
 		$search_string = preg_replace( "/[<>\!\@£\$\^&\+\=\=\[\]\{\}\(\)\"':;\.,\/]/", "", $search_string );
 		
 		$seen = array();
 		
 		$this->output = $this->ipsclass->compiled_templates['skin_help']->start( $this->ipsclass->lang['page_title'], $this->ipsclass->lang['results_txt'], $this->ipsclass->lang['search_results'] );
 		
 		$this->ipsclass->DB->cache_add_query( 'help_search', array( 'search_string' => $search_string ) );
		$this->ipsclass->DB->cache_exec_query();
 		
 		$cnt = 0;
 		
 		while ($row = $this->ipsclass->DB->fetch_row() )
 		{
 		
 			if (isset($seen[ $row['title'] ]) )
 			{
 				continue;
 			}
 			else
 			{
 				$seen[ $row['title'] ] = 1;
 			}
 			
 			$row['CELL_COLOUR'] = $cnt % 2 ? 'row1' : 'row2';
 			
 			$cnt++;
 			
 			$this->output .= $this->ipsclass->compiled_templates['skin_help']->row($row);
 			
 		}
 		
 		if ($cnt == 0)
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_help']->no_results();
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_help']->help_end();
 		
 		$this->page_title = $this->ipsclass->lang['page_title'];
 		$this->nav        = array( "<a href='{$this->base_url}&amp;act=Help'>{$this->ipsclass->lang['help_topics']}</a>", $this->ipsclass->lang['results_title'] );
 	}

        
}

?>