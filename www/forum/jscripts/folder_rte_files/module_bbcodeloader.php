<html>
<head>
  <title>Insert Custom BBCode</title>
	<script type="text/javascript">
	<!--
	
	//-----------------------------------------
    // Attempt to get editor ID
    //-----------------------------------------

    var editor_id         = <?php print '"'.trim( htmlspecialchars( substr( $_REQUEST['editorid'], 0, 30 ) ) ).'";'; ?>
	var bbcode_id         = <?php print '"'.trim( htmlspecialchars( substr( $_REQUEST['id'], 0, 30 ) ) ).'";'; ?>
	var this_module       = 'bbcodeloader';
	var this_height       = 400;
	var allow_advanced    = null;
	var current_selection = null;
	var bbcode            = parent.ips_bbcode_items[ bbcode_id ];
	
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
		
		document.getElementById("f_content_text").focus();
		
		//-----------------------------------------
		// Set up initial text value
		//-----------------------------------------
		
		document.getElementById("f_content_text").value = current_selection;
		
		//-----------------------------------------
		// Add in data
		//-----------------------------------------
		
		document.getElementById("_title").innerHTML   += ' ' + bbcode['title'];
		document.getElementById("_example").innerHTML += ' ' + bbcode['example'];
		document.getElementById("_option").innerHTML  += ' ' + bbcode['text_option'];
		document.getElementById("_content").innerHTML += ' ' + bbcode['text_content'];
		
		//-----------------------------------------
		// Using option?
		//-----------------------------------------
		
		if ( parseInt( bbcode['use_option'] ) == 1 )
		{
			document.getElementById( 'use-option' ).style.display = '';
		}
		
		//-----------------------------------------
		// Set up initial text value
		//-----------------------------------------
		
		document.getElementById("f_content_text").value = parent.IPS_editor[ editor_id ].strip_empty_html( current_selection );
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
		var fields = [ "f_option_text", "f_content_text" ];
					  
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
						  "f_option_text" : "You must enter option text",
						  "f_content_text": "You must enter content text"
					   };
					
		for (var i in required)
		{
			var el = document.getElementById(i);
		  	
			if ( i == 'f_option_text' )
			{
				if ( parseInt( bbcode['use_option'] ) != 1 )
				{
					continue;
				}
			}
			
			if ( ! el.value )
			{
				alert( required[i] );
				el.focus();
				return false;
			}
		}
		
		//-----------------------------------------
		// Now prep the table
		//-----------------------------------------
		
		var _html    = '';
		var _content = document.getElementById( 'f_content_text' ).value;
		var _option  = document.getElementById( 'f_option_text' ).value;
		
		if ( parseInt( bbcode['use_option'] ) == 1 )
		{
			if ( parseInt( bbcode['switch_option'] ) == 1 )
			{
				_html = '[' + bbcode['tag'] + '="' + _content + '"]' + _option + '[/' + bbcode['tag'] + ']';
			}
			else
			{
				_html = '[' + bbcode['tag'] + '="' + _option + '"]' + _content + '[/' + bbcode['tag'] + ']';
			}
		}
		else
		{
			_html = '[' + bbcode['tag'] + ']' + _content + '[/' + bbcode['tag'] + ']';
		}	
		
		//-----------------------------------------
		// Is this RTE?
		//-----------------------------------------
		
		if ( parent.IPS_editor[ editor_id ].is_rte )
		{
			_html = _html.replace( /(\n|\r|<br>|<br \/>)/g, "<br />" );
		}
		
    	//-----------------------------------------
        // Return..
        //-----------------------------------------

		parent.IPS_editor[ editor_id].editor_check_focus();
       
        parent.IPS_editor[ editor_id ].insert_text( _html );

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

<div class="title" id="_title">Insert</div>

<fieldset style="margin-left: 5px;">
<legend><strong>Example</strong></legend>
	<div id="_example" style='font-family:"Courier New", Monaco, Courier;font-size:12px'></div>
</fieldset>

<br />

<form action="" method="get">

<div id='use-option' style='display:none'>
<fieldset style="margin-left: 5px;">
<legend><strong>Option Text</strong></legend>
<div id='_option' style='padding:2px;color:#555;'></div>
<table border="0" width='100%' style="padding: 0px; margin: 0px">
  <tbody>
  <tr>
    <td><textarea name="text" id="f_option_text" style='width:100%;height:70px' cols='23'  rows='4'></textarea></td>
  </tr>
  </tbody>
</table>
</fieldset>
<br />
</div>

<fieldset style="margin-left: 5px;">
<legend><strong>Content Text</strong></legend>
<div id='_content' style='padding:2px;color:#555;'></div>
<table border="0" width='100%' style="padding: 0px; margin: 0px">
  <tbody>
  <tr>
    <td><textarea name="href" id="f_content_text" style='width:100%;height:60px' cols='23' onfocus='this.select()' rows='4'></textarea></td>
  </tr>
  </tbody>
</table>
</fieldset>

<br />


<div style="text-align: center;">
<hr />
<button type="button" class='tblbutton' name="ok" onclick="return do_submit();">OK</button>
<button type="button" class='tblbutton' name="cancel" onclick="return do_cancel();">Cancel</button>
</div>

</form>

</body>
</html>
