var topic_dates={};var topic_flags={};var topic_state={};var forum_mark_time;var newer_topics=new Array;var newer_count=0;var flags_count=0;var forum_id;var url_extra='';var start_int=0;var forum_mark_sent=0;var span_html=new Array;var desc_html=new Array;var text_html=new Array;var folders=new Array;var _desc_clicked=0;var topic_links_init=0;var _this_select_all=0;var tid_date=new Array();var click_delay=1200;function forum_mod_pop(fid){try{menu_action_close();}catch(e){}PopUp(ipb_var_base_url+'act=mod&CODE=prune_start&f='+fid+'&auth_key='+ipb_md5_check,'PRUNE',600,500);}function forum_init_topic_links(){var pagelinks=document.getElementsByTagName('a');for(var i=0;i<=pagelinks.length;i++){try{if(!pagelinks[i].id){continue;}}catch(e){continue;}var linkid=pagelinks[i].id;var linkname=linkid.replace( /^(.*)-(\d+)$/,"$1");if(linkname=='tid-link'){pagelinks[i].onmousedown=topic_link_event_mousedown;pagelinks[i].onmouseup=topic_link_event_mouseup;if(!topic_links_init){pagelinks[i].title=pagelinks[i].title+'. '+lang_clickhold;}}}topic_links_init=1;}function topic_link_event_mousedown(event){event=global_cancel_bubble(event,true);var tid=_get_tid_from_id(this.id);tid_date[tid]=_get_time_now();setTimeout("topic_link_event_timer("+tid+")",5);}function topic_link_event_timer(tid){var timenow=_get_time_now();if(timenow>0&&tid_date[tid]>0&&((timenow-tid_date[tid])>click_delay)){tid_date[tid]=0;span_to_input(tid);return false;}if(tid_date[tid]>0){setTimeout("topic_link_event_timer("+tid+")",5);}else{return false;}}function topic_link_event_mouseup(event){event=global_cancel_bubble(event,true);var tid=_get_tid_from_id(this.id);tid_date[tid]=0;}function _get_time_now(){var mydate=new Date();return mydate.getTime();}function _get_tid_from_id(id){return id.replace( /.*\-(\d+)/,"$1");}function topic_toggle_folder(tid,state){if(!use_enhanced_js){return false;}if(!perm_can_open&&!perm_can_close){return false;}var td_content=document.getElementById('tid-folder-'+tid).innerHTML;if(topic_state[tid]){state=topic_state[tid];}if(state=='closed'){if(!perm_can_open){return false;}if(folders[tid]){td_content=folders[tid];}state='open';}else{if(!perm_can_close){return false;}folders[tid]=td_content;state='closed';}topic_state[tid]=state;do_request_function=function(){if(!xmlobj.readystate_ready_and_ok()){return;}var returned=xmlobj.xmlhandler.responseText;td_content=returned;document.getElementById('tid-folder-'+tid).innerHTML=td_content;};xmlobj=new ajax_request();xmlobj.onreadystatechange(do_request_function);xmlobj.process(ipb_var_base_url+'act=xmlout&do=save-topic&type=openclose&name='+state+'&md5check='+ipb_md5_check+'&tid='+tid);return false;}function span_to_input(tid){if(!use_enhanced_js){return false;}if(!perm_can_edit){return false;}if(_desc_clicked){return false;}span_html[tid]=document.getElementById('tid-span-'+tid).innerHTML;text_html[tid]=document.getElementById('tid-link-'+tid).innerHTML;perm_max_length=perm_max_length?perm_max_length:50;document.getElementById('tid-span-'+tid).innerHTML='<input id="edit-'+tid+'" class="dny-edit-title" maxlength="'+perm_max_length+'" type="text" size="40" value="'+text_html[tid].replace( /"/g,'&quot;')+'" />';//"'
document.getElementById( 'edit-' + tid ).onkeyup       = function( event ) { tid_keypress(event, tid) }
document.getElementById( 'edit-' + tid ).onblur        = function( event ) { tid_blur(tid) }
document.getElementById( 'edit-' + tid ).focus();
return false;
}
function span_desc_to_input( tid )
{
if ( ! use_enhanced_js )
{
return false;
}
if ( ! perm_can_edit )
{
return false;
}
if ( _desc_clicked )
{
return false;
}
_desc_clicked = 1;
desc_html[ tid ] = document.getElementById( 'tid-desc-' + tid ).innerHTML;
document.getElementById( 'tid-desc-' + tid ).innerHTML = '<input id="edit-'+tid+'" maxlength="70" class="dny-edit-title" type="text" size="40" onblur="tid_blur(\''+tid+'\', \'desc\')" onkeypress="tid_keypress(event, \''+tid+'\',\'desc\')" value="'+desc_html[tid].replace( /"/g,'&quot;')+'" />';//"'
document.getElementById( 'edit-'     + tid ).focus();
return false;
}
tid_blur = function( tid, type )
{
new_text = document.getElementById( 'edit-' + tid ).value;
if( type == 'desc' )
{
tid_save( tid, new_text, type );
}
else
{
if ( new_text != "" )
{
tid_save( tid, new_text, type );
}
}
}
tid_keypress = function( evt, tid, type )
{
if ( is_safari )
{
return false;
}
evt      = evt ? evt : window.event;
new_text = document.getElementById( 'edit-' + tid ).value;
if ( ( evt.keyCode == 13 || evt.keyCode == 3 ) && new_text != "" )
{
tid_save( tid, new_text, type );
}
else if( evt.keyCode == 27 )
{
if( type == 'desc' )
{
document.getElementById( 'tid-desc-' + tid ).innerHTML = desc_html[ tid ];
}
else
{
document.getElementById( 'tid-span-' + tid ).innerHTML = span_html[ tid ];
document.getElementById( 'tid-link-' + tid ).innerHTML = text_html[ tid ];
}
return false;
}	
}
tid_save = function( tid, new_text, type )
{
var donotedit = 0;
if ( type == 'desc' )
{
if ( new_text == desc_html[ tid ] )
{
donotedit = 1;
}
_desc_clicked = 0;
document.getElementById( 'tid-desc-' + tid ).innerHTML = new_text;
}
else
{
if ( new_text == text_html[ tid ] )
{
donotedit = 1;
}
type = 'title';
document.getElementById( 'tid-span-' + tid ).innerHTML = span_html[ tid ];
document.getElementById( 'tid-link-' + tid ).innerHTML = new_text;
forum_init_topic_links();
}
if ( donotedit )
{
return false;
}
var url    = ipb_var_base_url + 'act=xmlout&do=save-topic&type='+type+'&md5check='+ipb_md5_check+'&tid='+tid;
var fields = new Array();
fields['md5check'] = ipb_md5_check;
fields['tid']      = tid;
fields['act']      = 'xmlout';
fields['do']       = 'save-topic';
fields['type']     = type;
fields['name']     = new_text;
do_request_function = function()
{
if ( ! xmlobj.readystate_ready_and_ok() )
{
return;
}
var returned = xmlobj.xmlhandler.responseText;
if ( type != 'desc' && ! returned.match( /<null>s<\/null>/ ) )
{
document.getElementById( 'tid-link-' + tid ).innerHTML = returned;
}
};
xmlobj = new ajax_request();
xmlobj.onreadystatechange( do_request_function );
xmlobj.process( url, 'POST', xmlobj.format_for_post(fields) );
return false;
}
function who_posted(tid)
{
window.open( ipb_var_base_url+ "act=Stats&CODE=who&t="+tid, "WhoPosted", "toolbar=no,scrollbars=yes,resizable=yes,width=230,height=300");
}
function checkdelete()
{
if ( ! document.modform.selectedtids.value )
{
return false;
}
isDelete = document.modform.tact.options[document.modform.tact.selectedIndex].value;
if (isDelete == 'delete')
{
formCheck = confirm( lang_suredelete );
if (formCheck == true)
{
return true;
}
else
{
return false;
}
}
}
function forum_select_all()
{
clean                = new Array();
saved                = new Array();
var topics_this_page = new Array();
tmp = document.modform.selectedtids.value;
if ( tmp != "" )
{
saved = tmp.split(",");
}
if( _this_select_all == 0 )
{
var the_topics = document.getElementsByTagName('input');
for ( var i = 0 ; i <= the_topics.length ; i++ )
{
var e = the_topics[i];
if ( e && (e.type == 'hidden') && (! e.disabled) )
{
var s = e.id;
var a = s.replace( /^tid_(.+?)$/, "$1" );
if ( a )
{
try
{
document.getElementById( 'ipb-topic-' + a ).src = selectedbutton;
clean[clean.length]   = a;
topics_this_page[ a ] = 1;
}
catch(err)
{
}
}
}
}
document.getElementById( 'ipb-topics-all' ).src = selectedbutton;
_this_select_all = 1;
}
else
{
var the_topics = document.getElementsByTagName('input');
for ( var i = 0 ; i <= the_topics.length ; i++ )
{
var e = the_topics[i];
if ( e && (e.type == 'hidden') && (! e.disabled) )
{
var s = e.id;
var a = s.replace( /^tid_(.+?)$/, "$1" );
if ( a )
{
try
{
document.getElementById( 'ipb-topic-' + a ).src = unselectedbutton;
topics_this_page[ a ] = 1;
}
catch(err)
{
}
}
}
}
document.getElementById( 'ipb-topics-all' ).src = unselectedbutton;
_this_select_all = 0;
}
for( i = 0 ; i < saved.length; i++ )
{
if ( saved[i] != "" && topics_this_page[ saved[i] ] != 1 )
{
clean[clean.length] = saved[i];
}
}		
newvalue = clean.join(',');
var oldvalue = 0;
for( var k = 0; k < clean.length; k++ )
{
if( topics_this_page[ clean[ k ] ] != 1 )
{
oldvalue++;
}
}
my_setcookie( 'modtids', newvalue, 0 );
document.modform.selectedtids.value = newvalue;
newcount = stacksize(clean);
if( oldvalue > 0 )
{	
document.modform.gobutton.value = ipsclass.html_entity_decode( lang_gobutton ) + ' (' + newcount + ') (' + oldvalue + ' ' + ipsclass.html_entity_decode( lang_otherpage ) +' )';
}
else
{
document.modform.gobutton.value = ipsclass.html_entity_decode( lang_gobutton ) + ' (' + newcount + ')';
}
return false;	
}
function forum_toggle_tid( tid )
{
var saved = new Array();
var clean = new Array();
var add   = 1;
var _img  = document.getElementById( 'ipb-topic-' + tid );
tmp = document.modform.selectedtids.value;
if( tmp != "" )
{
saved = tmp.split(",");
}
for( i = 0 ; i < saved.length; i++ )
{
if ( saved[i] != "" )
{
if ( saved[i] == tid )
{
add = 0;
}
else
{
clean[clean.length] = saved[i];
}
}
}
if ( add )
{
clean[ clean.length ] = tid;
_img.src              = selectedbutton;
}
else
{
_img.src              = unselectedbutton;
}
newvalue             = clean.join(',');
var topics_this_page = new Array();
var oldvalue         = 0;
var the_topics       = document.getElementsByTagName('input');
for ( var i = 0 ; i <= the_topics.length ; i++ )
{
var e = the_topics[i];
if ( e && (e.type == 'hidden') && (! e.disabled) )
{
var s = e.id;
var a = s.replace( /^tid_(.+?)$/, "$1" );
if ( a )
{
topics_this_page[ a ] = 1;
}
}
}
for( var k = 0; k < clean.length; k++ )
{
if ( topics_this_page[ clean[ k ] ] != 1 )
{
oldvalue++;
}
}
my_setcookie( 'modtids', newvalue, 0 );
document.modform.selectedtids.value = newvalue;
newcount = stacksize(clean);
if ( oldvalue > 0 )
{	
document.modform.gobutton.value = ipsclass.html_entity_decode( lang_gobutton ) + ' (' + newcount + ') (' + oldvalue + ' ' + ipsclass.html_entity_decode( lang_otherpage ) +' )';
}
else
{
document.modform.gobutton.value = ipsclass.html_entity_decode( lang_gobutton ) + ' (' + newcount + ')';
}
return false;
}
function multi_page_jump( url_bit, total_posts, per_page )
{
pages = 1;
cur_st = ipb_var_st;
cur_page  = 1;
if ( total_posts % per_page == 0 )
{
pages = total_posts / per_page;
}
else
{
pages = Math.ceil( total_posts / per_page );
}
msg = ipb_lang_tpl_q1 + " " + pages;
if ( cur_st > 0 )
{
cur_page = cur_st / per_page; cur_page = cur_page -1;
}
show_page = 1;
if ( cur_page < pages )
{
show_page = cur_page + 1;
}
if ( cur_page >= pages )
{
show_page = cur_page - 1;
}
else
{
show_page = cur_page + 1;
}
userPage = prompt( msg, show_page );
if ( userPage > 0  )
{
if ( userPage < 1 )     {    userPage = 1;  }
if ( userPage > pages ) { userPage = pages; }
if ( userPage == 1 )    {     start = 0;    }
else { start = (userPage - 1) * per_page; }
window.location = url_bit + "&st=" + start + "&start=" + start;
}
}
function boards_send_marker_update( fid, is_subforum )
{
try
{
var imgsrc = document.getElementById( 'f-'+fid ).innerHTML;
if ( imgsrc )
{
var regex  = new RegExp( "src=['\"](.*/)("+regex_markers+")['\"]");var results=imgsrc.match(regex);if(img_markers[results[2]]){imgsrc=imgsrc.replace(regex,"src='$1"+img_markers[results[2]]+"'");document.getElementById('f-'+fid).innerHTML=imgsrc;}}}catch(e){}var text_return=0;do_request_function=function(){if(!xmlobj.readystate_ready_and_ok()){return;};text_return=xmlobj.xmlhandler.responseText;};xmlobj=new ajax_request();xmlobj.onreadystatechange(do_request_function);xmlobj.process(ipb_var_base_url+'act=xmlout&do=mark-forum&fid='+fid+'&sf='+is_subforum);if(text_return==1){return false;}}
