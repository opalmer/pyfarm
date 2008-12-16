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
|   > $Date: 2007-03-29 18:12:27 -0400 (Thu, 29 Mar 2007) $
|   > $Revision: 914 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin HTML stuff library
|   > Script written by Matt Mecham
|   > Date started: 1st march 2002
|
|   > DBA Checked: Tue 25th May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_skin
{
	# Global
	var $ipsclass;
	
	var $base_url;
	var $img_url;
	var $has_title;
	var $td_widths = array();
	var $td_header = array();
	var $td_colspan;
	var $valid_hostnames = array();
	
	function init_admin_skin()
	{
		$this->base_url = $this->ipsclass->base_url;
		$this->img_url  = $this->ipsclass->skin_acp_url;
	}
	
	/*-------------------------------------------------------------------------*/
	// Print the global ACP header
	/*-------------------------------------------------------------------------*/
	
	function print_top($title="",$desc="")
	{
		return $this->ipsclass->skin_acp_global->global_header( $title, $desc );
	}
	
	/*-------------------------------------------------------------------------*/
	// Print the nav wrapper
	/*-------------------------------------------------------------------------*/
	
	function wrap_nav($links)
	{
		return $this->ipsclass->skin_acp_global->global_wrap_nav( $links );
	}
	
	/*-------------------------------------------------------------------------*/
	// Print the global ACP footer
	/*-------------------------------------------------------------------------*/
	
	function print_foot()
	{
		return $this->ipsclass->skin_acp_global->global_footer( date("Y") );
	}
	
	/*-------------------------------------------------------------------------*/
	// Print the global ACP menu header
	/*-------------------------------------------------------------------------*/
	
	function menu_top()
	{
		return $this->ipsclass->skin_acp_global->global_menu_header();
	}
	
	/*-------------------------------------------------------------------------*/
	// Print the global ACP menu footer
	/*-------------------------------------------------------------------------*/
	
	function menu_foot()
	{
		return $this->ipsclass->skin_acp_global->global_menu_footer();
	}
	
	/*-------------------------------------------------------------------------*/
	// Print the global ACP frameset
	/*-------------------------------------------------------------------------*/
	
	function frame_set()
	{
		//-----------------------------------------
		// Carry on
		//-----------------------------------------
		
		$extra_query = 'act=index';
		
		if ( $this->ipsclass->input['act'] != 'idx' )
		{
			$extra_query = str_replace( '&amp;', '&', $this->ipsclass->parse_clean_value($this->ipsclass->my_getenv('QUERY_STRING')) );
			$extra_query = str_replace( "{$this->ipsclass->vars['board_url']}"           , "" , $extra_query );
			$extra_query = preg_replace( "!/?admin\.{$this->ipsclass->vars['php_ext']}!i", "" , $extra_query );
			$extra_query = preg_replace( "!^\?!"                                         , "" , $extra_query );
			$extra_query = str_replace( "printframes=1"                                  , "" , $extra_query );
			$extra_query = preg_replace( "!adsess=(\w){32}!"                             , "" , $extra_query );
			$extra_query = preg_replace( "!s=(\w){32}!"                                  , "" , $extra_query );
		}
		
		return $this->ipsclass->skin_acp_global->global_frame_set( $extra_query );
	}

	/*-------------------------------------------------------------------------*/
	// JS Make button
	/*-------------------------------------------------------------------------*/
	
	function js_make_button($text="", $url="", $css='realbutton', $title="")
	{
		return "<input type='button' class='{$css}' value='{$text}' onclick='self.location.href=\"{$url}\"' title='$title' />";
	}
	
	/*-------------------------------------------------------------------------*/
	// JS Help Link
	/*-------------------------------------------------------------------------*/
	
	function js_help_link($help="", $text="Quick Help")
	{
		return "( <a href='#' onClick=\"window.open('{$this->ipsclass->base_url}&act=quickhelp&id=$help','Help','width=250,height=400,resizable=yes,scrollbars=yes'); return false;\">$text</a> )";
	}
	
	/*-------------------------------------------------------------------------*/
	// JS Template tools
	/*-------------------------------------------------------------------------*/
	
	function js_template_tools()
	{
		return "
				<script language='javascript'>
      				var template_bit_ids = '<!--IPB.TEMPLATE_BIT_IDS-->';
				</script>
				";
	}
	
	/*-------------------------------------------------------------------------*/
	// JS Make page jump
	/*-------------------------------------------------------------------------*/
	
	function make_page_jump($tp="", $pp="", $ub="" )
	{
		return "<a href='#' title=\"Jump to a page...\" onclick=\"multi_page_jump('$ub',$tp,$pp);\">Pages:</a>";
	}
	
	/*-------------------------------------------------------------------------*/
	// Form: Start Form
	/*-------------------------------------------------------------------------*/
	
	function start_form($hiddens="", $name='theAdminForm', $js="", $id="")
	{
		if ( ! $id )
		{
			$id = $name;
		}

		$form = "<form action='{$this->ipsclass->base_url}' method='post' name='$name' $js id='$id'>";
		
		if (is_array($hiddens))
		{
			foreach ($hiddens as $v)
			{
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}' />";
			}
		}
		
		//-----------------------------------------
		// Add in auth key
		//-----------------------------------------
		
		$form .= "\n<input type='hidden' name='_admin_auth_key' value='".$this->ipsclass->_admin_auth_key."' />";
		
		return $form;
	}
	
	/*-------------------------------------------------------------------------*/
	// Form: Hidden
	/*-------------------------------------------------------------------------*/
	
	function form_hidden($hiddens="")
	{
		if (is_array($hiddens))
		{
			foreach ($hiddens as $v)
			{
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}'>";
			}
		}
		
		return $form;
	}
	
	
	//-----------------------------------------
	
	function end_form($text = "", $js = "", $extra = "")
	{
		$html    = "";
		$colspan = "";
		
		if ($text != "")
		{
			if ($this->td_colspan > 0)
			{
				$colspan = " colspan='".$this->td_colspan."' ";
			}
			
			$html .= "<tr><td align='center' class='tablesubheader'".$colspan."><input type='submit' value='$text'".$js." class='realbutton' accesskey='s'>{$extra}</td></tr>\n";
		}
		
		$html .= "</form>";
		
		return $html;
	}
	
	//-----------------------------------------
	
	function end_form_standalone($text = "", $js = "")
	{
		$html    = "";
		$colspan = "";
		
		if ($text != "")
		{
			$html .= "<div class='tableborder'><div align='center' class='tablesubheader'><input type='submit' value='$text'".$js." class='realbutton' accesskey='s'></div></div>\n";
		}
		
		$html .= "</form>";
		
		return $html;
	}
	
	//-----------------------------------------
	
	function form_upload($name="FILE_UPLOAD", $js="")
	{
		if ($js != "")
		{
			$js = ' '.$js.' ';
		}
	
		return "<input class='textinput' type='file' $js size='30' name='$name'>";
	}
	
	//-----------------------------------------
	
	function form_input($name, $value="", $type='text', $js="", $size="30")
	{
		if ($js != "")
		{
			$js = ' '.$js.' ';
		}
	
		return "<input type='$type' name='$name' value=\"$value\" size='$size'".$js." class='textinput'>";
	}
	
	function form_simple_input($name, $value="", $size='5')
	{
		return "<input type='text' name='$name' value='$value' size='$size' class='textinput'>";
	}
	
	//-----------------------------------------
	
	function form_textarea($name, $value="", $cols='60', $rows='5', $wrap='soft', $id="", $style="", $js="") {
	
		if ( $id )
		{
			$id = "id='$id'";
		}
		else
		{
			$id = "id='{$name}'";
		}
		
		if ( $style )
		{
			$style = "style='$style'";
		}
		
		return "<textarea name='$name' cols='$cols' rows='$rows' wrap='$wrap' $id $style $js class='multitext'>$value</textarea>";
		
	}
	
	//-----------------------------------------
	
	function form_dropdown($name, $list=array(), $default_val="", $js="", $css="") {
	
		if ($js != "")
		{
			$js = ' '.$js.' ';
		}
		
		if ($css != "")
		{
			$css = ' class="'.$css.'" ';
		}
	
		$html = "<select name='$name'".$js." $css class='dropdown'>\n";
		
		foreach ($list as $v)
		{
			$selected = "";
			
			if ( ($default_val != "") and ($v[0] == $default_val) )
			{
				$selected = ' selected';
			}
			
			$html .= "<option value='".$v[0]."'".$selected.">".$v[1]."</option>\n";
		}
		
		$html .= "</select>\n\n";
		
		return $html;
	
	
	}
	
	//-----------------------------------------
	
	function form_multiselect($name, $list=array(), $default=array(), $size=5, $js="") {
	
		if ($js != "")
		{
			$js = ' '.$js.' ';
		}
	
		//$html = "<select name='$name".'[]'."'".$js." id='dropdown' multiple='multiple' size='$size'>\n";
		$html = "<select name='$name"."'".$js." class='dropdown' multiple='multiple' size='$size'>\n";
		foreach ($list as $v)
		{
		
			$selected = "";
			
			if ( count($default) > 0 )
			{
				if ( in_array( $v[0], $default ) )
				{
					$selected = ' selected="selected"';
				}
			}
			
			$html .= "<option value='".$v[0]."'".$selected.">".$v[1]."</option>\n";
		}
		
		$html .= "</select>\n\n";
		
		return $html;
	
	
	}
	
	//-----------------------------------------
	
	function form_yes_no( $name, $default_val="", $js=array() ) {
	
		$y_js = "";
		$n_js = "";
		
		if ( isset($js['yes']) AND $js['yes'] != "" )
		{
			$y_js = $js['yes'];
		}
		
		if ( isset($js['no']) ANd $js['no'] != "" )
		{
			$n_js = $js['no'];
		}
	
		$yes = "Yes &nbsp; <input type='radio' name='$name' value='1' $y_js id='green'>";
		$no  = "<input type='radio' name='$name' value='0' $n_js id='red'> &nbsp; No";
		
		
		
		if ($default_val == 1)
		{
			
			$yes = "Yes &nbsp; <input type='radio' name='$name' value='1'$y_js checked id='green'>";
		}
		else
		{
			$no  = "<input type='radio' name='$name' value='0' checked $n_js id='red'> &nbsp; No";
		}
		
		
		return $yes.'&nbsp;&nbsp;&nbsp;'.$no;
		
	}
	
	//-----------------------------------------
	
	function form_checkbox( $name, $checked=0, $val=1, $js=array() ) {
		
		$form_js = "";
		
		if( count( $js ) )
		{
			foreach( $js as $javascript )
			{
				$form_js .= $javascript." ";
			}
		}
		if ($checked == 1)
		{
			
			return "<input type='checkbox' name='$name' value='$val' $form_js checked='checked'>";
		}
		else
		{
			return "<input type='checkbox' name='$name' value='$val' $form_js>";
		}
	}
	
	
	
	//-----------------------------------------
	//--------------------------------------------------------------------
	// SCREEN ELEMENTS
	//-----------------------------------------
	//--------------------------------------------------------------------
	
	function add_subtitle($title="",$id="subtitle", $colspan="") {
		
		if ($colspan != "")
		{
			$colspan = " colspan='$colspan' ";
		}
		
		return "\n<tr><td id='$id'".$colspan.">$title</td><tr>\n";
		
	}
	
	//-----------------------------------------
	
	function start_table( $title="", $desc="") {
	
		$html = "";
		
		if ($title != "")
		{
			$this->has_title = 1;
			$html .= "<div class='tableborder'>
						<div class='tableheaderalt'>$title</div>\n";
						
			if ( $desc != "" )
			{
				$html .= "<div class='tablesubheader'>$desc</div>\n";
			}
		}
	
	
	
		$html .= "\n<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>";
		
		
		if (isset($this->td_header[0]))
		{
			// Auto remove two &nbsp; only headers.. 
			
			$this->td_header[1][0] = ( isset($this->td_header[1][0]) AND $this->td_header[1][0] ) ? $this->td_header[1][0] : '';
			$this->td_header[1][1] = ( isset($this->td_header[1][1]) AND $this->td_header[1][1] ) ? $this->td_header[1][1] : '';
			
			if ( $this->td_header[0][0] == '&nbsp;' && $this->td_header[1][0] == '&nbsp;' && ( ! isset( $this->td_header[2][0] ) ) )
			{
				$this->td_header[0][0] = '{none}';
				$this->td_header[1][0] = '{none}';
			}
			
			$tds = "";
			
			foreach ($this->td_header as $td)
			{
				if ($td[1] != "")
				{
					$width = " width='{$td[1]}' ";
				}
				else
				{
					$width = "";
				}
				
				if ($td[0] != '{none}')
				{
					$tds .= "<td class='tablesubheader'".$width."align='center'>{$td[0]}</td>\n";
				}
				
				$this->td_colspan++;
			}
			
			if( $tds )
			{
				$html .= "<tr>\n{$tds}</tr>\n";
			}
		}
		
		return $html;
		
	}
	
	//-----------------------------------------
	
	function add_standalone_row($text = "", $align='center', $class='tablesubheader')
	{
		return "<div class='tableborder'><div align='{$align}' class='{$class}'>{$text}</div></div>\n";
	}
	
	//-----------------------------------------
	
	
	function add_td_row( $array, $css="", $align='middle' ) {
	
		if (is_array($array))
		{
			$html = "<tr>\n";
			
			$count = count($array);
			
			$this->td_colspan = $count;
			
			for ($i = 0; $i < $count ; $i++ )
			{
				$td_col = $i % 2 ? 'tablerow2' : 'tablerow1';
				
				if ($css != "")
				{
					$td_col = $css;
				}
			
				if (is_array($array[$i]))
				{
					$text    = $array[$i][0];
					$colspan = $array[$i][1];
					$td_col  = $array[$i][2] != "" ? $array[$i][2] : $td_col;
					
					$html .= "<td class='$td_col' colspan='$colspan' valign='$align'>".$text."</td>\n";
				}
				else
				{
					if (isset($this->td_header[$i][1]) AND $this->td_header[$i][1] != "")
					{
						$width = " width='{$this->td_header[$i][1]}' ";
					}
					else
					{
						$width = "";
					}
					
					$html .= "<td class='$td_col' $width valign='$align'>".$array[$i]."</td>\n";
				}
			}
			
			$html .= "</tr>\n";
			
			return $html;
		}
		
	}
	
	//-----------------------------------------
	
	function add_td_basic($text="",$align="left",$id="tablerow1", $colspanint=0) {
	
		$html    = "";
		$colspan = "";
		
		if ( $colspanint )
		{
			$this->td_colspan = $colspanint;
		}
		
		if ($text != "")
		{
			if ($this->td_colspan > 0)
			{
				$colspan = " colspan='".$this->td_colspan."' ";
			}
			
			
			$html .= "<tr><td align='$align' class='$id'".$colspan.">$text</td></tr>\n";
		}
		
		return $html;
	
	}
	
	//-----------------------------------------
	
	function add_td_spacer() {
	
		if ($this->td_colspan > 0)
		{
			$colspan = " colspan='".$this->td_colspan."' ";
		}
	
		return "<tr><td".$colspan."><br /></td></tr>";
	
	}
	
	
	
	//-----------------------------------------
	
	function end_table() {
	
		$this->td_header = array();  // Reset TD headers
	
		if ($this->has_title == 1)
		{
			$this->has_title = 0;
			
			return "</table></div><br />\n\n";
		}
		else
		{
			return "</table>\n\n";
		}
		
	}
	
	
	
	
	
	//-----------------------------------------
	
	
	function skin_jump_menu_wrap()
	{
		return "<br /><div align='center' style='width:250px;margin-left:auto;margin-right:auto;'>
			   <div style='padding:3px 0px 3px 0px;border:1px solid #AAA;'>
			   <div class='tablepad' align='center'>".$this->ipsclass->admin->skin_jump_menu()."</div></div></div>";
		
	}
	
	
    /*-------------------------------------------------------------------------*/
    // Build up page span links                
    /*-------------------------------------------------------------------------*/
    
    /**
	* Build up page span links 
	*
	* @param	array	Page data
	* @return	string	Parsed page links HTML
	* @since	2.0
	*/
	function build_pagelinks($data)
	{
		$work = array( 'page_span' => NULL, 'st_dots' => NULL, 'pages' => 0 );
		
		$section = (!isset($data['leave_out']) OR $data['leave_out'] == "") ? 2 : $data['leave_out'];  // Number of pages to show per section( either side of current), IE: 1 ... 4 5 [6] 7 8 ... 10
		
		$use_st  = (!isset($data['USE_ST']) OR $data['USE_ST'] == "") ? 'st' : $data['USE_ST'];

		//-----------------------------------------
		// Get the number of pages
		//-----------------------------------------
		
		if ( $data['TOTAL_POSS'] > 0 )
		{
			$work['pages'] = ceil( $data['TOTAL_POSS'] / $data['PER_PAGE'] );
		}
		
		$work['pages'] = $work['pages'] ? $work['pages'] : 1;
		
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$work['total_page']   = $work['pages'];
		$work['current_page'] = $data['CUR_ST_VAL'] > 0 ? ($data['CUR_ST_VAL'] / $data['PER_PAGE']) + 1 : 1;
		
		//-----------------------------------------
		// Next / Previous page linkie poos
		//-----------------------------------------
		
		$previous_link = "";
		$next_link     = "";
		
		if ( $work['current_page'] > 1 )
		{
			$start = $data['CUR_ST_VAL'] - $data['PER_PAGE'];
			$previous_link = $this->ipsclass->skin_acp_global->pagination_previous_link("{$data['BASE_URL']}&amp;$use_st=$start");
		}
		
		if ( $work['current_page'] < $work['pages'] )
		{
			$start = $data['CUR_ST_VAL'] + $data['PER_PAGE'];
			$next_link = $this->ipsclass->skin_acp_global->pagination_next_link("{$data['BASE_URL']}&amp;$use_st=$start");
		}
		
		//-----------------------------------------
		// Loppy loo
		//-----------------------------------------
		
		if ($work['pages'] > 1)
		{
			$work['first_page'] = $this->ipsclass->skin_acp_global->pagination_make_jump($work['pages']);
			
			for( $i = 0; $i <= $work['pages'] - 1; ++$i )
			{
				$RealNo = $i * $data['PER_PAGE'];
				$PageNo = $i+1;
				
				if ($RealNo == $data['CUR_ST_VAL'])
				{
					$work['page_span'] .=  $this->ipsclass->skin_acp_global->pagination_current_page($PageNo);
				}
				else
				{
					if ($PageNo < ($work['current_page'] - $section))
					{
						$work['st_dots'] = $this->ipsclass->skin_acp_global->pagination_start_dots($data['BASE_URL']);
						continue;
					}
					
					// If the next page is out of our section range, add some dotty dots!
					
					if ($PageNo > ($work['current_page'] + $section))
					{
						$work['end_dots'] = $this->ipsclass->skin_acp_global->pagination_end_dots("{$data['BASE_URL']}&amp;$use_st=".($work['pages']-1) * $data['PER_PAGE']);
						break;
					}
					
					
					$work['page_span'] .= $this->ipsclass->skin_acp_global->pagination_page_link("{$data['BASE_URL']}&amp;$use_st={$RealNo}",$PageNo);
				}
			}
			
			$work['return']    = $this->ipsclass->skin_acp_global->pagination_compile($work['first_page'],$previous_link,$work['st_dots'],$work['page_span'],$work['end_dots'],$next_link,$data['TOTAL_POSS'],$data['PER_PAGE'], $data['BASE_URL']);
		}
		else
		{
			$work['return']    = $data['L_SINGLE'];
		}
	
		return $work['return'];
	}	

}






?>