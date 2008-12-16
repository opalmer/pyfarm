var IPS_Lite_Editor=new Array();function ips_text_editor_lite(editor_id){this.editor_id=editor_id;this.control_obj=document.getElementById(editor_id+'-controls');this.initialized=false;this.buttons=new Array();this.fonts=new Array();this.state=new Array();this.text_obj=document.getElementById(this.editor_id+'_textarea');this.open_brace='[';this.close_brace=']';this.editor_document=this.text_obj;this.editor_window=this.editor_document;this._ie_cache=null;this.is_ie=is_ie;this.is_moz=is_moz;this.is_opera=is_opera;this.has_focus=false;this.emoticon_window_id=null;this.init=function(){var controls=new Array();if(!this.control_obj){return;}var items=this.control_obj.getElementsByTagName("DIV");for(var i=0;i<items.length;i++){if((items[i].className=='rte-normal'||items[i].className=='rte-menu-button'||items[i].className=='rte-normal-menubutton')&&items[i].id){controls[controls.length]=items[i].id;}}for(var i=0;i<controls.length;i++){var control=document.getElementById(controls[i]);if(control.className=='rte-normal'){this.init_editor_button(control);}else if(control.className=='rte-menu-button'||control.className=='rte-normal-menubutton'){this.init_editor_popup_button(control);}}this.text_obj.onfocus=ips_editor_events.prototype.editor_window_onfocus;this.text_obj.onblur=ips_editor_events.prototype.editor_window_onblur;if(this.editor_document.addEventListener){this.editor_document.addEventListener('keypress',ips_editor_events.prototype.editor_document_onkeypress,false);}};this.editor_check_focus=function(){if(!this.text_obj.has_focus){if(this.is_opera){this.text_obj.focus();}try{this.text_obj.focus();}catch(err){return false;}}return true;};this.init_editor_button=function(obj){obj.cmd=obj.id.replace(new RegExp('^'+this.editor_id+'_cmd_(.+?)$'),'$1');obj.editor_id=this.editor_id;this.buttons[obj.cmd]=obj;obj.state=false;obj.mode='normal';obj.real_type='button';obj.onclick=ips_editor_events.prototype.button_onmouse_event;obj.onmousedown=ips_editor_events.prototype.button_onmouse_event;obj.onmouseover=ips_editor_events.prototype.button_onmouse_event;obj.onmouseout=ips_editor_events.prototype.button_onmouse_event;};this.set_button_context=function(obj,state,type){if(typeof type=='undefined'){type='button';}switch(obj.state){case true:{switch(state){case 'mouseout':{this.editor_set_ctl_style(obj,'button','selected');break;}case 'mouseover':case 'mousedown':case 'mouseup':{this.editor_set_ctl_style(obj,type,'down');break;}}break;}default:{switch(state){case 'mouseout':{this.editor_set_ctl_style(obj,type,'normal');break;}case 'mousedown':{this.editor_set_ctl_style(obj,type,'down');break;}case 'mouseover':case 'mouseup':{this.editor_set_ctl_style(obj,type,'hover');break;}}break;}}};this.editor_set_ctl_style=function(obj,type,mode){if(obj.mode!=mode){var extra='';if(type=='menu'){extra='-menu';}else if(type=='menubutton'){extra='-menubutton';}extra+=obj.colorname?'-color':'';extra+=obj.emo_id?'-emo':'';obj.mode=mode;try{switch(mode){case "normal":{obj.className='rte-normal'+extra;}break;case "hover":{obj.className='rte-hover'+extra;}break;case "selected":case "down":{obj.className='rte-selected'+extra;}break;}}catch(e){}}};this.format_text=function(e,command,arg){e=ipsclass.cancel_bubble(e,true);if(command.match( /resize_/)){this.resize_editorbox(command.replace( /.+?resize_(up|down)/,"$1"));}if(command.match( /emoticon/)){this.show_all_emoticons();}this.editor_check_focus();if(this[command]){var return_val=this[command](e);}else{try{var return_val=this.apply_formatting(command,false,(typeof arg=='undefined'?true:arg));}catch(e){var return_val=false;}}this.editor_check_focus();return return_val;};this.createlink=function(){var _url=prompt(ipb_global_lang['editor_enter_url'],'http://');if(!_url||_url==null||_url=='http://'){return false;}var _text=this.get_selection();_text=_text?_text:prompt(ipb_global_lang['editor_enter_title']);if(!_text||_text==null){return false;}this.wrap_tags('url',_url,_text);};this.insertimage=function(){var _text=this.get_selection();var _url=prompt(ipb_global_lang['editor_enter_image'],_text?_text:"http://");if(!_url||_url==null||_url=='http://'){return false;}this.wrap_tags('img',false,_url);};this.ipb_quote=function(){var _text=this.get_selection();this.wrap_tags('quote',false,_text);};this.ipb_code=function(){var _text=this.get_selection();this.wrap_tags('code',false,_text);};this.apply_formatting=function(cmd,dialog,argument){switch(cmd){case 'bold':case 'italic':case 'underline':{this.wrap_tags(cmd.substr(0,1),false);return;}case 'justifyleft':case 'justifycenter':case 'justifyright':{this.wrap_tags(cmd.substr(7),false);return;}case 'indent':{this.wrap_tags(cmd,false);return;}case 'createlink':{var sel=this.get_selection();if(sel){this.wrap_tags('url',argument);}else{this.wrap_tags('url',argument,argument);}return;}case 'fontname':{this.wrap_tags('font',argument);return;}case 'fontsize':{this.wrap_tags('size',argument);return;}case 'forecolor':{this.wrap_tags('color',argument);return;}case 'backcolor':{this.wrap_tags('background',argument);return;}case 'insertimage':{this.wrap_tags('img',false,argument);return;}case 'strikethrough':{this.wrap_tags('strike',false);return;}case 'superscript':{this.wrap_tags('sup',false);return;}case 'subscript':{this.wrap_tags('sub',false);return;}case 'removeformat':return;}};this.wrap_tags=function(tag_name,has_option,selected_text){var tag_close=tag_name;if(typeof selected_text=='undefined'){selected_text=this.get_selection();selected_text=(selected_text===false)?'':new String(selected_text);}if(has_option===true){var option=prompt(ips_language_array['js_rte_optionals']?ips_language_array['js_rte_optionals']:"Enter the optional arguments for this tag",'');if(option){var opentag=this.open_brace+tag_name+'="'+option+'"'+this.close_brace;}else{return false;}}else if(has_option!==false){var opentag=this.open_brace+tag_name+'="'+has_option+'"'+this.close_brace;}else{var opentag=this.open_brace+tag_name+this.close_brace;}var closetag=this.open_brace+'/'+tag_close+this.close_brace;var text=opentag+selected_text+closetag;this.insert_text(text);return false;};this.wrap_tags_lite=function(start_text,close_text){selected_text=this.get_selection();selected_text=(selected_text===false)?'':new String(selected_text);this.insert_text(start_text+selected_text+close_text);return false;};this.editor_get_contents=function(){return this.editor_document.value;};this.get_selection=function(){if(typeof(this.editor_document.selectionStart)!='undefined'){return this.editor_document.value.substr(this.editor_document.selectionStart,this.editor_document.selectionEnd-this.editor_document.selectionStart);}else if((document.selection&&document.selection.createRange)||this._ie_cache){return this._ie_cache?this._ie_cache.text:document.selection.createRange().text;}else if(window.getSelection){return window.getSelection()+'';}else{return false;}};this.insert_text=function(text){if(this.editor_check_focus()==false){return false;}if(typeof(this.editor_document.selectionStart)!='undefined'){var open=this.editor_document.selectionStart+0;var st=this.editor_document.scrollTop;this.editor_document.value=this.editor_document.value.substr(0,this.editor_document.selectionStart)+text+this.editor_document.value.substr(this.editor_document.selectionEnd);if(!text.match(new RegExp("\\" + this.open_brace + "(\\S+?)" + "\\" + this.close_brace + "\\" + this.open_brace + "/(\\S+?)" + "\\" + this.close_brace ) ) )
{
this.editor_document.selectionStart = open;
this.editor_document.selectionEnd   = open + text.length;
this.editor_document.scrollTop      = st;
}
}
else if ( ( document.selection && document.selection.createRange ) || this._ie_cache )
{
var sel  = this._ie_cache ? this._ie_cache : document.selection.createRange();
sel.text = text.replace(/\r?\n/g, '\r\n');
sel.select();
}
else
{
this.editor_document.value += text;
}
this._ie_cache = null;
};
this.show_all_emoticons = function()
{
if ( typeof( this.emoticon_window_id ) != null )
{
this.emoticon_window_id = window.open( ipb_var_base_url + "act=legends&CODE=emoticons&_lite=1&editor_id=" + editor_id,"Legends","width=250,height=500,resizable=yes,scrollbars=yes");
}
else
{
this.emoticon_window_id.focus();
}
return false;
};
this.insert_emoticon = function( emo_id, emo_image, emo_code, event )
{
emo_code = ipsclass.un_htmlspecialchars( emo_code );
this.wrap_tags_lite( " " + emo_code, " ");
if ( this.is_ie )
{
if ( IPS_Lite_Editor[ this.editor_id ].emoticon_window_id != '' && typeof( IPS_Lite_Editor[ this.editor_id ].emoticon_window_id ) != 'undefined' )
{
IPS_Lite_Editor[ this.editor_id ].emoticon_window_id.focus();
}
}
};
this.resize_editorbox = function( direction )
{
var inc_value	   = 100;
var current_height = parseInt( this.text_obj.style.height );
var new_height     = 0;
current_height     = current_height ? current_height : 200;
if ( current_height >= 50 )
{
if ( direction == 'up' )
{
new_height = current_height - inc_value;
}
else
{
new_height = current_height + inc_value;
}
if ( new_height > 149 )
{
this.text_obj.style.height = new_height + 'px';
}
}
};
};
function ips_editor_events()
{
}
ips_editor_events.prototype.button_onmouse_event = function(e)
{
if ( is_ie )
{
e = ipsclass.cancel_bubble(e, true);
}
if ( e.type == 'click' )
{
IPS_Lite_Editor[this.editor_id].format_text(e, this.cmd, false, true);
}
IPS_Lite_Editor[this.editor_id].set_button_context(this, e.type);
};
ips_editor_events.prototype.editor_window_onfocus = function(e)
{
this.has_focus = true;
};
ips_editor_events.prototype.editor_window_onblur = function(e)
{
this.has_focus = false;
};
ips_editor_events.prototype.editor_document_onkeypress = function(e)
{	
if ( e.ctrlKey )
{
switch (String.fromCharCode(e.charCode).toLowerCase())
{
case 'b': cmd = 'bold';      break;
case 'i': cmd = 'italic';    break;
case 'u': cmd = 'underline'; break;
default: return;
}
e.preventDefault();
IPS_Lite_Editor[this.editor_id].apply_formatting(cmd, false, null);
return false;
}
};
