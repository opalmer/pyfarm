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
|   > $Date: 2007-06-15 15:16:20 -0400 (Fri, 15 Jun 2007) $
|   > $Revision: 1042 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Topic display in printable format module
|   > Module written by Matt Mecham
|   > Date started: 25th March 2002
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

class printpage {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    var $moderator = array();
    var $forum     = array();
    var $topic     = array();
    var $category  = array();
    var $mem_groups = array();
    var $mem_titles = array();
    var $mod_action = array();
    var $poll_html  = "";
    
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
		
        $this->ipsclass->load_language('lang_printpage');
        $this->ipsclass->load_template('skin_printpage');
        
        //-----------------------------------------
        // Check the input
        //-----------------------------------------
        
        $this->ipsclass->input['t'] = intval($this->ipsclass->input['t']);
        $this->ipsclass->input['f'] = intval($this->ipsclass->input['f']);
        
        if ( ! $this->ipsclass->input['t'] or ! $this->ipsclass->input['f'] )
        {
            $this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        //-----------------------------------------
        // Get the forum info based on the
        // forum ID, get the category name, ID,
        // and get the topic details
        //-----------------------------------------
        
        $this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topics', 'where' => "tid=".$this->ipsclass->input['t'] ) );
		$this->ipsclass->DB->simple_exec();
        
        $this->topic = $this->ipsclass->DB->fetch_row();
        
        $this->forum = $this->ipsclass->forums->forum_by_id[ $this->topic['forum_id'] ];
        					
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if ( ! $this->forum['id'])
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
        
        //-----------------------------------------
        // Check viewing permissions, private forums,
        // password forums, etc
        //-----------------------------------------
        
        if ( ( ! $this->topic['pinned']) and ( ! $this->ipsclass->member['g_other_topics'] ) )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
        }
        
        //-----------------------------------------
        // Check access
        //-----------------------------------------
        
        $this->ipsclass->forums->forums_check_access( $this->forum['id'], 1, 'topic' );
        
        if( !$this->topic['approved'] AND !( $this->ipsclass->member['g_is_supmod'] OR $this->ipsclass->member['_moderator'][ $this->topic['forum_id'] ]['topic_q'] ) )
        {
	        $this->ipsclass->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
        }
        
        //-----------------------------------------
        //
        // Main logic engine
        //
        //-----------------------------------------
        
        if ($this->ipsclass->input['client'] == 'choose')
        {
        	// Show the "choose page"
        	
        	$this->page_title = $this->topic['title'];
		
			$this->nav = array ( "<a href='{$this->ipsclass->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
							 	 "<a href='{$this->ipsclass->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>"
						       );
						       
						       
			$this->output = $this->ipsclass->compiled_templates['skin_printpage']->choose_form($this->forum['id'], $this->topic['tid'], $this->topic['title']);
						       
			$this->ipsclass->print->add_output("$this->output");
			
        	$this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
        	
        	exit(); // Incase we haven't already done so :p
        }
        else
        {
        	$header = 'text/html';
        	$ext    = '.html';
        	
        	switch ($this->ipsclass->input['client'])
        	{
        		case 'printer':
        			$header = 'text/html';
        			$ext    = '.html';
        			break;
        		case 'html':
        			$header = 'unknown/unknown';
        			$ext    = '.html';
        			break;
        		default:
        			$header = 'application/msword';
        			$ext    = '.doc';
        	}
        }
        
        $title = substr( str_replace( " ", "_" , preg_replace( "/&(lt|gt|quot|#124|#036|#33|#39);/", "", $this->topic['title'] ) ), 0, 12);
        
		//$this->output .= "<br><br><font size='1'><center>Powered by Invision Power Board<br>&copy; 2002 Invision PS</center></font></body></html>";
		
		@header( "Content-type: $header;charset={$this->ipsclass->vars['gb_char_set']}" );
		
		if ($this->ipsclass->input['client'] != 'printer')
		{
			@header("Content-Disposition: attachment; filename=$title".$ext);
		}
		
