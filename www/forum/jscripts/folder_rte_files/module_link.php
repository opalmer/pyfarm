<html>
<head>
  <title>Insert Div/Span</title>
	<script type="text/javascript">
	<!--
	
	//-----------------------------------------
    // Attempt to get editor ID
    //-----------------------------------------

    var editor_id         = <?php print '"'.trim( htmlspecialchars( substr( $_REQUEST['editorid'], 0, 30 ) ) ).'";'; ?>
	var this_module       = 'link';
	var this_height       = 400;
	var allow_advanced    = null;
	var current_selection = null;
	
	/*-------------------------------------------------------------------------*/
	// INIT
	/*-------------------------------------------------------------------------*/
	
	function Init()
	{
		var allow_advanced    = parent.IPS_editor[ editor_id ].allow_advanced;
		var current_selection = parent.ipsclass.un_htmlspecialchars( parent.IPS_editor[ editor_id ].get_selection() );

		//-----------------------------------------
		// Set up main close button
		//-----------------------------------------
		
		parent.document.getElementById( editor_id + '_cb-close-window' ).onclick      = do_cancel;
		parent.document.getElementById( editor_id + '_cb-close-window' ).style.cursor = 'pointer';
		
		//-----------------------------------------
		// Resize window
		//-----------------------------------------
		
		parent.document.getElementById( editor_id +  '_htmlblock_'  + this_module + '_menu' ).style.height = this_height + 'px';
		parent.document.getElementById( editor_id + '_iframeblock_' + this_module + '_menu' ).style.height = ( this_height - 10 ) + 'px';
		
		//-----------------------------------------
		// Module specific stuff
		//-----------------------------------------
		
		document.getElementById("f_href").focus();
		
		//-----------------------------------------
		// Set up initial text value
		//-----------------------------------------
		
		document.getElementById("f_text").value = current_selection;
		
		//-----------------------------------------
		// Show advanced options?
		//-----------------------------------------
		
		if ( allow_advanced )
		{
			document.getElementById("advanced-options").style.display = '';
		}
		
		//-----------------------------------------
		// Set up initial text value
		//-----------------------------------------
		
		var link_regex = '';
		var link_text  = '';
		var link_href  = '';

		if ( parent.IPS_editor[ editor_id ].use_bbcode && ! parent.IPS_editor[ editor_id ].is_rte )
		{
			link_regex = /\[url=([^\]]+?)\]([^\[]+?)\[\/url\]/ig;
			
			if ( current_selection.match( link_regex ) )
			{ 
				link_href = current_selection.replace( link_regex, "$1" );
				link_text = current_selection.replace( link_regex, "$2" );
			}
		}
		else
		{
			link_regex = /<a href=['"]([^"']+?)['"]([^>]+?)?>(.+?)<\/a>/ig;
			
			if ( current_selection.match( link_regex ) )
			{ 
				link_href = current_selection.replace( link_regex, "$1" );
				link_text = current_selection.replace( link_regex, "$3" );
			}
		}
		
		if ( link_text )
		{
			current_selection = link_text;
		}
		
		if ( link_href )
		{
			document.getElementById("f_href").value = link_href;
		}
		
		current_selection = current_selection.replace( /<br \/>|<br>|\n|\r/g, "");
		
		document.getElementById("f_text").value = parent.IPS_editor[ editor_id ].strip_empty_html( current_selection );
	}
	
	/*-------------------------------------------------------------------------*/
	// Do cancel
	/*-------------------------------------------------------------------------*/
	
	function do_cancel()
	{
		parent.IPS_editor[ editor_id ].module_remove_control_bar();
	 	return false;
	}
	
	/*-------------------------------------------------------------------------*/
	// Do submit (low tech)
	/*-------------------------------------------------------------------------*/
	
	function do_submit()
	{
		var fields = [ "f_href", "f_text", "f_ad_title", "f_ad_target" ];
					  
		var param = new Object();
		
		//-----------------------------------------
		// Compile data
		//-----------------------------------------
		
		for ( var i in fields )
		{
			param[ fields[i] ] = document.getElementById( fields[i] ).value;
		}
		
		//-----------------------------------------
        // Set up required fields
        //-----------------------------------------
        
		var required = {
						  "f_href": "You must enter a link location",
						  "f_text": "You must enter some text for the link body"
					   };
					
		for (var i in required)
		{
			var el = document.getElementById(i);
		  
			if ( ! el.value )
			{
				alert(required[i]);
				el.focus();
				return false;
			}
		}
		
		//-----------------------------------------
		// Now prep the table
		//-----------------------------------------
		
		var link_title  = allow_advanced && param['f_ad_title']  ? ' title="' + param['f_ad_title']    + '" ' : '';
		var link_target = allow_advanced && param['f_ad_target'] ? ' target="' + param['f_ad_target']  + '"' : '';
		var link_html   = '';
		
		if ( parent.IPS_editor[ editor_id ].use_bbcode && ! parent.IPS_editor[ editor_id ].is_rte )
		{
			link_html = '[url=' + param['f_href'] + ']' + param['f_text'] + '[/url]';
		}
		else
		{
			link_html = '<a href="' + param['f_href'] + '"' + link_title + link_target + '>'  + param['f_text']  + '</a>';
		}

    	//-----------------------------------------
        // Return..
        //-----------------------------------------

		parent.IPS_editor[ editor_id].editor_check_focus();
       
        parent.IPS_editor[ editor_id ].insert_text( link_html );

		//-----------------------------------------
		// Kill Window
		//-----------------------------------------
		
		do_cancel();
		
		return false;
	}
	//-->
	</script>

<style type='text/css' media="all">
@import url(rte_popup.css);
</style>
</head>

<body onload="Init()">

<div class="title">Insert Link</div>

<form action="" method="get">

<fieldset style="margin-left: 5px;">
<legend>Link Location</legend>
<table border="0" width='100%' style="padding: 0px; margin: 0px">
  <tbody>
  <tr>
    <td><textarea name="href" id="f_href" style='width:100%;height:60px' cols='23' onfocus='this.select()' rows='4'>http://</textarea></td>
  </tr>
  </tbody>
</table>
</fieldset>

<br />

<fieldset style="margin-left: 5px;">
<legend>Link Text</legend>
<table border="0" width='100%' style="padding: 0px; margin: 0px">
  <tbody>
  <tr>
    <td><textarea name="text" id="f_text" style='width:100%;height:70px' cols='23'  rows='4'></textarea></td>
  </tr>
  </tbody>
</table>
</fieldset>

<div id='advanced-options' style='display:none'>
	<br />
	<fieldset style="margin-left: 5px;">
	<legend>Advanced Options</legend>
	<table border="0" width='100%' style="padding: 0px; margin: 0px">
	  <tbody>
	  <tr>
	    <td>Title:</td>
	    <td><input type="text" name="title" id="f_ad_title" size="20" value="" title="Link Title" /></td>
	  </tr>
	  <tr>
	    <td>Target:</td>
	    <td><input type="text" name="fontsize" id="f_ad_target" size="20" value="" title="Link Target" /></td>
	  </tr>
	  </tbody>
	</table>
	</fieldset>
</div>

<br />





<div style="text-align: center;">
<hr />
<button type="button" class='tblbutton' name="ok" onclick="return do_submit();">OK</button>
<button type="button" class='tblbutton' name="cancel" onclick="return do_cancel();">Cancel</button>
</div>

</form>

</body>
</html>
