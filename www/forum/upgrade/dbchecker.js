/*-------------------------------------------------------------------------*/
// Hide / Unhide menu elements
/*-------------------------------------------------------------------------*/

function ShowHide(id1, id2)
{
	if (id1 != '') toggleview(id1);
	if (id2 != '') toggleview(id2);
}
	
/*-------------------------------------------------------------------------*/
// Get element by id
/*-------------------------------------------------------------------------*/

function my_getbyid(id)
{
	itm = null;
	
	if (document.getElementById)
	{
		itm = document.getElementById(id);
	}
	else if (document.all)
	{
		itm = document.all[id];
	}
	else if (document.layers)
	{
		itm = document.layers[id];
	}
	
	return itm;
}

/*-------------------------------------------------------------------------*/
// Show/hide toggle
/*-------------------------------------------------------------------------*/

function toggleview(id)
{
	if ( ! id ) return;
	
	if ( itm = my_getbyid(id) )
	{
		if (itm.style.display == "none")
		{
			my_show_div(itm);
		}
		else
		{
			my_hide_div(itm);
		}
	}
}

/*-------------------------------------------------------------------------*/
// Set DIV ID to hide
/*-------------------------------------------------------------------------*/

function my_hide_div(itm)
{
	if ( ! itm ) return;
	
	itm.style.display = "none";
}

/*-------------------------------------------------------------------------*/
// Set DIV ID to show
/*-------------------------------------------------------------------------*/

function my_show_div(itm)
{
	if ( ! itm ) return;
	
	itm.style.display = "";
}

var all_queries = new Array();

function fix_all_dberrors()
{
	var url = 'index.php?p=install&sub=checkdb&saved_data='+save_data;
	
	for( var i=0; i<all_queries.length; i++ )
	{
		url += '&query'+i+'='+all_queries[i];
	}
	
	window.location = url;
	return false;
}