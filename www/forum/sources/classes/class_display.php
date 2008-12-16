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
|   > $Date: 2007-08-31 10:37:25 -0400 (Fri, 31 Aug 2007) $
|   > $Revision: 1100 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > DISPLAY CLASS
|   > Module written by Matt Mecham
|   > Date started: 26th January 2004
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class display {

	# Global
	var $ipsclass;
	
    var $to_print = "";
    var $output   = "";
    var $macros   = "";
    
    //-----------------------------------------
    // CONSTRUCTOR
    //-----------------------------------------
    
    function display()
    {
    }
    
    
    //-----------------------------------------
    // Appends the parsed HTML to our class var
    //-----------------------------------------
    
    function add_output($to_add)
    {
        $this->to_print .= $to_add;
        //return 'true' on success
        return true;
    }
    

    
    /*-------------------------------------------------------------------------*/
    //
    // Parses all the information and prints it.
    //
    /*-------------------------------------------------------------------------*/
    
    function do_output($output_array)
    {
		global $Debug;
        
        //-----------------------------------------
        // Global
        //-----------------------------------------
        
        $components_links = "";
        
        //-----------------------------------------
        // UNPACK MACROS
        //-----------------------------------------
        
        $this->_unpack_macros();
        
        //-----------------------------------------
        // END TIMER
        //-----------------------------------------
        
        $this->ex_time  = sprintf( "%.4f",$Debug->endTimer() );
        
        //-----------------------------------------
        // SQL DEBUG?
        //-----------------------------------------
        
        $this->_check_debug();
        
        $stats = $this->_show_debug();

		//-----------------------------------------
		// Stop E_ALL moaning...
		//-----------------------------------------

		$this->ipsclass->cache['systemvars']['task_next_run'] = isset( $this->ipsclass->cache['systemvars']['task_next_run'] ) ? $this->ipsclass->cache['systemvars']['task_next_run'] : 0;

        //-----------------------------------------
        // NAVIGATION
        //-----------------------------------------
        
        $nav  = $this->ipsclass->compiled_templates['skin_global']->start_nav();
        
        $nav .= "<a href='{$this->ipsclass->base_url}act=idx'>{$this->ipsclass->vars['board_name']}</a>";
        
        if ( empty($output_array['OVERRIDE']) )
        {
			if ( isset($output_array['NAV']) AND is_array( $output_array['NAV'] ) )
			{
				foreach ($output_array['NAV'] as $n)
				{
					if ($n)
					{
						$nav .= "<{F_NAV_SEP}>" . $n;
					}
				}
			}
        }
        
        $nav .= $this->ipsclass->compiled_templates['skin_global']->end_nav();
     
        //-----------------------------------------
        // CSS
        //-----------------------------------------
        
        $css = $this->_get_css();
        
		//-----------------------------------------
		// REMOVAL OF THIS WITHOUT PURCHASING COPYRIGHT REMOVAL WILL VIOLATE THE LICENCE YOU AGREED
		// TO WHEN DOWNLOADING THIS PRODUCT. THIS COULD MEAN REMOVAL OF YOUR BOARD AND EVEN
		// CRIMINAL CHARGES
		//-----------------------------------------
        
		$version = ( isset( $this->ipsclass->vars['ipb_display_version'] ) AND $this->ipsclass->vars['ipb_display_version'] != 0 ) ? $this->ipsclass->version : '';
		
        if ($this->ipsclass->vars['ipb_copy_number'] && $this->ipsclass->vars['ips_cp_purchase'])
        {
        	$copyright = "";
        }
        else if ( TRIAL_VERSION )
        {
        	$copyright = "<!-- Copyright Information -->
        				  <div align='center' style='background-color:#FFF;color:#000;font-size:11px;width:auto;'>
        				  	Powered by: Invision Power Board (Free Trial)
        				  	{$version} &copy; ".date("Y")." &nbsp;Invision Power Services, Inc.
        				  	<br /><strong>Get your free trial here!</strong>
        				  </div>
        				  <!-- / Copyright -->";
        }
        else
        {
        	$copyright = "<!-- Copyright Information -->
        				  <div align='center' class='copyright'>
        				  	Powered By IP.Board
        				  	{$version} &copy; ".date("Y")." &nbsp;IPS, Inc.
        				  ";
        				  
        	if ( $this->ipsclass->vars['ipb_reg_show'] and $this->ipsclass->vars['ipb_reg_name'] )
        	{
        		$copyright .= "<div>Licensed to: ". $this->ipsclass->vars['ipb_reg_name']."</div>";
        	}
        	
        	
        	$copyright .= "</div>\n\t\t<!-- / Copyright -->";
        }
        
		//-----------------------------------------
        // Must be called before board_header
		//-----------------------------------------
         
        $this->ipsclass->member['new_msg'] = isset($this->ipsclass->member['new_msg']) ? intval($this->ipsclass->member['new_msg']) : 0;
        $this->ipsclass->member['msg_total'] = isset($this->ipsclass->member['msg_total']) ? $this->ipsclass->member['msg_total'] : 0;
        
        if ( ($this->ipsclass->member['g_max_messages'] > 0) and ($this->ipsclass->member['msg_total'] >= $this->ipsclass->member['g_max_messages']) )
		{
			$msg_data['TEXT'] = $this->ipsclass->lang['msg_full'];
		}
		else
		{
			$msg_data['TEXT'] = sprintf( $this->ipsclass->lang['msg_new'], $this->ipsclass->member['new_msg']);
		}

		//-----------------------------------------
		// Components link
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['components'] ) and count( $this->ipsclass->cache['components'] ) )
    	{
			# As of IPB 2.2, we use float:right which reverses the order of the elements
			$_tmp = array_reverse( $this->ipsclass->cache['components'] );
			
    		foreach( $_tmp as $data )
    		{
    			if ( $data['com_url_title'] AND $data['com_url_uri'] )
    			{
    				$data['com_url_uri']   = str_replace(  '{ipb.base_url}'                   , $this->ipsclass->base_url    , $data['com_url_uri'] );
    				$data['com_url_title'] = preg_replace_callback( '#{ipb.lang\[[\'"](.+?)[\'"]\]}#i', array( &$this, '_return_lang_var' ), $data['com_url_title'] );
    				
    				$components_links .= $this->ipsclass->compiled_templates['skin_global']->global_board_header_component_link( $data['com_url_uri'],$data['com_url_title'], $data );
    			}
    		}
    	}
    	
		//-----------------------------------------
		// Do it
		//-----------------------------------------
		
        $this_header  = $this->ipsclass->compiled_templates['skin_global']->global_board_header( $components_links );
        $this_footer  = $this->ipsclass->compiled_templates['skin_global']->global_board_footer( $this->ipsclass->get_date( time(), 'SHORT', 1 ) );
        
        //-----------------------------------------
        // Show rules link?
        //-----------------------------------------
        
        if ( $this->ipsclass->vars['gl_show'] and $this->ipsclass->vars['gl_title'] )
        {
        	if ( $this->ipsclass->vars['gl_link'] == "" AND $this->ipsclass->vars['gl_title'] )
        	{
        		$this->ipsclass->vars['gl_link'] = $this->ipsclass->base_url."act=boardrules";
        	}
        	
        	$this_header = str_replace( "<!--IBF.RULES-->", $this->ipsclass->compiled_templates['skin_global']->rules_link($this->ipsclass->vars['gl_link'], $this->ipsclass->vars['gl_title']), $this_header );
        }
        
        //-----------------------------------------
        // Build the members bar
		//-----------------------------------------
		
		$output_array['MEMBER_BAR'] = $this->ipsclass->compiled_templates['skin_global']->member_bar($msg_data);
		
		//-----------------------------------------
		// Board offline?
		//-----------------------------------------
		
 		if ($this->ipsclass->vars['board_offline'] == 1)
 		{
 			$output_array['TITLE'] = $this->ipsclass->lang['warn_offline']." ".$output_array['TITLE'];
 		}
        
        //-----------------------------------------
        // Showing skin jump?
        //-----------------------------------------
        
        if ( $this->ipsclass->vars['allow_skins'] and $this->ipsclass->member['id'] > 0 )
        {
	        $skin_jump_options = $this->_build_skin_list();
	        
	        if( $skin_jump_options )
	        {
        		$skin_jump = $this->ipsclass->compiled_templates['skin_global']->global_skin_chooser( $skin_jump_options );
    		}
    		else
    		{
	    		$skin_jump = "";
    		}
        }
        else
        {
        	$skin_jump = "";
        }
        
        //-----------------------------------------
        // Showing skin jump?
        //-----------------------------------------
        
        if ( $this->ipsclass->member['id'] > 0 )
        {
        	$lang_jump = $this->ipsclass->compiled_templates['skin_global']->global_lang_chooser( $this->_build_language_list() );
        }
        else
        {
        	$lang_jump = "";
        }
        
        //-----------------------------------------
        // Show quick stats?
        //-----------------------------------------
        
        $gzip_status = $this->ipsclass->vars['disable_gzip'] == 1 ? $this->ipsclass->lang['gzip_off'] : $this->ipsclass->lang['gzip_on'];
        
        if ( ! $this->ipsclass->server_load  )
        {
        	$this->ipsclass->server_load = '--';
        }
        
        //-----------------------------------------
        // Basics
        //-----------------------------------------
        
        if( strstr( strtolower(PHP_OS), 'win' ) )
		{
			$this->ipsclass->server_load = $this->ipsclass->server_load . '%';
		}
		
        if ( $this->ipsclass->member['id'] and $this->ipsclass->vars['debug_level'] )
        {
        	$quickstats = $this->ipsclass->compiled_templates['skin_global']->global_quick_stats($this->ex_time, $gzip_status, $this->ipsclass->server_load, $this->ipsclass->DB->get_query_cnt() );
        }
        else
        {
        	$quickstats = "";
        }
        
        //-----------------------------------------
        // Add in task image?
        //-----------------------------------------
        
        if ( time() >= $this->ipsclass->cache['systemvars']['task_next_run'] )
        {
        	$this->to_print .= "<!--TASK--><img src='{$this->ipsclass->base_url}act=task' border='0' height='1' width='1' alt='' /><!--ETASK-->";
        }
        
        //-----------------------------------------
		// Parse EXEC commands in wrapper only
		// If we moved this below the block of str_replace
		// we'd get exec commands parsed in HTML templates
		// too... Do we want that? Not for now...
		//-----------------------------------------
		
		if ( stristr( $this->ipsclass->skin['_wrapper'], '<!--exec.' ) )
		{
			$this->_parse_exec_tags();
		}
		
		$output_array['JS'] = (isset($output_array['JS']) AND !is_numeric($output_array['JS'])) ? $output_array['JS'] : '';
		
        $this->ipsclass->skin['_wrapper'] = str_replace( "<% CSS %>"            , $css                                , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% JAVASCRIPT %>"     , $output_array['JS']                 , $this->ipsclass->skin['_wrapper']);
        $this->ipsclass->skin['_wrapper'] = str_replace( "<% TITLE %>"          , $output_array['TITLE']              , $this->ipsclass->skin['_wrapper']);
        $this->ipsclass->skin['_wrapper'] = str_replace( "<% BOARD %>"          , $this->to_print                     , $this->ipsclass->skin['_wrapper']);
        $this->ipsclass->skin['_wrapper'] = str_replace( "<% STATS %>"          , $stats                              , $this->ipsclass->skin['_wrapper']);
        $this->ipsclass->skin['_wrapper'] = str_replace( "<% GENERATOR %>"      , $this->_get_rss_export()            , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% COPYRIGHT %>"      , $copyright                          , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% BOARD HEADER %>"   , $this_header                        , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% BOARD FOOTER %>"   , $this_footer                        , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% NAVIGATION %>"     , $nav                                , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% SKINCHOOSER %>"    , $skin_jump                          , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% LANGCHOOSER %>"    , $lang_jump                          , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% QUICKSTATS %>"     , $quickstats                         , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% LOFIVERSION %>"    , $this->_get_lofi_link()             , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% SYNDICATION %>"    , $this->_get_synd_link()             , $this->ipsclass->skin['_wrapper']);
		$this->ipsclass->skin['_wrapper'] = str_replace( "<% CHARSET %>"        , $this->ipsclass->vars['gb_char_set'], $this->ipsclass->skin['_wrapper']);
		
		if ( empty($output_array['OVERRIDE']) )
		{
      	    $this->ipsclass->skin['_wrapper'] = str_replace( "<% MEMBER BAR %>", $output_array['MEMBER_BAR'], $this->ipsclass->skin['_wrapper']);
        }
        else
        {
      	    $this->ipsclass->skin['_wrapper'] = str_replace( "<% MEMBER BAR %>", $this->ipsclass->compiled_templates['skin_global']->member_bar_disabled(), $this->ipsclass->skin['_wrapper']);
      	}
      	
      	//-----------------------------------------
		// Do we have a PM show?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->member['show_popup']) AND $this->ipsclass->member['show_popup'] AND ! $this->ipsclass->member['members_disable_pm'] )
		{
			$this->ipsclass->DB->simple_construct( array( 'update' => 'members', 'set' => 'show_popup=0', 'where' => 'id='.$this->ipsclass->member['id'] ) );
			$this->ipsclass->DB->simple_shutdown_exec();
			
			if ( $this->ipsclass->input['act'] != 'Msg' )
			{
				$this->ipsclass->skin['_wrapper'] = str_replace( '<!--IBF.NEWPMBOX-->', $this->ipsclass->get_new_pm_notification(), $this->ipsclass->skin['_wrapper'] );
			}
		}
      	
      	//-----------------------------------------
      	// Get the macros and replace them
      	//-----------------------------------------
      	
      	if ( is_array( $this->ipsclass->skin['_macros'] ) )
      	{
			foreach( $this->ipsclass->skin['_macros'] as $row )
			{
				if ( $row['macro_value'] != "" )
				{
					$this->ipsclass->skin['_wrapper'] = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $this->ipsclass->skin['_wrapper'] );
				}
			}
		}
		
		$this->ipsclass->skin['_wrapper'] = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $this->ipsclass->skin['_wrapper'] );
		$this->ipsclass->skin['_wrapper'] = str_replace( "<#EMO_DIR#>", $this->ipsclass->skin['_emodir']  , $this->ipsclass->skin['_wrapper'] );
		
		//-----------------------------------------
		// Images on another server? uncomment and alter below
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['ipb_img_url'] )
		{
			$this->ipsclass->skin['_wrapper'] = preg_replace( "#img\s+?src=([\"'])style_(images|avatars|emoticons)(.+?)[\"'](.+?)?".">#is", "img src=\\1".$this->ipsclass->vars['ipb_img_url']."style_\\2\\3\\1\\4>", $this->ipsclass->skin['_wrapper'] );
		}
		
		//-----------------------------------------
		// Stop one from removing cookie protection
		//-----------------------------------------
		
		$this->ipsclass->skin['_wrapper'] = preg_replace( "#htmldocument\.prototype#is", "HTMLDocument_prototype", $this->ipsclass->skin['_wrapper'] );
		
		$this->_finish();
		
        print $this->ipsclass->skin['_wrapper'];
		
		//-----------------------------------------
		// Clean up...
		//-----------------------------------------
		
		unset( $this->ipsclass->skin['_wrapper'], $output_array, $this->to_print );
		
		//-----------------------------------------
		// Memory usage
		//-----------------------------------------

		if ( IPS_MEMORY_DEBUG_MODE AND defined( 'IPB_MEMORY_START' ) )
		{
			if ( is_array( $this->ipsclass->_memory_debug ) )
			{
				$memory .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>MEMORY USAGE</div><div class='row1' style='padding:6px'>\n";
				$memory .= "<table cellpadding='4' cellspacing='0' border='0' width='100%'>\n";
				$_c      = 0;
				
				foreach( $this->ipsclass->_memory_debug as $usage )
				{
					$_col = ( $_c % 2 ) ? '#eee' : '#ddd';
					$_c++;
					
					if ( $usage[1] > 500 * 1024 )
					{
						$_col .= ";color:#D00000";
					}
					else if ( $usage[1] < 10 * 1024 )
					{
						$_col .= ";color:darkgreen";
					}
					else if ( $usage[1] < 100 * 1024 )
					{
						$_col .= ";color:darkorange";
					}
					
					$memory .= "<tr><td width='60%' style='background-color:{$_col}' align='left'>{$usage[0]}</td><td style='background-color:{$_col}' align='left'><strong>".$this->ipsclass->size_format( $usage[1] )."</strong></td></tr>";
				}
				
				$memory .= "</table></div></div>";
			}
			
			$end       = memory_get_usage();
			$peak_end  = function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : memory_get_usage();
			$_used     = $end - IPB_MEMORY_START;
			$peak_used = $peak_end - IPB_MEMORY_START;
			
			print $memory;
			print "Total Memory Used: " . $this->ipsclass->size_format( $_used ) . " (Peak:".$this->ipsclass->size_format( $peak_used ).")";
		}
		
        exit;
    }
    
    /*-------------------------------------------------------------------------*/
    //
    // print the headers
    //
    /*-------------------------------------------------------------------------*/
        
    function do_headers()
    {
    	if ( $this->ipsclass->vars['print_headers'] )
    	{
    		$this->ipsclass->vars['gb_char_set'] = $this->ipsclass->vars['gb_char_set'] ? $this->ipsclass->vars['gb_char_set'] : 'iso-8859-1';
    		
			header("HTTP/1.0 200 OK");
			header("HTTP/1.1 200 OK");
			header( "Content-type: text/html;charset={$this->ipsclass->vars['gb_char_set']}" );
			
			if ( $this->ipsclass->vars['nocache'] )
			{
				header("Cache-Control: no-cache, must-revalidate, max-age=0");
				//header("Expires:" . gmdate("D, d M Y H:i:s") . " GMT");
				header("Expires: 0");
				header("Pragma: no-cache");
			}
        }
    }
    
    /*-------------------------------------------------------------------------*/
    //
    // print a pure redirect screen
    //
    /*-------------------------------------------------------------------------*/
    
    function redirect_screen($text="", $url="", $override=0)
    {
    	//-----------------------------------------
    	// Make sure global skin is loaded
    	//-----------------------------------------
    	
    	if ( !isset($this->ipsclass->compiled_templates['skin_global']) OR !is_object($this->ipsclass->compiled_templates['skin_global']) )
		{
			$this->ipsclass->load_template('skin_global');
		}
    	
    	if ( isset($this->ipsclass->input['debug']) AND $this->ipsclass->input['debug'] )
        {
        	flush();
        	exit();
        }
        
        //-----------------------------------------
        // $ibforums not initialized yet?
        //-----------------------------------------
        
        if ( $override != 1 )
        {
			if ( $this->ipsclass->base_url )
			{
				$url = $this->ipsclass->base_url.$url;
			}
			else
			{
				$url = "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?".$url;
			}
    	}
    	
    	//-----------------------------------------
    	// Feck off first?
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->vars['ipb_remove_redirect_pages'] == 1 )
    	{
    		$this->ipsclass->boink_it( $url );
    	}
    	
    	$this->ipsclass->lang['stand_by'] = stripslashes($this->ipsclass->lang['stand_by']);
    	
    	//-----------------------------------------
        // CSS
        //-----------------------------------------
        
        $css = $this->_get_css();
        
        //-----------------------------------------
        // Fix up URLs
        //-----------------------------------------
        
        //$url = preg_replace( "#&(?!amp;)#", "&amp;" , $url );
        //$url = preg_replace( '/#(?!\d)/'  , '&#035;', $url );
        
        //-----------------------------------------
        // Get template
        //-----------------------------------------
        
    	$html = $this->ipsclass->compiled_templates['skin_global']->Redirect( ucfirst($text), $url, $css);
    	
    	//-----------------------------------------
    	// Get and parse macros
    	//-----------------------------------------
    	
    	$this->_unpack_macros();
		
		foreach( $this->ipsclass->skin['_macros'] as $row )
      	{
			if ($row['macro_value'] != "")
			{
				$html = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $html );
			}
		}
		
		$html = str_replace( "<% CHARSET %>" , $this->ipsclass->vars['gb_char_set'], $html );
		$html = str_replace( "<#IMG_DIR#>"   , $this->ipsclass->skin['_imagedir']  , $html );
		
		$this->_finish();
        
    	echo ($html);
    	exit;
    }
    
    /*-------------------------------------------------------------------------*/
    //
    // print a minimalist screen suitable for small pop up windows
    //
    /*-------------------------------------------------------------------------*/
    
    function pop_up_window($title = 'Invision Power Board', $text = "" )
    {
    	$this->_check_debug();
    	
    	//-----------------------------------------
        // CSS
        //-----------------------------------------
        
        $css = $this->_get_css();
		
		//-----------------------------------------
        // Get template
        //-----------------------------------------
        
    	$html = $this->ipsclass->compiled_templates['skin_global']->pop_up_window($title, $css, $text);

    	//-----------------------------------------
    	// Get and parse macros
    	//-----------------------------------------
    	
    	$this->_unpack_macros();
		
		foreach( $this->ipsclass->skin['_macros'] as $row )
      	{
			if ( $row['macro_value'] != "" )
			{
				$html = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $html );
			}
		}
		 
		$html = str_replace( "<% CHARSET %>" , $this->ipsclass->vars['gb_char_set'], $html );
		$html = str_replace( "<#IMG_DIR#>"   , $this->ipsclass->skin['_imagedir']  , $html );
		$html = str_replace( "<#EMO_DIR#>"   , $this->ipsclass->skin['_emodir']    , $html );
    	$html = str_replace( '<{__body_extra__}>', '', $html );

    	//-----------------------------------------
		// Images on another server? uncomment and alter below
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['ipb_img_url'] )
		{
			$html = preg_replace( "#img\s+?src=[\"']style_(images|avatars|emoticons)(.+?)[\"'](.+?)?".">#is", "img src=\"".$this->ipsclass->vars['ipb_img_url']."style_\\1\\2\"\\3>", $html );
		}
		
		//-----------------------------------------
		// Stop one from removing cookie protection
		//-----------------------------------------
		
		$html = preg_replace( "#htmldocument\.prototype#is", "HTMLDocument_prototype", $html );
		
    	$this->_finish();
        
    	echo ($html);
    	exit;
    } 
    
    /*-------------------------------------------------------------------------*/
	// Parse EXEC tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* Parses exec tags in the wrapper
	* Nicked from IP.Dynamic.
	*/
	function _parse_exec_tags()
	{
		preg_match_all( "#<\!--exec\.(file|url)=(?:[\"'])?(.+?)(?:[\"'])?-->#is", $this->ipsclass->skin['_wrapper'], $match );
		
		for ($i=0; $i < count($match[0]); $i++)
		{
			$method = strtolower(trim( $match[1][$i] ));
			$uri    = trim( $match[2][$i] );
			
			//-----------------------------------------
			// PARSE: URL
			//-----------------------------------------
			
			if ( $method == 'url' AND IPB_ACP_ALLOW_EXEC_URL )
			{
				//-----------------------------------------
				// Buffer...
				//-----------------------------------------
				
				@ob_start();
				include( $uri );
				$data = @ob_get_contents();
				@ob_end_clean();
			}
			//-----------------------------------------
			// PARSE: LOCAL FILE
			//-----------------------------------------
			else
			{
				if ( file_exists( $uri ) AND ! preg_match( "#http(s)?://#si", $uri ) )
				{
					//-----------------------------------------
					// Buffer...
					//-----------------------------------------
					
					@ob_start();
					include( $uri );
					$data = @ob_get_contents();
					@ob_end_clean();
				}
			}
			
			$this->ipsclass->skin['_wrapper'] = str_replace( $match[0][$i], "<!--included content-->\n".$data."\n<!--/ included content-->", $this->ipsclass->skin['_wrapper'] );
			unset( $data );
		}
	}
	
	
    /*-------------------------------------------------------------------------*/
    // Show Syndication Links
    /*-------------------------------------------------------------------------*/
    
    function _get_synd_link()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$content = "";
    	
    	//-----------------------------------------
    	// Got any?
    	//-----------------------------------------
    	
    	if ( ( ! is_array( $this->ipsclass->cache['rss_export'] ) OR ! count( $this->ipsclass->cache['rss_export'] ) ) AND ( ! is_array( $this->ipsclass->cache['rss_calendar'] ) OR ! count( $this->ipsclass->cache['rss_calendar'] ) ))
    	{
    		return;
    	}
    	
    	//-----------------------------------------
    	// Build
    	//-----------------------------------------
    	
    	if ( is_array( $this->ipsclass->cache['rss_export'] ) and count( $this->ipsclass->cache['rss_export'] ) )
    	{
			foreach( $this->ipsclass->cache['rss_export'] as $data )
			{
				$data['title'] = str_replace( '"', '\"', $data['title'] );
				
				$content .= $this->ipsclass->compiled_templates['skin_global']->global_footer_synd_link( $data ) . "\n";
			}
    	}
    	
    	//-----------------------------------------
    	// Build
    	//-----------------------------------------
    	
    	if ( is_array( $this->ipsclass->cache['rss_calendar'] ) and count( $this->ipsclass->cache['rss_calendar'] ) )
    	{
			foreach( $this->ipsclass->cache['rss_calendar'] as $data )
			{
				$data['title'] = $this->ipsclass->lang['rss_calendar'].' '.$data['title'];
				$content      .= $this->ipsclass->compiled_templates['skin_global']->global_footer_synd_link( $data ) . "\n";
			}
    	}
    	
    	//-----------------------------------------
    	// Clean up content
    	//-----------------------------------------
    	
    	$content = preg_replace( "#,(\s+)?$#s", "", $content );
    	
    	//-----------------------------------------
    	// Return
    	//-----------------------------------------
    	
    	return $this->ipsclass->compiled_templates['skin_global']->global_footer_synd_wrapper( $content );
    }
    
    /*-------------------------------------------------------------------------*/
    // Show RSS export links
    /*-------------------------------------------------------------------------*/
    
    function _get_rss_export()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$content = "";
    	
    	//-----------------------------------------
    	// Got any?
    	//-----------------------------------------
    	
    	if ( ( ! is_array( $this->ipsclass->cache['rss_export'] ) OR ! count( $this->ipsclass->cache['rss_export'] ) ) AND ( ! is_array( $this->ipsclass->cache['rss_calendar'] ) OR ! count( $this->ipsclass->cache['rss_calendar'] ) ))
    	{
    		return;
    	}
    	
    	//-----------------------------------------
    	// Build
    	//-----------------------------------------
    	
    	if ( is_array( $this->ipsclass->cache['rss_export'] ) and count( $this->ipsclass->cache['rss_export'] ) )
    	{
			foreach( $this->ipsclass->cache['rss_export'] as $data )
			{
				$data['title'] = str_replace( '"', '&quot;', $data['title'] );
				$content .= $this->ipsclass->compiled_templates['skin_global']->global_rss_link( $data ) . "\n";
			}
    	}
    	
    	//-----------------------------------------
    	// Build
    	//-----------------------------------------
    	
    	if ( is_array( $this->ipsclass->cache['rss_calendar'] ) and count( $this->ipsclass->cache['rss_calendar'] ) )
    	{
			foreach( $this->ipsclass->cache['rss_calendar'] as $data )
			{
				$data['title'] = $this->ipsclass->lang['rss_calendar'] . ' ' . str_replace( '"', '&quot;', $data['title'] );
				$content      .= $this->ipsclass->compiled_templates['skin_global']->global_rss_link( $data ) . "\n";
			}
    	}
    	
    	//-----------------------------------------
    	// Return
    	//-----------------------------------------
    	
    	return $content;
    }
    
    /*-------------------------------------------------------------------------*/
    // Show lo-fi link
    /*-------------------------------------------------------------------------*/
    
    function _get_lofi_link()
    {
    	$link = "";
    	$char = '/';
    	
    	if ( substr(PHP_OS, 0, 3) == 'WIN' OR strstr( php_sapi_name(), 'cgi') OR php_sapi_name() == 'apache2filter' )
		{
			$char = '?';
		}
		
    	if ( $this->ipsclass->input['act'] == 'st' )
    	{
    		$link = $char.'t'.$this->ipsclass->input['t'].'.html';
    	}
    	else if ( $this->ipsclass->input['act'] == 'sf' )
    	{
    		$link = $char.'f'.$this->ipsclass->input['f'].'.html';
    	}
    	
    	return $link;
    }
    
    /*-------------------------------------------------------------------------*/
    // Build Languages List
    /*-------------------------------------------------------------------------*/
    
    function _build_language_list()
    {
    	$lang_list = "";
    	
    	//-----------------------------------------
		// Roots
		//-----------------------------------------
		
		foreach( $this->ipsclass->cache['languages'] as $data )
		{
			if ( $this->ipsclass->lang_id == $data['ldir'] )
			{
				$selected = ' selected="selected"';
			}
			else
			{
				$selected = "";
			}
			
			$lang_list .= "\n<option value='{$data['ldir']}'{$selected}>{$data['lname']}</option>";
		}
		
		return $lang_list;
    }

	/*-------------------------------------------------------------------------*/
    // Build Skin List
    /*-------------------------------------------------------------------------*/
    
    function _build_skin_list()
    {
    	$skin_list = "";
    	
    	//-----------------------------------------
		// Roots
		//-----------------------------------------
		
		foreach( $this->ipsclass->cache['skin_id_cache'] as $id => $data )
		{
			$skin_sets[ $data['set_parent'] ]['_children'][] = $id;
			
			if ( $data['set_parent'] < 1 and $id > 1 )
			{
				if ( $data['set_hidden'] and ! $this->ipsclass->member['g_access_cp'] )
				{
					continue;
				}
				
				$star = $data['set_hidden'] ? ' *' : '';
				
				if ( isset($this->ipsclass->skin['_setid']) AND $this->ipsclass->skin['_setid'] == $id )
				{
					$selected = ' selected="selected"';
				}
				else
				{
					$selected = "";
				}
				
				$skin_list .= "\n<option value='$id'{$selected}>{$data['set_name']}{$star}</option><!--CHILDREN:{$id}-->";
			}
		}
		
		//-----------------------------------------
		// Kids...
		//-----------------------------------------
		
		foreach( $skin_sets as $id => $data )
		{	
			if ( is_array( $data['_children'] ) and count( $data['_children'] ) > 0 )
			{
				$html = "";
				
				foreach( $data['_children'] as $cid )
				{
					if ( $this->ipsclass->cache['skin_id_cache'][ $cid ]['set_hidden'] and ! $this->ipsclass->member['g_access_cp'] )
					{
						continue;
					}
					
					$star = $this->ipsclass->cache['skin_id_cache'][ $cid ]['set_hidden'] ? ' *' : '';
					
					if ( isset($this->ipsclass->skin['_setid']) AND $this->ipsclass->skin['_setid'] == $cid )
					{
						$selected = ' selected="selected"';
					}
					else
					{
						$selected = "";
					}
				
					$html .= "\n<option value='$cid'{$selected}>---- {$this->ipsclass->cache['skin_id_cache'][ $cid ]['set_name']}{$star}</option>";
				}
				
				$skin_list = str_replace( "<!--CHILDREN:{$id}-->", $html, $skin_list );
			}
		}
		return $skin_list;
    }

    /*-------------------------------------------------------------------------*/
    // unpack_macros
    /*-------------------------------------------------------------------------*/
    
    function _unpack_macros()
    {
    	if ( ! is_array( $this->ipsclass->skin['_macros'] ) OR ! count( $this->ipsclass->skin['_macros'] ) )
    	{
    		$this->ipsclass->skin['_macros'] = unserialize( stripslashes($this->ipsclass->skin['_macro']) );
    	}
    	
    	if ( LEGACY_MODE )
    	{
    		$this->macros =& $this->ipsclass->skin['_macros'];
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // show_debug
    /*-------------------------------------------------------------------------*/
    
    function _show_debug()
    {
    	$input   = "";
        $queries = "";
        $sload   = "";
        $stats   = "";
        
       //-----------------------------------------
       // Form & Get & Skin
       //-----------------------------------------
       
       if ($this->ipsclass->vars['debug_level'] >= 2)
       {
       		$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>FORM and GET Input</div><div class='row1' style='padding:6px'>\n";
        
			while( list($k, $v) = each($this->ipsclass->input) )
			{
				if ( in_array( strtolower( $k ), array( 'pass', 'password' ) ) )
				{
					$v = '*******';
				}
				
				$stats .= "<strong>$k</strong> = $v<br />\n";
			}
			
			$stats .= "</div>\n</div>";
			
			$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>SKIN & TASK Info</div><div class='row1' style='padding:6px'>\n";
        
			while( list($k, $v) = each($this->ipsclass->skin) )
			{
				if( is_array($v) )
				{
					continue;
				}
				
				if ( strlen($v) > 120 )
				{
					$v = substr( $v, 0, 120 ). '...';
				}
				
				$stats .= "<strong>$k</strong> = ".$this->ipsclass->txt_htmlspecialchars($v)."<br />\n";
			}
			
			//-----------------------------------------
			// Stop E_ALL moaning...
			//-----------------------------------------

			$this->ipsclass->cache['systemvars']['task_next_run'] = isset( $this->ipsclass->cache['systemvars']['task_next_run'] ) ? $this->ipsclass->cache['systemvars']['task_next_run'] : 0;
			
			$stats .= "<b>Next task</b> = ".$this->ipsclass->get_date( $this->ipsclass->cache['systemvars']['task_next_run'], 'LONG' )."\n<br /><b>Time now</b> = ".$this->ipsclass->get_date( time(), 'LONG' );
			$stats .= "<br /><b>Timestamp Now</b> = ".time();
			
			$stats .= "</div>\n</div>";
			
			$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>Loaded PHP Templates</div><div class='row1' style='padding:6px'>\n";
        
			$stats .= "<strong>".implode(", ",array_keys($this->ipsclass->compiled_templates))."</strong><br />\n";
			$stats .= "<strong>".implode(", ",array_keys($this->ipsclass->loaded_templates))."</strong><br />\n";
			
			$stats .= "</div>\n</div>";
        
        }
        
        //-----------------------------------------
        // SQL
        //-----------------------------------------
        
        if ($this->ipsclass->vars['debug_level'] >= 3)
        {
           	$stats .= "<br />\n<div class='tableborder' style='overflow:auto'>\n<div class='subtitle'>Queries Used</div><div class='row1' style='padding:6px'>";
       					
        	foreach($this->ipsclass->DB->obj['cached_queries'] as $q)
        	{
        		$q = htmlspecialchars($q);
        		$q = preg_replace( "/^SELECT/i" , "<span class='red'>SELECT</span>"   , $q );
        		$q = preg_replace( "/^UPDATE/i" , "<span class='blue'>UPDATE</span>"  , $q );
        		$q = preg_replace( "/^DELETE/i" , "<span class='orange'>DELETE</span>", $q );
        		$q = preg_replace( "/^INSERT/i" , "<span class='green'>INSERT</span>" , $q );
        		$q = str_replace( "LEFT JOIN"   , "<span class='red'>LEFT JOIN</span>" , $q );
        		
        		$q = preg_replace( "/(".$this->ipsclass->vars['sql_tbl_prefix'].")(\S+?)([\s\.,]|$)/", "<span class='purple'>\\1\\2</span>\\3", $q );
        		
        		$stats .= "$q<hr />\n";
        	}
        	
        	if ( count( $this->ipsclass->DB->obj['shutdown_queries'] ) )
        	{
				foreach($this->ipsclass->DB->obj['shutdown_queries'] as $q)
				{
					$q = htmlspecialchars($q);
					$q = preg_replace( "/^SELECT/i" , "<span class='red'>SELECT</span>"   , $q );
					$q = preg_replace( "/^UPDATE/i" , "<span class='blue'>UPDATE</span>"  , $q );
					$q = preg_replace( "/^DELETE/i" , "<span class='orange'>DELETE</span>", $q );
					$q = preg_replace( "/^INSERT/i" , "<span class='green'>INSERT</span>" , $q );
					$q = str_replace( "LEFT JOIN"   , "<span class='red'>LEFT JOIN</span>" , $q );
					
					$q = preg_replace( "/(".$this->ipsclass->vars['sql_tbl_prefix'].")(\S+?)([\s\.,]|$)/", "<span class='purple'>\\1\\2</span>\\3", $q );
					
					$stats .= "<div style='background:#DEDEDE'><b>SHUTDOWN:</b> $q</div><hr />\n";
				}
        	}
        	
        	$stats .= "</div>\n</div>";
        }
        
        if ( $stats )
        {
			$collapsed_ids = ','.$this->ipsclass->my_getcookie('collapseprefs').',';
			
			$show['div_fo'] = '';
			$show['div_fc'] = 'none';
				
			if ( strstr( $collapsed_ids, ',debug,' ) )
			{
				$show['div_fo'] = 'none';
				$show['div_fc'] = '';
			}
			
			$stats = "<div align='center' style='display:{$show['div_fc']}' id='fc_debug'>
					   <div class='row2' style='padding:8px;vertical-align:middle'><a href='javascript:togglecategory(\"debug\", 0);'>Show Debug Information</a></div>
					  </div>
					  
					  <div align='center' style='display:{$show['div_fo']}' id='fo_debug'>
					   <div class='row2' style='padding:8px;vertical-align:middle'><a href='javascript:togglecategory(\"debug\", 1);'>Hide Debug Information</a></div>
					   <br />
					   <div class='tableborder' align='left'>
						<div class='maintitle'>Debug Information</div>
						 <div style='padding:5px;background:#8394B2;'>$stats</div>
					   </div>
					  </div>";
        }
        
        return $stats;
    }
    
    /*-------------------------------------------------------------------------*/
    // check_debug
    /*-------------------------------------------------------------------------*/
    
    function _check_debug()
    {
    	if ($this->ipsclass->DB->obj['debug'])
        {
        	flush();
        	print "<html><head><title>SQL Debugger</title><body bgcolor='white'><style type='text/css'> TABLE, TD, TR, BODY { font-family: verdana,arial, sans-serif;color:black;font-size:11px }</style>";
        	print "<h1 align='center'>SQL Total Time: {$this->ipsclass->DB->sql_time} for {$this->ipsclass->DB->query_count} queries</h1><br />".$this->ipsclass->DB->debug_html;
        	print "<br /><div align='center'><strong>Total SQL Time: {$this->ipsclass->DB->sql_time}</div></body></html>";
        	exit();
        }
    }
    
    
    //*-------------------------------------------------------------------------*/
    // get_css
    /*-------------------------------------------------------------------------*/
    
    function _get_css()
    {
    	if ( $this->ipsclass->skin['_usecsscache'] and @file_exists( CACHE_PATH.'style_images/css_'. $this->ipsclass->skin['_csscacheid'] .'.css' ) )
        {
        	$css = $this->ipsclass->compiled_templates['skin_global']->css_external($this->ipsclass->skin['_csscacheid']);
        }
        else
        {
        	$css = $this->ipsclass->compiled_templates['skin_global']->css_inline( str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $this->ipsclass->skin['_css'] ) );
        }
        
        return $css;
    }
    
    /*-------------------------------------------------------------------------*/
    // finish
    /*-------------------------------------------------------------------------*/
    
    function _finish()
    {
    	//-----------------------------------------
		// Do shutdown
		//-----------------------------------------
		
		if ( ! USE_SHUTDOWN )
        {
        	$this->ipsclass->my_deconstructor();
        	$this->ipsclass->DB->close_db();
        }
        
		//-----------------------------------------
		// Start GZIP compression
        //-----------------------------------------
        
        if ($this->ipsclass->vars['disable_gzip'] != 1 )
        {
	        $buffer = "";
	        
	        if ( count( ob_list_handlers() ) )
	        {
        		$buffer = ob_get_contents();
        		ob_end_clean();
    		}
    		
        	@ob_start('ob_gzhandler');
        	print $buffer;
        }
        
        //-----------------------------------------
        // Print, plop and part
        //-----------------------------------------
        
        $this->do_headers();
    }
	
	/*-------------------------------------------------------------------------*/
    // Bleh.
    /*-------------------------------------------------------------------------*/

	function _return_lang_var( $matches=array() )
	{
		return $this->ipsclass->lang[ $matches[1] ];
	}
        
} // END class
    

?>