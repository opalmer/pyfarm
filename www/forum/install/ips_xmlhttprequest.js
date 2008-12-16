//------------------------------------------------------------------------------
// IPS XML HTTP REQUEST 
//------------------------------------------------------------------------------
// Supports Safari, Mozilla 1.3+ (Firefox, etc) and IE 5.5+
// (c) 2005 Invision Power Services, Inc.
// http://www.invisionpower.com
//------------------------------------------------------------------------------

/*--------------------------------------------*/
// New object
/*--------------------------------------------*/

function ajax_request()
{
	this.isIE               = false;
	this.allow_use          = use_enhanced_js ? true : false;
	this.xmlhandler         = null;
	this.error_string       = '';
	this.nocache			= true;
	this.do_request_functon = function() {}
	this.loading_fired		= 0;
	this.centerdiv          = null;
}

/*--------------------------------------------*/
// Initiate
/*--------------------------------------------*/

ajax_request.prototype.xml_init = function()
{
	try
	{
		//------------------------------------------------
		// Moz, Safari, Opera
		//------------------------------------------------
		
		this.xmlhandler = new XMLHttpRequest();
		this.ie        = false;
		this.allow_use = true;
		return true;
	}
	catch(e)
	{
		try
		{
			//------------------------------------------------
			// IE
			//------------------------------------------------
			
			this.xmlhandler = new ActiveXObject('Microsoft.XMLHTTP');
			this.ie        = true;
			this.allow_use = true;
			return true;
		}
		catch(e)
		{
			this.ie        = true;
			this.allow_use = false;
			return false;
		}
	}
}

/*--------------------------------------------*/
// Actually send data
/*--------------------------------------------*/

ajax_request.prototype.process = function( url, type, post )
{
	//------------------------------------------------
	// The 'post' variable needs to be in the following format:
	//
	// var=content&var2=content&var3=content
	//
	// All values need to be escaped with encodeURIComponent();
	//------------------------------------------------
	
	type = type == "POST" ? "POST" : "GET";
	
	//------------------------------------------------
	// Use nocache where possible...
	//------------------------------------------------
	
	if ( this.nocache == true  && type == 'GET' )
	{
		url = this.nocache_url( url );
	}
	
	//------------------------------------------------
	// Make sure we're initialized
	//------------------------------------------------
	
	if ( ! this.xmlhandler )
	{
		this.xml_init();
	}

	//------------------------------------------------
	// Only go when ready
	//------------------------------------------------
	
	if ( ! this.readystate_not_ready() )
	{
		this.xmlhandler.open(type, url, true);
		
		if ( type == "GET" )
		{
			this.xmlhandler.send(null);
		}
		else
		{
			if ( typeof( this.xmlhandler.setRequestHeader ) != "undefined" )
			{
				this.xmlhandler.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			}
			
			this.xmlhandler.send( post );
		}
		
		if ( this.xmlhandler.readyState == 4 && this.xmlhandler.status == 200 )
		{
			return true;
		}
	}
	
	return false;
}

/*--------------------------------------------*/
// retrieve text of an XML document element, including
// elements using namespaces
/*--------------------------------------------*/

ajax_request.prototype.get_element_text_ns = function(prefix, local, parentElem, index)
{
    var result = "";
    
    if ( prefix && this.isIE )
    {
        //-------------------------------
        // IE
        //-------------------------------
        
        result = parentElem.getElementsByTagName(prefix + ":" + local)[index];
    }
    else
    {
        //-------------------------------
        // Safari, Gecko
        //-------------------------------
        
        result = parentElem.getElementsByTagName(local)[index];
    }
    
    if ( result )
    {
        if (result.childNodes.length > 1)
        {
            return result.childNodes[1].nodeValue;
        }
        else
        {
            return result.firstChild.nodeValue;    		
        }
    }
    else
    {
        return "n/a";
    }
}

/*--------------------------------------------*/
// Make sure URL is not cached
/*--------------------------------------------*/

ajax_request.prototype.nocache_url = function (url)
{
	var sep    = ( -1 < url.indexOf("?") ) ? "&" : "?";
	var mydate = new Date();
	var newurl = url + sep + "__=" + mydate.getTime();
	return newurl;
}

/*--------------------------------------------*/
// Takes an array of:
// array[ field ] = value
// and returns a nice encoded POST string
/*--------------------------------------------*/

ajax_request.prototype.format_for_post = function( arrayfields )
{
	var str = '';
	
	try
	{
		for( var i in arrayfields )
		{
			str += i + '=' + this.encodeurl(arrayfields[i]) + '&';
		}
	}
	catch(e)
	{
	}
	
	return str;
}

/*--------------------------------------------*/
// Hand roll encode URL to UTF-8
/*--------------------------------------------*/

ajax_request.prototype.encodeurl = function( url )
{
	//-------------------------------
	// Ensure we have a string
	//-------------------------------
	
	url = url.toString();

	var regcheck = url.match(/[\x90-\xFF]/g);
	
	if ( regcheck )
	{
		for (var i = 0; i < i.length; i++)
		{
			url = url.replace(regcheck[i], '%u00' + (regcheck[i].charCodeAt(0) & 0xFF).toString(16).toUpperCase());
		}
	}

	return escape(url).replace(/\+/g, "%2B");
}


/*--------------------------------------------*/
// Check to ensure ready state-ness
/*--------------------------------------------*/

ajax_request.prototype.readystate_not_ready = function()
{
	return ( this.xmlhandler.readyState && ( this.xmlhandler.readyState < 4 ) );
}

/*--------------------------------------------*/
// Check to ensure ready state-ness
/*--------------------------------------------*/

ajax_request.prototype.readystate_ready_and_ok = function()
{
	return ( this.xmlhandler.readyState == 4 && this.xmlhandler.status == 200 ) ? true : false;
}

/*--------------------------------------------*/
// Onready state change event handler
/*--------------------------------------------*/

ajax_request.prototype.onreadystatechange = function( event )
{
	//------------------------------------------------
	// Make sure we're initialized
	//------------------------------------------------
	
	if ( ! this.xmlhandler )
	{
		this.xml_init();
	}
	
	//------------------------------------------------
	// Make sure its a function event
	//------------------------------------------------
	
	if ( typeof(event) == 'function' )
	{
		this.xmlhandler.onreadystatechange = event;
	}
}

/*--------------------------------------------*/
// Show loading layer
/*--------------------------------------------*/

ajax_request.prototype.show_loading = function( message )
{
	if ( ! this.loading_fired )
	{
		this.loading_fired = 1;
		
		//------------------------------------------------
		// Change text?
		//------------------------------------------------
		
		if ( message )
		{
			document.getElementById( 'loading-layer-text' ).innerHTML = message;
		}
		
		this.centerdiv         = new center_div();
		this.centerdiv.divname = 'loading-layer';
		this.centerdiv.move_div();
	}
	
	return;
}

/*--------------------------------------------*/
// Hide loading layer
/*--------------------------------------------*/

ajax_request.prototype.hide_loading = function()
{
	try
	{
		if ( this.centerdiv && this.centerdiv.divobj )
		{
			this.centerdiv.hide_div();
		}
	}
	catch(e)
	{
	}
	
	this.loading_fired = 0;
	
	return;
}

/*--------------------------------------------*/
// IPB thinks we can use fancy JS, lets see...
/*--------------------------------------------*/

if ( use_enhanced_js )
{
	use_enhanced_js = ajax_request.prototype.xml_init() ? 1 : 0;
}