		print $this->get_posts();
		
		exit;
		
				        
	}
	
	/*-------------------------------------------------------------------------*/
	// GET POSTS
	/*-------------------------------------------------------------------------*/
	
	function get_posts()
	{
		//-----------------------------------------
		// Render the page top
		//-----------------------------------------
		
		$posts_html = $this->ipsclass->compiled_templates['skin_printpage']->pp_header( $this->forum['name'], $this->topic['title'], $this->topic['starter_name'] , $this->forum['id'], $this->topic['tid']);

		$max_posts   = 300;
		$attach_pids = array();
		
		$this->ipsclass->DB->simple_construct( array ( 'select' => '*',
													   'from'   => 'posts',
													   'where'  => "topic_id={$this->topic['tid']} and queued=0",
													   'order'  => 'pid',
													   'limit'  => array(0, $max_posts)
												   )   );
		$this->ipsclass->DB->simple_exec();
		
		//-----------------------------------------    
		// Loop through to pick out the correct member IDs.
		// and push the post info into an array - maybe in the future
		// we can add page spans, or maybe save to a PDF file?
		//-----------------------------------------
		
		$the_posts      = array();
		$mem_ids        = "";
		$member_array   = array();
		$cached_members = array();
		
		while ( $i = $this->ipsclass->DB->fetch_row() )
		{
			$the_posts[] = $i;
			
			if ($i['author_id'] != 0)
			{
				if (preg_match( "/'".$i['author_id']."',/", $mem_ids) )
				{
					continue;
				}
				else
				{
					$mem_ids .= "'".$i['author_id']."',";
				}
			}
		}
		
		//-----------------------------------------
		// Fix up the member_id string
		//-----------------------------------------
		
		$mem_ids = preg_replace( "/,$/", "", $mem_ids);
		
		//-----------------------------------------
		// Get the member profiles needed for this topic
		//-----------------------------------------
		
		if ($mem_ids != "")
		{
			$this->ipsclass->DB->cache_add_query( 'print_page_get_members', array( 'mem_ids' => $mem_ids ) );
			$this->ipsclass->DB->cache_exec_query();
		
			while ( $m = $this->ipsclass->DB->fetch_row() )
			{
				if ($m['id'] and $m['name'])
				{
					if (isset($member_array[ $m['id'] ]))
					{
						continue;
					}
					else
					{
						$member_array[ $m['id'] ] = $m;
					}
				}
			}
		}
		
		//-----------------------------------------
		// Format and print out the topic list
		//-----------------------------------------
		
		$td_col_cnt = 0;
		
		foreach ($the_posts as $row) {
		
			$poster = array();
			
			//-----------------------------------------
			// Get the member info. We parse the data and cache it.
			// It's likely that the same member posts several times in
			// one page, so it's not efficient to keep parsing the same
			// data
			//-----------------------------------------
			
			if ($row['author_id'] != 0)
			{
				//-----------------------------------------
				// Is it in the hash?
				//-----------------------------------------
				
				if ( isset($cached_members[ $row['author_id'] ]) )
				{
					//-----------------------------------------
					// Ok, it's already cached, read from it
					//-----------------------------------------
					
					$poster = $cached_members[ $row['author_id'] ];
					$row['name_css'] = 'normalname';
				}
				else
				{
					//-----------------------------------------
					// Ok, it's NOT in the cache, is it a member thats
					// not been deleted?
					//-----------------------------------------
					
					if ($member_array[ $row['author_id'] ])
					{
						$row['name_css'] = 'normalname';
						$poster = $member_array[ $row['author_id'] ];
						
						//-----------------------------------------
						// Add it to the cached list
						//-----------------------------------------
						
						$cached_members[ $row['author_id'] ] = $poster;
					}
					else
					{
						//-----------------------------------------
						// It's probably a deleted member, so treat them as a guest
						//-----------------------------------------
						
						$poster = $this->ipsclass->set_up_guest( $row['author_id'] );
						$row['name_css'] = 'unreg';
					}
				}
			}
			else
			{
				//-----------------------------------------
				// It's definately a guest...
				//-----------------------------------------
				
				$poster = $this->ipsclass->set_up_guest( $row['author_name'] );
				$row['name_css'] = 'unreg';
			}
			
			//-----------------------------------------
			
			$row['post_css'] = $td_col_count % 2 ? 'post1' : 'post2';
			
			++$td_col_count;
			
			//-----------------------------------------
			
			$row['post'] = preg_replace( "/<!--EDIT\|(.+?)\|(.+?)-->/", "", $row['post'] );
			
			//-----------------------------------------
		
			$row['post_date']   = $this->ipsclass->get_date( $row['post_date'], 'LONG', 1 );
			
			//-----------------------------------------
 			// Quoted attachments?
 			//-----------------------------------------
 			
			$attach_pids[ $row['pid'] ] = $row['pid'];
 			
			$row['post'] = $this->parse_message($row['post']);
			
			//-----------------------------------------
			// Siggie stuff
			//-----------------------------------------
			
			$row['signature'] = "";
			
			if ($poster['signature'] and $this->ipsclass->member['view_sigs'])
			{
				if ($row['use_sig'] == 1)
				{
					$row['signature'] = $this->ipsclass->compiled_templates['skin_global']->signature_separator( $poster['signature'] );
				}
			}
			
			//-----------------------------------------
			// Parse HTML tag on the fly
			//-----------------------------------------
			
			$posts_html .= $this->ipsclass->compiled_templates['skin_printpage']->pp_postentry( $poster, $row );
		}
		
		if ( count( $attach_pids ) )
 		{
 			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				require_once( ROOT_PATH . 'sources/classes/attach/class_attach.php' );
				$this->class_attach           =  new class_attach();
				$this->class_attach->ipsclass =& $this->ipsclass;
				
				$this->ipsclass->load_template( 'skin_topic' );
				
				$this->class_attach->type  = 'post';
				$this->class_attach->init();
			}

			$posts_html = $this->class_attach->render_attachments( $posts_html, $attach_pids );
 		}
 		
		//-----------------------------------------
		// Print the footer
		//-----------------------------------------
		
		$posts_html .= $this->ipsclass->compiled_templates['skin_printpage']->pp_end();
		
		//-----------------------------------------
		// Macros
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->skin['_macros'] ) )
      	{
			foreach( $this->ipsclass->skin['_macros'] as $row )
			{
				if ( $row['macro_value'] != "" )
				{
					$posts_html = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $posts_html );
				}
			}
		}
		
		$posts_html = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $posts_html );
		$posts_html = str_replace( "<#EMO_DIR#>", $this->ipsclass->skin['_emodir']  , $posts_html );
		
		$posts_html = preg_replace( "#([^/])style_images/(<\#IMG_DIR\#>|".preg_quote($this->ipsclass->skin['_imagedir'], '/').")#is", "\\1".$this->ipsclass->vars['board_url']."/style_images/\\2", $posts_html );
		$posts_html = preg_replace( "#([\"'])style_emoticons/#is", "\\1".$this->ipsclass->vars['board_url']."/style_emoticons/", $posts_html );
		
		//-----------------------------------------
        // CSS
        //-----------------------------------------
        
        $this->ipsclass->skin['_usecsscache'] = 0;
        
        $css = $this->ipsclass->print->_get_css();
        
        $posts_html = str_replace( '<!--IPB.CSS-->', $css, $posts_html );
        
		return $posts_html;
	}
	

	function parse_message($message="")
	{
		$message = preg_replace( "#<!--Flash (.+?)-->.+?<!--End Flash-->#i"                            , "(FLASH MOVIE)" , $message );
		$message = preg_replace( "#<a href=[\"'](http|https|ftp|news)://(\S+?)['\"].+?".">(.+?)</a>#"  , "\\1://\\2"     , $message );
		
		return $message;
		
	}

}

?>