<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > IPB Portal Core Module
|   > Module written by Matt Mecham
|   > Date started: Tuesday 2nd August 2005 (12:37)
+--------------------------------------------------------------------------
*/

class portal
{
	/**
	* IPSCLASS
	*
	* @var object
	*/
	var $ipsclass;
	
	/**
	* Object of portal stuff
	*
	* @var array
	*/
	var $portal_object = array();

    /**
	* Array of replacement tags
	*
	* @var array
	*/
    var $replace_tags  = array();
    
    /**
	* Array of tags to module...
	*
	* @var array
	*/
    var $remap_tags_module = array();
    
    /**
	* Array of tags to function...
	*
	* @var array
	*/
    var $remap_tags_function = array();
    
    /**
	* Array of module objects
	*
	* @var array
	*/
    var $module_objects = array();
    
    /**
	* Array of basic tags
	*
	* @var array
	*/
    var $basic_tags     = array( 'BASIC:SITENAV'    => '_show_sitenav',
    							 'BASIC:AFFILIATES' => '_show_affiliates' );
    
    /*-------------------------------------------------------------------------*/
    // AUTO-RUN
    /*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$conf_groups   = array();
    	$found_tags    = array();
    	$found_modules = array();
    	
    	//-----------------------------------------
    	// Get settings...
    	//-----------------------------------------
    	
    	foreach( $this->ipsclass->cache['portal'] as $portal_data )
    	{
    		if ( $portal_data['pc_settings_keyword'] )
    		{
    			$conf_groups[] = "'".$portal_data['pc_settings_keyword']."'";
    		}
    		
    		//-----------------------------------------
    		// Remap tags
    		//-----------------------------------------
    		
    		if ( is_array( $portal_data['pc_exportable_tags'] ) AND count( $portal_data['pc_exportable_tags'] ) )
    		{
    			foreach( $portal_data['pc_exportable_tags'] as $tag => $tag_data )
    			{
    				$this->remap_tags_function[ $tag ] = $tag_data[0];
    				$this->remap_tags_module[ $tag ]   = $portal_data['pc_key'];
    			}
    		}
    	}
    	
    	//-----------------------------------------
    	// Now really get them...
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->simple_construct( array( 'select'   => 'c.conf_key, c.conf_value, c.conf_default',
    												  'from'     => array( 'conf_settings' => 'c' ),
    												  'add_join' => array( 0 => array( 'select' => 't.conf_title_id, t.conf_title_keyword',
    												  								   'from'   => array( 'conf_settings_titles' => 't' ),
    												  								   'where'  => 'c.conf_group=t.conf_title_id',
    												  								   'type'   => "left" ) ),
    												  'where'  => 't.conf_title_keyword IN('.implode(",",$conf_groups).") OR conf_key LIKE 'csite%'" ) );
    	$this->ipsclass->DB->simple_exec();
    	
    	//-----------------------------------------
    	// Set 'em up
    	//-----------------------------------------
    	
    	while( $r = $this->ipsclass->DB->fetch_row() )
    	{
    		$value = $r['conf_value'] != "" ? $r['conf_value'] : $r['conf_default'];
    		
    		if ( $r['conf_key'] == 'csite_nav_contents' or $r['conf_key'] == 'csite_fav_contents' )
    		{
    			$this->raw[ $r['conf_key'] ] = str_replace( '&#39;', "'", str_replace( "\r\n", "\n", $value ) );
    		}
    		else
    		{
    			$this->ipsclass->vars[ $r['conf_key'] ] = $value;
    		}
    	}
    	
		//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
    	if ( ! $this->ipsclass->vars['csite_on'] )
    	{
	    	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'csite_not_enabled') );
    	}
    	
    	//-----------------------------------------
    	// Get global skin and language files
    	//-----------------------------------------
    	
		$this->ipsclass->load_language('lang_portal');
    	$this->ipsclass->load_template('skin_portal');
    	
        //-----------------------------------------
		// Get forums we're allowed to read
		// - Use forum cache incase we have NO perms
		//-----------------------------------------
		
		foreach( $this->ipsclass->cache['forum_cache'] as $f )
		{
			if ( ($this->ipsclass->check_perms($f['read_perms']) != TRUE) or ($f['password'] != "" ) )
        	{
        		$this->portal_object['bad_forum'][]  = $f['id'];
        	}
        	else
        	{
        		$this->portal_object['good_forum'][] = $f['id'];
        	}
        }
               
    	//-----------------------------------------
    	// Assign skeletal template ma-doo-bob
    	//-----------------------------------------
    	
    	$this->template = $this->ipsclass->compiled_templates['skin_portal']->csite_skeleton_template();
    	
    	//-----------------------------------------
    	// Grab all special tags
    	//-----------------------------------------
    	
    	preg_match_all( "#<!--\:\:(.+?)\:\:-->#", $this->template, $match );
    	
    	//-----------------------------------------
    	// Assign functions
    	//-----------------------------------------
    	
    	for ( $i=0; $i < count($match[0]); $i++ )
		{
			$tag = $match[1][$i];
			
			if ( $this->remap_tags_module[ $tag ] OR $this->basic_tags[ $tag ] )
			{
				$found_tags[ $tag ] = 1;
				
				if ( $this->remap_tags_module[ $tag ])
				{
					$found_modules[ $this->remap_tags_module[ $tag ] ] = 1;
				}
			}
		}
			
    	//-----------------------------------------
    	// Require modules...
    	//-----------------------------------------
    	
    	if ( is_array( $found_modules ) AND count( $found_modules ) )
    	{
    		foreach( $found_modules as $mod_name => $pointless )
    		{
    			if ( ! is_object( $this->module_objects[ $mod_name ] ) )
    			{
    				if ( file_exists( ROOT_PATH . 'sources/portal_plugins/'.$mod_name.'.php' ) )
    				{
						require_once( ROOT_PATH . 'sources/portal_plugins/'.$mod_name.'.php' );
						$constructor = 'ppi_'.$mod_name;
						$this->module_objects[ $mod_name ]                = new $constructor;
						$this->module_objects[ $mod_name ]->ipsclass      =& $this->ipsclass;
						$this->module_objects[ $mod_name ]->portal_object =& $this->portal_object;
						$this->module_objects[ $mod_name ]->init();
    				}
    			}
    		}
    	}
    	
    	//-----------------------------------------
    	// Get the tag replacements...
    	//-----------------------------------------
    	
    	if ( is_array( $found_tags ) AND count( $found_tags ) )
    	{
    		foreach( $found_tags as $tag_name => $even_more_pointless )
    		{
    			foreach( $this->basic_tags as $btag => $bfunction )
    			{
    				if ( $tag_name == $btag )
    				{ 
    					$this->replace_tags[ $tag_name ] = $this->$bfunction();
    					continue;
    				}
    			}
    			
    			$mod_obj = $this->remap_tags_module[ $tag_name ];
    			$fun_obj = $this->remap_tags_function[ $tag_name ];
    			
    			if ( method_exists( $this->module_objects[ $mod_obj ], $fun_obj ) )
    			{
    				$this->replace_tags[ $tag_name ] = $this->module_objects[ $mod_obj ]->$fun_obj();
    				continue;
    			}
    		}
    	}
    	
    	$this->_do_output();
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Do OUTPUT
 	/*-------------------------------------------------------------------------*/
 	
