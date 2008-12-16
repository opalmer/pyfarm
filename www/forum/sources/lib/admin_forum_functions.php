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
|   > $Date: 2007-07-02 16:25:56 -0400 (Mon, 02 Jul 2007) $
|   > $Revision: 1080 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin forum functions library
|   > Script written by Matt Mecham
|   > Date started: 19th November 2003
|
|   > DBA Checked: Tue 25th May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forum_functions
{
	# Global
	var $ipsclass;
	
	# HTML
	
	var $html;
	
	var $type     = "";
	var $printed  = 0;
	var $show_all = 0;
	var $skins    = array();
	var $need_desc = array();
	
	/*-------------------------------------------------------------------------*/
	// Forum - Build Children (of the CORN!!!)
	/*-------------------------------------------------------------------------*/
	
	function forum_build_children($root_id, $temp_html="", $depth_guide="")
	{
		if ( isset($this->ipsclass->forums->forum_cache[ $root_id ]) AND is_array( $this->ipsclass->forums->forum_cache[ $root_id ] ) )
		{
			foreach( $this->ipsclass->forums->forum_cache[ $root_id ] as $forum_data )
			{
				if ( isset($this->ipsclass->vars['forum_cache_minimum']) AND $this->ipsclass->vars['forum_cache_minimum'] )
				{
					$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
					$this->need_desc[] = $forum_data['id'];
				}
					
				$temp_html .= $this->render_forum($forum_data, $depth_guide);
				
				$temp_html = $this->forum_build_children( $forum_data['id'], $temp_html, $depth_guide . $this->ipsclass->forums->depth_guide );
			}
		}
		
		return $temp_html;
	}
	
	/*-------------------------------------------------------------------------*/
	// Forum - Render forum entry (NOT of the CORN!!!)
	/*-------------------------------------------------------------------------*/
	
	function render_forum($r, $depth_guide="")
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$desc       = "";
		$mod_string = "";
		
		$r['skin_id'] = isset($r['skin_id']) ? $r['skin_id'] : '';
		
		//-----------------------------------------
		// Skin
		//-----------------------------------------
		
		if ( ! $this->html )
		{
			$this->html = $this->ipsclass->acp_load_template('cp_skin_forums');
		}
		
		//-----------------------------------------
		// Manage forums?
		//-----------------------------------------
		
		if ( $this->type == 'manage' )
		{
			//-----------------------------------------
			// Show main forums...
			//-----------------------------------------
			
			if ( ! $this->show_all )
			{
				$children = $this->ipsclass->forums->forums_get_children( $r['id'] );
				
				$sub       = array();
				$subforums = "";
				$count     = 0;
				
				//-----------------------------------------
				// Build sub-forums link
				//-----------------------------------------
				
				if ( count($children) )
				{
					$r['name'] = "<a href='{$this->ipsclass->base_url}&section=content&act=forum&f={$r['id']}'>".$r['name']."</a>";
					
					foreach ( $children as $cid )
					{
						$count++;
						
						$cfid = $cid;
						
						if ( $count == count($children) )
						{
							//-----------------------------------------
							// Last subforum, link to parent
							// forum...
							//-----------------------------------------
							
							if ( !isset($children[ $count - 2 ]) OR ! $cfid = $children[ $count - 2 ] )
							{
								$cfid = $r['id'];
							}
						}
						
						$sub[] = "<a href='{$this->ipsclass->base_url}&section=content&act=forum&f={$this->ipsclass->forums->forum_by_id[$cid]['parent_id']}'>".$this->ipsclass->forums->forum_by_id[$cid]['name']."</a>";
					}
				}
				
				if ( count( $sub ) )
				{
					$subforums = '<fieldset style="margin-top:4px"><legend>Subforums</legend>'.implode( ", ", $sub ).'</fieldset>';
				}
				
				$desc = "{$r['description']}{$subforums}";
			}
			
			//-----------------------------------------
			// Moderators
			//-----------------------------------------
			
			$r['_modstring'] = "";
			
			foreach( $this->moderators as $data )
			{
				if ( $data['forum_id'] == $r['id'] )
				{
					if ($data['is_group'] == 1)
					{
						$data['_fullname'] = 'Group: '.$data['group_name'];
					}
					else
					{
						$data['_fullname'] = $data['members_display_name'];
					}
					
					$r['_modstring'] .= $this->html->render_moderator_entry( $data );
				}
			}
			
			//-----------------------------------------
			// Print
			//-----------------------------------------
			
			$this->skins[$r['skin_id']] = (isset($this->skins[$r['skin_id']]) AND $this->skins[$r['skin_id']]) ? $this->skins[$r['skin_id']] : '';

			return $this->html->render_forum_row( $desc, $r, $depth_guide, $this->skins[$r['skin_id']] );
		}
		
		//-----------------------------------------
		// REORDER
		//-----------------------------------------
		
		else if ( $this->type == 'reorder' )
		{
			$this->printed++;
			
			$no_root = count( $this->ipsclass->forums->forums_get_children($this->ipsclass->input['f']) );
			
			$reorder = "<select id='realbutton' name='f_{$r['id']}'>";
			
			for( $i = 1 ; $i <= $no_root ; $i++ )
			{
				$sel = "";
				
				if ( $this->printed == $i )
				{
					$sel =  'selected="selected" ';
				}
				
				$reorder .= "\n<option value='$i'{$sel}>$i</option>";
			}
			
			$reorder .= "</select>\n";
			
			return $this->html->render_reorder_row( $r, $reorder, $depth_guide );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Forum - SHOW CAT(MEOW) (MEOW _ WOOF :&)
	/*-------------------------------------------------------------------------*/
	
	function forum_show_cat($content, $r, $show_buttons=1, $show_reorder=0)
	{
		$this->printed++;
		
		$no_root = count( $this->ipsclass->forums->forum_cache['root'] );
		$reorder = "";
		
		//-----------------------------------------
		// Build reorder list
		//-----------------------------------------
		
		if ( $this->type != 'reorder' )
		{
			$reorder = "<select id='editbutton' name='f_{$r['id']}'>";
			
			for( $i = 1 ; $i <= $no_root ; $i++ )
			{
				$sel = "";
				
				if ( $this->printed == $i )
				{
					$sel =  'selected="selected" ';
				}
				
				$reorder .= "\n<option value='$i'{$sel}>$i</option>";
			}
			
			$reorder .= "</select>\n";
		}
		
		$this->ipsclass->html .= $this->html->forum_wrapper( $content, $r, $reorder, $show_buttons, $show_reorder );
	}
	
	/*-------------------------------------------------------------------------*/
	// Forum - END CAT(MEOW) (MEOW _ WOOF MOO :&)
	/*-------------------------------------------------------------------------*/
	
	function forum_end_cat($r=array())
	{
		// NO LONGER USED?
		if ( $this->type == 'manage' )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		}
		else if ( $this->type == 'reorder' )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// List all forums
	/*-------------------------------------------------------------------------*/
	
	function forums_list_forums()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->show_all = intval($this->ipsclass->input['showall']);
		
		if ( ! $this->html )
		{
			$this->html = $this->ipsclass->acp_load_template('cp_skin_forums');
		}
		
		//-----------------------------------------
		// Manage forums
		//-----------------------------------------
		
		if ( $this->type == 'manage' )
		{
			foreach( $this->ipsclass->cache['skin_id_cache'] as $id => $data )
			{
				$this->skins[ $id ] = $data['set_name'];
			}
		}
		
		$temp_html = "";
		$fid       = intval( $this->ipsclass->input['f'] );
		
		//-----------------------------------------
		// Show all forums
		//-----------------------------------------
		
		if ( $this->show_all )
		{
			foreach( $this->ipsclass->forums->forum_cache['root'] as $forum_data )
			{
				$cat_data    = $forum_data;
				$depth_guide = "";
				$temp_html 	 = "";
				
				if ( isset($this->ipsclass->forums->forum_cache[ $forum_data['id'] ]) AND is_array( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] ) )
				{
					foreach( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] as $forum_data )
					{
						if ( isset($this->ipsclass->vars['forum_cache_minimum']) AND $this->ipsclass->vars['forum_cache_minimum'] )
						{
							$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
							$this->need_desc[]         = $forum_data['id'];
						}
				
						$temp_html .= $this->render_forum($forum_data, $depth_guide);

						$temp_html = $this->forum_build_children( $forum_data['id'], $temp_html, '<span style="color:gray">&#0124;</span>'.$depth_guide . $this->ipsclass->forums->depth_guide );
					}
				}
				
				if( !$temp_html )
				{
					$temp_html = $this->html->render_no_forums( $cat_data['id'] );
				}
				
				$this->ipsclass->html .= $this->forum_show_cat($temp_html, $cat_data);
				unset($temp_html);
			}
		}
		
		//-----------------------------------------
		// Show root forums
		//-----------------------------------------
		
		else if ( ! $fid )
		{
			$seen_count  = 0;
			$total_items = 0;
			
			foreach( $this->ipsclass->forums->forum_cache[ 'root' ] as $forum_data )
			{
				$cat_data    = $forum_data;
				$depth_guide = "";
				$temp_html	 = "";
				
				if ( isset($this->ipsclass->forums->forum_cache[ $forum_data['id'] ]) AND is_array( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] ) )
				{
					foreach( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] as $forum_data )
					{
						if ( isset($this->ipsclass->vars['forum_cache_minimum']) AND $this->ipsclass->vars['forum_cache_minimum'] )
						{
							$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
							$this->need_desc[]         = $forum_data['id'];
						}
				
						$temp_html .= $this->render_forum($forum_data, $depth_guide);
					}
				}
				
				if( !$temp_html )
				{
					$temp_html = $this->html->render_no_forums( $cat_data['id'] );
				}				
				
				$this->ipsclass->html .= $this->forum_show_cat($temp_html, $cat_data);
				unset($temp_html);
			}
		}
		
		//-----------------------------------------
		// Show per ID forums
		//-----------------------------------------
		
		else
		{
			$cat_data    = array();
			$depth_guide = "";
			
		
			if ( is_array( $this->ipsclass->forums->forum_cache[ $fid ] ) )
			{
				$cat_data    = $this->ipsclass->forums->forum_by_id[ $fid ];
				$depth_guide = "";
				
				foreach( $this->ipsclass->forums->forum_cache[ $fid ] as $forum_data )
				{
					if ( isset($this->ipsclass->vars['forum_cache_minimum']) AND $this->ipsclass->vars['forum_cache_minimum'] )
					{
						$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
						$this->need_desc[]         = $forum_data['id'];
					}
			
					$temp_html .= $this->render_forum($forum_data, $depth_guide);
				}
			}
			
			if( !$temp_html )
			{
				$temp_html = $this->html->render_no_forums( $cat_data['id'] );
			}
			
			$this->ipsclass->html .= $this->forum_show_cat( $temp_html, $this->ipsclass->forums->forum_by_id[ $fid ], 0, 1 );
			unset($temp_html);
		}
		
		//-----------------------------------------
        // Get descriptions?
        //-----------------------------------------
        
        if ( isset($this->ipsclass->vars['forum_cache_minimum']) AND $this->ipsclass->vars['forum_cache_minimum'] and count($this->need_desc) )
        {
        	$this->ipsclass->DB->simple_construct( array( 'select' => 'id,description', 'from' => 'forums', 'where' => 'id IN('.implode( ',', $this->need_desc ) .')' ) );
        	$this->ipsclass->DB->simple_exec();
        	
        	while( $r = $this->ipsclass->DB->fetch_row() )
        	{
        		$this->ipsclass->html = str_replace( "<!--DESCRIPTION:{$r['id']}-->", $r['description'], $this->ipsclass->html );
        	}
        }
	}
	
	/*-------------------------------------------------------------------------*/
	// forum jumpee
	/*-------------------------------------------------------------------------*/
	
	function ad_forums_forum_list($restrict=0)
	{
		if ( $restrict != 1 )
		{	
			//$jump_array[] = array( '-1', 'Make Root (Category)' );
		}
		else
		{
			$jump_array = array();
		}
		
		foreach( $this->ipsclass->forums->forum_cache['root'] as $forum_data )
		{
			$jump_array[] = array( $forum_data['id'], $forum_data['name'] );
			
			$depth_guide = $this->ipsclass->forums->depth_guide;
			
			if ( isset($this->ipsclass->forums->forum_cache[ $forum_data['id'] ]) AND is_array( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] ) )
			{
				foreach( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] as $forum_data )
				{
					$jump_array[] = array( $forum_data['id'], $depth_guide.$forum_data['name'] );
					
					$jump_array = $this->forums_forum_list_internal( $forum_data['id'], $jump_array, $depth_guide . $this->ipsclass->forums->depth_guide );
				}
			}
		}
		
		return $jump_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// INTERNAL
	/*-------------------------------------------------------------------------*/
	
	function forums_forum_list_internal($root_id, $jump_array=array(), $depth_guide="")
	{
		if ( isset($this->ipsclass->forums->forum_cache[ $root_id ]) AND  is_array( $this->ipsclass->forums->forum_cache[ $root_id ] ) )
		{
			foreach( $this->ipsclass->forums->forum_cache[ $root_id ] as $forum_data )
			{
				$jump_array[] = array( $forum_data['id'], $depth_guide.$forum_data['name'] );
				
				$jump_array = $this->forums_forum_list_internal( $forum_data['id'], $jump_array, $depth_guide . $this->ipsclass->forums->depth_guide );
			}
		}
		
		
		return $jump_array;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// forum return data
	/*-------------------------------------------------------------------------*/
	
	function ad_forums_forum_data()
	{
		foreach( $this->ipsclass->forums->forum_cache['root'] as $forum_data )
		{
			$forum_data['depthed_name'] = $forum_data['name'];
			$forum_data['root_forum']   = 1;
			
			$jump_array[ $forum_data['id'] ] = $forum_data;
			
			$depth_guide = $this->ipsclass->forums->depth_guide;
			
			if ( isset($this->ipsclass->forums->forum_cache[ $forum_data['id'] ]) AND is_array( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] ) )
			{
				foreach( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] as $forum_data )
				{
					$forum_data['depthed_name'] = $depth_guide.$forum_data['name'];
					
					$jump_array[ $forum_data['id'] ] = $forum_data;
					
					$jump_array = $this->forums_forum_data_internal( $forum_data['id'], $jump_array, $depth_guide . $this->ipsclass->forums->depth_guide );
				}
			}
		}
		
		return $jump_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// INTERNAL
	/*-------------------------------------------------------------------------*/
	
	function forums_forum_data_internal($root_id, $jump_array=array(), $depth_guide="")
	{
		if ( isset($this->ipsclass->forums->forum_cache[ $root_id ]) AND is_array( $this->ipsclass->forums->forum_cache[ $root_id ] ) )
		{
			foreach( $this->ipsclass->forums->forum_cache[ $root_id ] as $forum_data )
			{
				$forum_data['depthed_name'] = $depth_guide.$forum_data['name'];
					
				$jump_array[ $forum_data['id'] ] = $forum_data;
				
				$jump_array = $this->forums_forum_data_internal( $forum_data['id'], $jump_array, $depth_guide . $this->ipsclass->forums->depth_guide );
			}
		}
		
		
		return $jump_array;
	}
	

}



?>