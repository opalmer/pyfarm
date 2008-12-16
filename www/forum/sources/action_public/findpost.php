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
|   > $Date: 2006-09-26 07:17:30 -0400 (Tue, 26 Sep 2006) $
|   > $Revision: 574 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Find-a-post module (a.k.a: The smallest IPB class ever)
|   > Module written by Matt Mecham
|   > Date started: 14th April 2004
|   > Interesting Fact: I've had iTunes playing every Radiohead tune
|   > I own for about a week now. Thats a lot of repeats. Got some
|   > cool rare tracks though. Every album+rare+b sides = 6.7 hours
|   > music. Not bad. I need to get our more. No, you can't take the
|   > laptop with you - nerd.
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

class findpost
{
	# Classes
	var $ipsclass;
	
	# Others
	var $post;
	
    function auto_run()
    {
		//-----------------------------------------
		// Find a post
		// Don't really need to check perms 'cos topic
		// will do that for us. Woohoop
		//-----------------------------------------
		
		$pid = intval($this->ipsclass->input['pid']);
		
		if ( ! $pid )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
		
		//-----------------------------------------
		// Get topic...
		//-----------------------------------------
		
		$post = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'posts', 'where' => 'pid='.$pid ) );
		
		if ( ! $post['topic_id'] )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as posts',
									 				  'from'   => 'posts',
													  'where'  => "topic_id=".$post['topic_id']." AND pid <= ".$pid,
											)      );
							
		$this->ipsclass->DB->simple_exec();
		
		$cposts = $this->ipsclass->DB->fetch_row();
		
		if ( (($cposts['posts']) % $this->ipsclass->vars['display_max_posts']) == 0 )
		{
			$pages = ($cposts['posts']) / $this->ipsclass->vars['display_max_posts'];
		}
		else
		{
			$number = ( ($cposts['posts']) / $this->ipsclass->vars['display_max_posts'] );
			$pages = ceil( $number);
		}
		
		$st = ($pages - 1) * $this->ipsclass->vars['display_max_posts'];
		$hl = $this->ipsclass->input['hl'] ? '&hl=' . trim( $this->ipsclass->input['hl'] ) : '';
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$post['topic_id']."&st=$st&p=$pid".$hl."&#entry".$pid);
 	}
}

?>