 	function _do_output()
 	{
 		//-----------------------------------------
        // SITE REPLACEMENTS
        //-----------------------------------------
        
        foreach( $this->replace_tags as $sbk => $sbv )
        {
        	$this->template = str_replace( "<!--::".$sbk."::-->", $sbv, $this->template );
        }
 		
 		//-----------------------------------------
 		// Pass to print...
 		//-----------------------------------------
 		
 		$this->ipsclass->print->add_output( $this->template );
 		
 		$this->ipsclass->print->do_output( array( 'TITLE' => $this->ipsclass->vars['csite_title'] ) );

		exit();
 	}

 	/*-------------------------------------------------------------------------*/
 	// Navigation Stuff
 	/*-------------------------------------------------------------------------*/
 	
 	function _show_sitenav()
 	{
 		if ( ! $this->ipsclass->vars['csite_nav_show'] )
 		{
 			return;
 		}
 		
 		$links = "";
 		
 		$raw_nav = $this->raw['csite_nav_contents'];
 		
 		foreach( explode( "\n", $raw_nav ) as $l )
 		{
 			preg_match( "#^(.+?)\[(.+?)\]$#is", trim($l), $matches );
 			
 			$matches[1] = trim($matches[1]);
 			$matches[2] = trim($matches[2]);
 			
 			if ( $matches[1] and $matches[2] )
 			{
	 			$matches[1] = str_replace( '&', '&amp;', str_replace( '&amp;', '&', $matches[1] ) );
	 			
 				$links .= $this->ipsclass->compiled_templates['skin_portal']->tmpl_links_wrap( str_replace( '{board_url}', $this->ipsclass->base_url, $matches[1] ), $matches[2] );
 			}
 		}
 		
 		return $this->ipsclass->compiled_templates['skin_portal']->tmpl_sitenav($links);
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Affiliates
 	/*-------------------------------------------------------------------------*/
 	
 	function _show_affiliates()
 	{
 		if ( ! $this->ipsclass->vars['csite_fav_show'] )
 		{
 			return;
 		}
 		
 		return $this->ipsclass->compiled_templates['skin_portal']->tmpl_affiliates($this->raw['csite_fav_contents']);
 	}  
}

?>