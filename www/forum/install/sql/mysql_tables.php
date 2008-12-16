<?php

# Don't forget to remove TYPE=MyISAM AND FULLTEXT INDEXES
# ); must be the last character, no spaces!

$TABLE[] = "CREATE TABLE ibf_admin_logs (
  id bigint(20) NOT NULL auto_increment,
  act varchar(255) default NULL,
  code varchar(255) default NULL,
  member_id int(10) default NULL,
  ctime int(10) default NULL,
  note text NULL,
  ip_address varchar(255) default NULL,
  PRIMARY KEY  (id)
);";

$TABLE[] = "CREATE TABLE ibf_acp_help (
  id int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  is_setting tinyint(1) NOT NULL DEFAULT '0',
  page_key varchar(255) NULL ,
  help_title varchar(255) NULL ,
  help_body text NULL ,
  help_mouseover varchar(255) NULL ,
  KEY page_key ( page_key ) 
);";

$TABLE[] = "CREATE TABLE ibf_admin_login_logs (
 admin_id			INT(10) NOT NULL auto_increment,
 admin_ip_address	VARCHAR(16) NOT NULL default '0.0.0.0',
 admin_username		VARCHAR(40) NOT NULL default '',
 admin_time			INT(10) UNSIGNED NOT NULL default '0',
 admin_success		INT(1) UNSIGNED NOT NULL default '0',
 admin_post_details	TEXT NULL,
 PRIMARY KEY (admin_id),
 KEY admin_ip_address (admin_ip_address),
 KEY admin_time (admin_time)
);";

# IPB 2.1: BETA 2
$TABLE[] = "CREATE TABLE ibf_admin_permission_rows (
 row_member_id	INT(8) NOT NULL,
 row_perm_cache	MEDIUMTEXT NULL,
 row_updated		INT(10) NOT NULL DEFAULT '0',
 PRIMARY KEY (row_member_id)
);";

# IPB 2.1: BETA 2
$TABLE[] = "CREATE TABLE ibf_admin_permission_keys (
 perm_key	VARCHAR(255) NOT NULL,
 perm_main	VARCHAR(255) NOT NULL,
 perm_child	VARCHAR(255) NOT NULL,
 perm_bit	VARCHAR(255) NOT NULL,
 PRIMARY KEY    (perm_key),
 KEY	perm_main  (perm_main),
 KEY perm_child (perm_child)
);";

$TABLE[] = "CREATE TABLE ibf_admin_sessions (
  session_id varchar(32) NOT NULL default '',
  session_ip_address varchar(32) NOT NULL default '',
  session_member_name varchar(250) NOT NULL default '',
  session_member_id mediumint(8) NOT NULL default '0',
  session_member_login_key varchar(32) NOT NULL default '',
  session_location varchar(64) NOT NULL default '',
  session_log_in_time int(10) NOT NULL default '0',
  session_running_time int(10) NOT NULL default '0',
  PRIMARY KEY  (session_id)
);";

$TABLE[] = "CREATE TABLE ibf_announcements (
  announce_id int(10) unsigned NOT NULL auto_increment,
  announce_title varchar(255) NOT NULL default '',
  announce_post text NOT NULL,
  announce_forum text NULL,
  announce_member_id mediumint(8) unsigned NOT NULL default '0',
  announce_html_enabled tinyint(1) NOT NULL default '0',
  announce_nlbr_enabled TINYINT( 1 ) NOT NULL DEFAULT '0',
  announce_views int(10) unsigned NOT NULL default '0',
  announce_start int(10) unsigned NOT NULL default '0',
  announce_end int(10) unsigned NOT NULL default '0',
  announce_active tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (announce_id)
);";

$TABLE[] = "CREATE TABLE ibf_attachments (
  attach_id				int(10) NOT NULL auto_increment,
  attach_ext 			varchar(10) NOT NULL default '',
  attach_file 			varchar(250) NOT NULL default '',
  attach_location 		varchar(250) NOT NULL default '',
  attach_thumb_location varchar(250) NOT NULL default '',
  attach_thumb_width 	smallint(5) NOT NULL default '0',
  attach_thumb_height 	smallint(5) NOT NULL default '0',
  attach_is_image 		tinyint(1) NOT NULL default '0',
  attach_hits 			int(10) NOT NULL default '0',
  attach_date 			int(10) NOT NULL default '0',
  attach_temp 			tinyint(1) NOT NULL default '0',
  attach_post_key 		varchar(32) NOT NULL default '0',
  attach_member_id 		INT(8) NOT NULL default '0',
  attach_approved 		int(10) NOT NULL default '1',
  attach_filesize 		int(10) NOT NULL default '0',
  attach_rel_id			INT(10) NOT NULL default '0',
  attach_rel_module		VARCHAR(100) NOT NULL default '0',
  attach_img_width	    INT(5) NOT NULL default '0',
  attach_img_height		INT(5) NOT NULL default '0',
  PRIMARY KEY  (attach_id),
  KEY attach_pid (attach_rel_id),
  KEY attach_where (attach_rel_module, attach_rel_id),
  KEY attach_post_key (attach_post_key),
  KEY attach_mid_size (attach_member_id,attach_rel_module, attach_filesize)
);";

$TABLE[] = "CREATE TABLE ibf_attachments_type (
  atype_id int(10) NOT NULL auto_increment,
  atype_extension varchar(18) NOT NULL default '',
  atype_mimetype varchar(255) NOT NULL default '',
  atype_post tinyint(1) NOT NULL default '1',
  atype_photo tinyint(1) NOT NULL default '0',
  atype_img text NULL,
  PRIMARY KEY  (atype_id),
  KEY atype ( atype_post, atype_photo ),
  KEY atype_extension (atype_extension)
);";

$TABLE[] = "CREATE TABLE ibf_badwords (
  wid int(3) NOT NULL auto_increment,
  type varchar(250) NOT NULL default '',
  swop varchar(250) default NULL,
  m_exact tinyint(1) default '0',
  PRIMARY KEY  (wid)
);";

$TABLE[] = "CREATE TABLE ibf_banfilters (
  ban_id int(10) NOT NULL auto_increment,
  ban_type varchar(10) NOT NULL default 'ip',
  ban_content text NULL,
  ban_date int(10) NOT NULL default '0',
  PRIMARY KEY  (ban_id)
);";

$TABLE[] = "CREATE TABLE ibf_bulk_mail (
  mail_id int(10) NOT NULL auto_increment,
  mail_subject varchar(255) NOT NULL default '',
  mail_content mediumtext NOT NULL,
  mail_groups mediumtext NULL,
  mail_honor tinyint(1) NOT NULL default '1',
  mail_opts mediumtext NULL,
  mail_start int(10) NOT NULL default '0',
  mail_updated int(10) NOT NULL default '0',
  mail_sentto int(10) NOT NULL default '0',
  mail_active tinyint(1) NOT NULL default '0',
  mail_pergo smallint(5) NOT NULL default '0',
  PRIMARY KEY  (mail_id)
);";

$TABLE[] = "CREATE TABLE ibf_cache_store (
  cs_key varchar(255) NOT NULL default '',
  cs_value mediumtext NULL,
  cs_extra varchar(255) NOT NULL default '',
  cs_array tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (cs_key)
);";


$TABLE[] = "CREATE TABLE ibf_cal_calendars (
  cal_id int(10) unsigned NOT NULL auto_increment,
  cal_title varchar(255) NOT NULL default '0',
  cal_moderate tinyint(1) NOT NULL default '0',
  cal_position int(3) NOT NULL default '0',
  cal_event_limit int(2) unsigned NOT NULL default '0',
  cal_bday_limit int(2) unsigned NOT NULL default '0',
  cal_rss_export tinyint(1) NOT NULL default '0',
  cal_rss_export_days int(3) unsigned NOT NULL default '0',
  cal_rss_export_max tinyint(1) NOT NULL default '0',
  cal_rss_update int(3) unsigned NOT NULL default '0',
  cal_rss_update_last int(10) unsigned NOT NULL default '0',
  cal_rss_cache mediumtext NULL,
  cal_permissions mediumtext NULL,
  PRIMARY KEY  (cal_id)
);";

$TABLE[] = "CREATE TABLE ibf_cal_events (
  event_id int(10) unsigned NOT NULL auto_increment,
  event_calendar_id int(10) unsigned NOT NULL default '0',
  event_member_id mediumint(8) unsigned NOT NULL default '0',
  event_content mediumtext NULL,
  event_title varchar(255) NOT NULL default '',
  event_smilies tinyint(1) NOT NULL default '0',
  event_perms text NULL,
  event_private tinyint(1) NOT NULL default '0',
  event_approved tinyint(1) NOT NULL default '0',
  event_unixstamp int(10) unsigned NOT NULL default '0',
  event_recurring int(2) unsigned NOT NULL default '0',
  event_tz int(4) NOT NULL default '0',
  event_timeset VARCHAR( 6 ) DEFAULT '0' NOT NULL,
  event_unix_from int(10) unsigned NOT NULL default '0',
  event_unix_to int(10) unsigned NOT NULL default '0',
  event_all_day TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (event_id),
  KEY daterange (event_calendar_id,event_approved,event_unix_from,event_unix_to),
  KEY approved (event_calendar_id,event_approved)
);";


$TABLE[] = "CREATE TABLE ibf_components (
  com_id int(10) NOT NULL auto_increment,
  com_title varchar(255) NOT NULL default '',
  com_author varchar(255) NOT NULL default '',
  com_url varchar(255) NOT NULL default '',
  com_version varchar(255) NOT NULL default '',
  com_date_added int(10) NOT NULL default '0',
  com_menu_data mediumtext NULL,
  com_enabled tinyint(1) NOT NULL default '1',
  com_safemode tinyint(1) NOT NULL default '1',
  com_section varchar(255) NOT NULL default '',
  com_filename varchar(255) NOT NULL default '',
  com_description varchar(255) NOT NULL default '',
  com_url_title varchar(255) NOT NULL default '',
  com_url_uri varchar(255) NOT NULL default '',
  com_position int(3) NOT NULL default '10',
  PRIMARY KEY  (com_id)
);";

# MySQL 5: added default values
$TABLE[] = "CREATE TABLE ibf_conf_settings (
  conf_id int(10) NOT NULL auto_increment,
  conf_title varchar(255) NOT NULL default '',
  conf_description text NULL,
  conf_group smallint(3) NOT NULL default '0',
  conf_type varchar(255) NOT NULL default '',
  conf_key varchar(255) NOT NULL default '',
  conf_value text NULL,
  conf_default text NULL,
  conf_extra text NULL,
  conf_evalphp text NULL,
  conf_protected tinyint(1) NOT NULL default '0',
  conf_position smallint(3) NOT NULL default '0',
  conf_start_group varchar(255) NOT NULL default '',
  conf_end_group tinyint(1) NOT NULL default '0',
  conf_add_cache tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (conf_id)
);";

$TABLE[] = "CREATE TABLE ibf_conf_settings_titles (
  conf_title_id smallint(3) NOT NULL auto_increment,
  conf_title_title varchar(255) NOT NULL default '',
  conf_title_desc text NULL,
  conf_title_count smallint(3) NOT NULL default '0',
  conf_title_noshow tinyint(1) NOT NULL default '0',
  conf_title_keyword varchar(200) NOT NULL default '',
  conf_title_module	 varchar(200) NOT NULL default '',
  PRIMARY KEY  (conf_title_id)
);";

$TABLE[] = "CREATE TABLE ibf_contacts (
  id mediumint(8) NOT NULL auto_increment,
  contact_id mediumint(8) NOT NULL default '0',
  member_id mediumint(8) NOT NULL default '0',
  contact_name varchar(32) NOT NULL default '',
  allow_msg tinyint(1) default NULL,
  contact_desc varchar(50) default NULL,
  PRIMARY KEY  (id)
);";

$TABLE[] = "CREATE TABLE ibf_converge_local (
 converge_api_code	VARCHAR(32) NOT NULL default '',
 converge_product_id	INT(10) NOT NULL default '0',
 converge_added		INT(10) NOT NULL default '0',
 converge_ip_address	VARCHAR(16) NOT NULL default '',
 converge_url		VARCHAR(255) NOT NULL default '',
 converge_active		INT(1) NOT NULL default '0',
 converge_http_user  VARCHAR(255) NOT NULL default '',
 converge_http_pass	VARCHAR(255) NOT NULL default '',
 PRIMARY KEY (converge_api_code ),
 KEY converge_active (converge_active)
);";

$TABLE[] = "CREATE TABLE ibf_custom_bbcode (
  bbcode_id int(10) NOT NULL auto_increment,
  bbcode_title varchar(255) NOT NULL default '',
  bbcode_desc text NULL,
  bbcode_tag varchar(255) NOT NULL default '',
  bbcode_replace text NULL,
  bbcode_useoption tinyint(1) NOT NULL default '0',
  bbcode_example text NULL,
  bbcode_switch_option     INT(1) NOT NULL default '0',
  bbcode_add_into_menu     INT(1) NOT NULL default '0',
  bbcode_menu_option_text  VARCHAR(200) NOT NULL default '',
  bbcode_menu_content_text VARCHAR(200) NOT NULL default '',
  PRIMARY KEY  (bbcode_id)
);";

$TABLE[] = "CREATE TABLE ibf_dnames_change (
  dname_id int(10) NOT NULL auto_increment,
  dname_member_id int(8) NOT NULL default '0',
  dname_date int(10) NOT NULL default '0',
  dname_ip_address varchar(16) NOT NULL default '',
  dname_previous varchar(255) NOT NULL default '',
  dname_current varchar(255) NOT NULL default '',
  PRIMARY KEY  (dname_id),
  KEY dname_member_id (dname_member_id),
  KEY date_id (dname_member_id,dname_date)
);";

$TABLE[] = "CREATE TABLE ibf_email_logs (
  email_id int(10) NOT NULL auto_increment,
  email_subject varchar(255) NOT NULL default '',
  email_content text NULL,
  email_date int(10) NOT NULL default '0',
  from_member_id mediumint(8) NOT NULL default '0',
  from_email_address varchar(250) NOT NULL default '',
  from_ip_address varchar(16) NOT NULL default '127.0.0.1',
  to_member_id mediumint(8) NOT NULL default '0',
  to_email_address varchar(250) NOT NULL default '',
  topic_id int(10) NOT NULL default '0',
  PRIMARY KEY  (email_id),
  KEY from_member_id (from_member_id),
  KEY email_date (email_date)
);";

$TABLE[] = "CREATE TABLE ibf_emoticons (
  id smallint(3) NOT NULL auto_increment,
  typed varchar(32) NOT NULL default '',
  image varchar(128) NOT NULL default '',
  clickable smallint(2) NOT NULL default '1',
  emo_set varchar(64) NOT NULL default 'default',
  PRIMARY KEY  (id)
);";

$TABLE[] = "CREATE TABLE ibf_faq (
  id mediumint(8) NOT NULL auto_increment,
  title varchar(128) NOT NULL default '',
  text text NULL,
  description text NULL,
  position SMALLINT(3) DEFAULT '0' NOT NULL,
  PRIMARY KEY  (id)
);";

$TABLE[] = "CREATE TABLE ibf_forum_perms (
  perm_id int(10) NOT NULL auto_increment,
  perm_name varchar(250) NOT NULL default '',
  PRIMARY KEY  (perm_id)
);";

$TABLE[] = "CREATE TABLE ibf_forum_tracker (
  frid mediumint(8) NOT NULL auto_increment,
  member_id varchar(32) NOT NULL default '',
  forum_id smallint(5) NOT NULL default '0',
  start_date int(10) default NULL,
  last_sent int(10) NOT NULL default '0',
  forum_track_type varchar(100) NOT NULL default 'delayed',
  PRIMARY KEY  (frid)
);";

$TABLE[] = "CREATE TABLE ibf_forums (
  id smallint(5) NOT NULL default '0',
  topics mediumint(6) default NULL,
  posts mediumint(6) default NULL,
  last_post int(10) default NULL,
  last_poster_id mediumint(8) NOT NULL default '0',
  last_poster_name varchar(32) default NULL,
  name varchar(128) NOT NULL default '',
  description text NULL,
  position INT(5)  UNSIGNED default '0',
  use_ibc tinyint(1) default NULL,
  use_html tinyint(1) default NULL,
  status tinyint(1) default '1',
  password varchar(32) default NULL,
  password_override VARCHAR(255) default NULL,
  last_title varchar(250) default NULL,
  last_id int(10) default NULL,
  sort_key varchar(32) default NULL,
  sort_order varchar(32) default NULL,
  prune tinyint(3) default NULL,
  topicfilter VARCHAR( 32 ) DEFAULT 'all' NOT NULL, 
  show_rules tinyint(1) default NULL,
  preview_posts tinyint(1) default NULL,
  allow_poll tinyint(1) NOT NULL default '1',
  allow_pollbump tinyint(1) NOT NULL default '0',
  inc_postcount tinyint(1) NOT NULL default '1',
  skin_id int(10) default NULL,
  parent_id mediumint(5) default '-1',
  quick_reply tinyint(1) default '0',
  redirect_url varchar(250) default '',
  redirect_on tinyint(1) NOT NULL default '0',
  redirect_hits int(10) NOT NULL default '0',
  redirect_loc varchar(250) default '',
  rules_title varchar(255) NOT NULL default '',
  rules_text text NULL,
  topic_mm_id varchar(250) NOT NULL default '',
  notify_modq_emails text NULL,
  sub_can_post tinyint(1) default '1',
  permission_custom_error text NULL,
  permission_array mediumtext NULL,
  permission_showtopic tinyint(1) NOT NULL default '0',
  queued_topics mediumint(6) NOT NULL default '0',
  queued_posts mediumint(6) NOT NULL default '0',
  forum_allow_rating tinyint(1) NOT NULL default '0',
  forum_last_deletion int(10) NOT NULL default '0',
  newest_title VARCHAR( 250 ) default NULL,
  newest_id INT( 10 ) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY position (position,parent_id)
);";

$TABLE[] = "CREATE TABLE ibf_groups (
  g_id int(3) unsigned NOT NULL auto_increment,
  g_view_board tinyint(1) default NULL,
  g_mem_info tinyint(1) default NULL,
  g_other_topics tinyint(1) default NULL,
  g_use_search tinyint(1) default NULL,
  g_email_friend tinyint(1) default NULL,
  g_invite_friend tinyint(1) default NULL,
  g_edit_profile tinyint(1) default NULL,
  g_post_new_topics tinyint(1) default NULL,
  g_reply_own_topics tinyint(1) default NULL,
  g_reply_other_topics tinyint(1) default NULL,
  g_edit_posts tinyint(1) default NULL,
  g_delete_own_posts tinyint(1) default NULL,
  g_open_close_posts tinyint(1) default NULL,
  g_delete_own_topics tinyint(1) default NULL,
  g_post_polls tinyint(1) default NULL,
  g_vote_polls tinyint(1) default NULL,
  g_use_pm tinyint(1) default '0',
  g_is_supmod tinyint(1) default NULL,
  g_access_cp tinyint(1) default NULL,
  g_title varchar(32) NOT NULL default '',
  g_can_remove tinyint(1) default NULL,
  g_append_edit tinyint(1) default NULL,
  g_access_offline tinyint(1) default NULL,
  g_avoid_q tinyint(1) default NULL,
  g_avoid_flood tinyint(1) default NULL,
  g_icon text NULL,
  g_attach_max bigint(20) default NULL,
  g_avatar_upload tinyint(1) default '0',
  prefix varchar(250) default NULL,
  suffix varchar(250) default NULL,
  g_max_messages int(5) default '50',
  g_max_mass_pm int(5) default '0',
  g_search_flood mediumint(6) default '20',
  g_edit_cutoff int(10) default '0',
  g_promotion varchar(10) default '-1&-1',
  g_hide_from_list tinyint(1) default '0',
  g_post_closed tinyint(1) default '0',
  g_perm_id varchar(255) NOT NULL default '',
  g_photo_max_vars varchar(200) default '100:150:150',
  g_dohtml tinyint(1) NOT NULL default '0',
  g_edit_topic tinyint(1) NOT NULL default '0',
  g_email_limit varchar(15) NOT NULL default '10:15',
  g_bypass_badwords tinyint(1) NOT NULL default '0',
  g_can_msg_attach tinyint(1) NOT NULL default '0',
  g_attach_per_post int(10) NOT NULL default '0',
  g_topic_rate_setting smallint(2) NOT NULL default '0',
  g_dname_changes INT(3) NOT NULL default '0',
  g_dname_date    INT(5) NOT NULL default '0',
  PRIMARY KEY  (g_id)
);";

$TABLE[] = "CREATE TABLE ibf_languages (
  lid mediumint(8) NOT NULL auto_increment,
  ldir varchar(64) NOT NULL default '',
  lname varchar(250) NOT NULL default '',
  lauthor varchar(250) default NULL,
  lemail varchar(250) default NULL,
  PRIMARY KEY  (lid)
);";

$TABLE[] = "CREATE TABLE ibf_login_methods (
  login_id int(10) NOT NULL auto_increment,
  login_title varchar(255) NOT NULL default '',
  login_description varchar(255) NOT NULL default '',
  login_folder_name varchar(255) NOT NULL default '',
  login_maintain_url varchar(255) NOT NULL default '',
  login_register_url varchar(255) NOT NULL default '',
  login_type varchar(30) NOT NULL default '',
  login_alt_login_html text NULL,
  login_date int(10) NOT NULL default '0',
  login_settings int(1) NOT NULL default '0',
  login_enabled int(1) NOT NULL default '0',
  login_safemode int(1) NOT NULL default '0',
  login_installed int(1) NOT NULL default '0',
  login_replace_form int(1) NOT NULL default '0',
  login_allow_create int(1) NOT NULL default '0',
  login_user_id VARCHAR(255) NOT NULL default 'username',
  login_login_url VARCHAR(255) NOT NULL default '',
  login_logout_url	VARCHAR(255) NOT NULL default '',
  PRIMARY KEY  (login_id)
);";

$TABLE[] = "CREATE TABLE ibf_mail_error_logs (
  mlog_id int(10) NOT NULL auto_increment,
  mlog_date int(10) NOT NULL default '0',
  mlog_to varchar(250) NOT NULL default '',
  mlog_from varchar(250) NOT NULL default '',
  mlog_subject varchar(250) NOT NULL default '',
  mlog_content varchar(250) NOT NULL default '',
  mlog_msg text NULL,
  mlog_code varchar(200) NOT NULL default '',
  mlog_smtp_msg text NULL,
  PRIMARY KEY  (mlog_id)
);";

$TABLE[] = "CREATE TABLE ibf_mail_queue (
  mail_id int(10) NOT NULL auto_increment,
  mail_date int(10) NOT NULL default '0',
  mail_to varchar(255) NOT NULL default '',
  mail_from varchar(255) NOT NULL default '',
  mail_subject text NULL,
  mail_content text NULL,
  mail_type varchar(200) NOT NULL default '',
  mail_html_on	INT(1) NOT NULL default '0',
  PRIMARY KEY  (mail_id)
);";


$TABLE[] = "CREATE TABLE ibf_member_extra (
  id mediumint(8) NOT NULL default '0',
  notes text NULL,
  links text NULL,
  bio text NULL,
  ta_size char(3) default NULL,
  photo_type varchar(10) default '',
  photo_location varchar(255) default '',
  photo_dimensions varchar(200) default '',
  aim_name varchar(40) NOT NULL default '',
  icq_number int(15) NOT NULL default '0',
  website varchar(250) NOT NULL default '',
  yahoo varchar(40) NOT NULL default '',
  interests text NULL,
  msnname varchar(200) NOT NULL default '',
  vdirs text NULL,
  location varchar(250) NOT NULL default '',
  signature text NULL,
  avatar_location varchar(255) NOT NULL default '',
  avatar_size varchar(9) NOT NULL default '',
  avatar_type varchar(15) NOT NULL default 'local',
  PRIMARY KEY  (id)
);";

$TABLE[] = "CREATE TABLE ibf_members (
  id mediumint(8) NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  mgroup smallint(3) NOT NULL default '0',
  email varchar(150) NOT NULL default '',
  joined int(10) NOT NULL default '0',
  ip_address varchar(16) NOT NULL default '',
  posts mediumint(7) default '0',
  title varchar(64) default NULL,
  allow_admin_mails tinyint(1) default NULL,
  time_offset varchar(10) default NULL,
  hide_email varchar(8) default NULL,
  email_pm tinyint(1) default '1',
  email_full tinyint(1) default NULL,
  skin smallint(5) default NULL,
  warn_level int(10) default NULL,
  warn_lastwarn int(10) NOT NULL default '0',
  language varchar(32) default NULL,
  last_post int(10) default NULL,
  restrict_post varchar(100) NOT NULL default '0',
  view_sigs tinyint(1) default '1',
  view_img tinyint(1) default '1',
  view_avs tinyint(1) default '1',
  view_pop tinyint(1) default '1',
  bday_day int(2) default NULL,
  bday_month int(2) default NULL,
  bday_year int(4) default NULL,
  new_msg tinyint(2) default '0',
  msg_total smallint(5) default '0',
  show_popup tinyint(1) default '0',
  misc varchar(128) default NULL,
  last_visit int(10) default '0',
  last_activity int(10) default '0',
  dst_in_use tinyint(1) default '0',
  view_prefs varchar(64) default '-1&-1',
  coppa_user tinyint(1) default '0',
  mod_posts varchar(100) NOT NULL default '0',
  auto_track varchar(50) default '0',
  temp_ban varchar(100) default '0',
  sub_end int(10) NOT NULL default '0',
  login_anonymous char(3) NOT NULL default '0&0',
  ignored_users text NULL,
  mgroup_others varchar(255) NOT NULL default '',
  org_perm_id varchar(255) NOT NULL default '',
  member_login_key varchar(32) NOT NULL default '',
  member_login_key_expire	INT(10) NOT NULL default '0',
  subs_pkg_chosen smallint(3) NOT NULL default '0',
  has_blog tinyint(1) NOT NULL default '0',
  has_gallery tinyint(1) NOT NULL default '0',
  members_markers text NULL,
  members_editor_choice char(3) NOT NULL default 'std',
  members_auto_dst tinyint(1) NOT NULL default '1',
  members_display_name varchar(255) NOT NULL default '',
  members_created_remote tinyint(1) NOT NULL default '0',
  members_cache MEDIUMTEXT NULL,
  members_disable_pm INT(1) NOT NULL default '0',
  members_l_display_name VARCHAR(255) NOT NULL default '0',
  members_l_username 	 VARCHAR(255) NOT NULL default '0',
  failed_logins TEXT NULL,
  failed_login_count SMALLINT(3) DEFAULT '0' NOT NULL,
  members_profile_views INT(10) UNSIGNED NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY members_l_display_name (members_l_display_name),
  KEY members_l_username (members_l_username),
  KEY mgroup (mgroup),
  KEY bday_day (bday_day),
  KEY bday_month (bday_month)
);";

$TABLE[] = "CREATE TABLE ibf_members_converge (
  converge_id int(10) NOT NULL auto_increment,
  converge_email varchar(250) NOT NULL default '',
  converge_joined int(10) NOT NULL default '0',
  converge_pass_hash varchar(32) NOT NULL default '',
  converge_pass_salt varchar(5) NOT NULL default '',
  PRIMARY KEY  (converge_id),
  KEY converge_email (converge_email)
);";

$TABLE[] = "CREATE TABLE ibf_members_partial (
  partial_id int(10) NOT NULL auto_increment,
  partial_member_id int(8) NOT NULL default '0',
  partial_date int(10) NOT NULL default '0',
  partial_email_ok INT(1) NOT NULL default '0',
  PRIMARY KEY  (partial_id),
  KEY partial_member_id (partial_member_id)
);";

$TABLE[] = "CREATE TABLE ibf_message_text (
  msg_id int(10) NOT NULL auto_increment,
  msg_date int(10) default NULL,
  msg_post text NULL,
  msg_cc_users text NULL,
  msg_sent_to_count smallint(5) NOT NULL default '0',
  msg_deleted_count smallint(5) NOT NULL default '0',
  msg_post_key varchar(32) NOT NULL default '0',
  msg_author_id mediumint(8) NOT NULL default '0',
  msg_ip_address VARCHAR(16) NOT NULL default '0',
  PRIMARY KEY  (msg_id),
  KEY msg_date (msg_date),
  KEY msg_sent_to_count (msg_sent_to_count),
  KEY msg_deleted_count (msg_deleted_count)
);";

$TABLE[] = "CREATE TABLE ibf_message_topics (
  mt_id int(10) NOT NULL auto_increment,
  mt_msg_id int(10) NOT NULL default '0',
  mt_date int(10) NOT NULL default '0',
  mt_title varchar(255) NOT NULL default '',
  mt_from_id mediumint(8) NOT NULL default '0',
  mt_to_id mediumint(8) NOT NULL default '0',
  mt_vid_folder varchar(32) NOT NULL default '',
  mt_read tinyint(1) NOT NULL default '0',
  mt_hasattach smallint(5) NOT NULL default '0',
  mt_hide_cc tinyint(1) default '0',
  mt_tracking tinyint(1) default '0',
  mt_addtosent TINYINT( 1 ) DEFAULT '0' NOT NULL, 
  mt_owner_id mediumint(8) NOT NULL default '0',
  mt_user_read int(10) default '0',
  PRIMARY KEY  (mt_id),
  KEY mt_from_id (mt_from_id),
  KEY mt_owner_id (mt_owner_id,mt_to_id,mt_vid_folder,mt_date)
);";

$TABLE[] = "CREATE TABLE ibf_moderator_logs (
  id int(10) NOT NULL auto_increment,
  forum_id int(5) default '0',
  topic_id int(10) NOT NULL default '0',
  post_id int(10) default NULL,
  member_id mediumint(8) NOT NULL default '0',
  member_name varchar(32) NOT NULL default '',
  ip_address varchar(16) NOT NULL default '0',
  http_referer varchar(255) default NULL,
  ctime int(10) default NULL,
  topic_title varchar(128) default NULL,
  action varchar(128) default NULL,
  query_string varchar(128) default NULL,
  PRIMARY KEY  (id)
);";

$TABLE[] = "CREATE TABLE ibf_moderators (
  mid mediumint(8) NOT NULL auto_increment,
  forum_id int(5) NOT NULL default '0',
  member_name varchar(32) NOT NULL default '',
  member_id mediumint(8) NOT NULL default '0',
  edit_post tinyint(1) default NULL,
  edit_topic tinyint(1) default NULL,
  delete_post tinyint(1) default NULL,
  delete_topic tinyint(1) default NULL,
  view_ip tinyint(1) default NULL,
  open_topic tinyint(1) default NULL,
  close_topic tinyint(1) default NULL,
  mass_move tinyint(1) default NULL,
  mass_prune tinyint(1) default NULL,
  move_topic tinyint(1) default NULL,
  pin_topic tinyint(1) default NULL,
  unpin_topic tinyint(1) default NULL,
  post_q tinyint(1) default NULL,
  topic_q tinyint(1) default NULL,
  allow_warn tinyint(1) default NULL,
  edit_user tinyint(1) NOT NULL default '0',
  is_group tinyint(1) default '0',
  group_id smallint(3) default NULL,
  group_name varchar(200) default NULL,
  split_merge tinyint(1) default '0',
  can_mm tinyint(1) NOT NULL default '0',
  mod_can_set_open_time tinyint(1) NOT NULL default '0',
  mod_can_set_close_time tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (mid),
  KEY forum_id (forum_id),
  KEY group_id (group_id),
  KEY member_id (member_id)
);";

$TABLE[] = "CREATE TABLE ibf_profile_portal (
 pp_member_id                    INT(10) NOT NULL default '0',
 pp_profile_update               INT(10) UNSIGNED NOT NULL default '0',
 pp_bio_content                  TEXT NULL,
 pp_last_visitors                TEXT NULL ,
 pp_comment_count                INT(10) UNSIGNED NOT NULL default '0',
 pp_rating_hits                  INT(10) UNSIGNED NOT NULL default '0',
 pp_rating_value                 INT(10) UNSIGNED NOT NULL default '0',
 pp_rating_real                  INT(10) UNSIGNED NOT NULL default '0',
 pp_friend_count                 INT(5) UNSIGNED NOT NULL default '0',
 pp_main_photo                   VARCHAR(255) NOT NULL default '',
 pp_main_width                   INT(5) UNSIGNED NOT NULL default '0',
 pp_main_height                  INT(5) UNSIGNED NOT NULL default '0',
 pp_thumb_photo                  VARCHAR(255) NOT NULL default '',
 pp_thumb_width                  INT(5) UNSIGNED NOT NULL default '0',
 pp_thumb_height                 INT(5) UNSIGNED NOT NULL default '0',
 pp_gender                       VARCHAR(10) NOT NULL default '',
 pp_setting_notify_comments      VARCHAR(10) NOT NULL default 'email',
 pp_setting_notify_friend        VARCHAR(10) NOT NULL default 'email',
 pp_setting_moderate_comments    TINYINT(1) NOT NULL default '0',
 pp_setting_moderate_friends     TINYINT(1) NOT NULL default '0',
 pp_setting_count_friends        INT(2) NOT NULL default '0',
 pp_setting_count_comments       INT(2) NOT NULL default '0',
 pp_setting_count_visitors       INT(2) NOT NULL default '0',
 pp_profile_views				INT(10) NOT NULL default '0',
 PRIMARY KEY ( pp_member_id )
);";

$TABLE[] = "CREATE TABLE ibf_profile_ratings (
 rating_id				INT(10) NOT NULL auto_increment,
 rating_for_member_id	INT(10) NOT NULL default '0',
 rating_by_member_id		INT(10) NOT NULL default '0',
 rating_added			INT(10) NOT NULL default '0',
 rating_ip_address		VARCHAR(16) NOT NULL default '',
 rating_value			INT(2) NOT NULL default '0',
 PRIMARY KEY ( rating_id ),
 KEY rating_for_member_id ( rating_for_member_id ) 
);";

$TABLE[] = "CREATE TABLE ibf_profile_portal_views (
  views_member_id int(10) NOT NULL default '0'
);";

$TABLE[] = "CREATE TABLE ibf_profile_friends (
 friends_id			INT(10) NOT NULL auto_increment,
 friends_member_id	INT(10) UNSIGNED NOT NULL default '0',
 friends_friend_id	INT(10) UNSIGNED NOT NULL default '0',
 friends_approved	TINYINT(1) NOT NULL default '0',
 friends_added		INT(10) UNSIGNED NOT NULL default '0',
 PRIMARY KEY( friends_id ),
 KEY my_friends ( friends_member_id, friends_friend_id ),
 KEY friends_member_id ( friends_member_id )
);";

$TABLE[] = "CREATE TABLE ibf_profile_comments (
 comment_id				INT(10) NOT NULL auto_increment,
 comment_for_member_id	INT(10) UNSIGNED NOT NULL default '0',
 comment_by_member_id	INT(10) UNSIGNED NOT NULL default '0',
 comment_date			INT(10) UNSIGNED NOT NULL default '0',
 comment_ip_address		VARCHAR(16) NOT NULL default '0',
 comment_content			TEXT NULL,
 comment_approved		TINYINT(1) NOT NULL default '0',
 PRIMARY KEY( comment_id ),
 KEY my_comments( comment_for_member_id,comment_date )
);";

$TABLE[] = "CREATE TABLE ibf_pfields_content (
  member_id mediumint(8) NOT NULL default '0',
  updated int(10) default '0',
  PRIMARY KEY  (member_id)
);";

$TABLE[] = "CREATE TABLE ibf_pfields_data (
  pf_id smallint(5) NOT NULL auto_increment,
  pf_title varchar(250) NOT NULL default '',
  pf_desc varchar(250) NOT NULL default '',
  pf_content text NULL,
  pf_type varchar(250) NOT NULL default '',
  pf_not_null tinyint(1) NOT NULL default '0',
  pf_member_hide tinyint(1) NOT NULL default '0',
  pf_max_input smallint(6) NOT NULL default '0',
  pf_member_edit tinyint(1) NOT NULL default '0',
  pf_position smallint(6) NOT NULL default '0',
  pf_show_on_reg tinyint(1) NOT NULL default '0',
  pf_input_format text NULL,
  pf_admin_only tinyint(1) NOT NULL default '0',
  pf_topic_format text NULL,
  PRIMARY KEY  (pf_id)
);";

$TABLE[] = "CREATE TABLE ibf_polls (
  pid mediumint(8) NOT NULL auto_increment,
  tid int(10) NOT NULL default '0',
  start_date int(10) default NULL,
  choices text NULL,
  starter_id mediumint(8) NOT NULL default '0',
  votes smallint(5) NOT NULL default '0',
  forum_id smallint(5) NOT NULL default '0',
  poll_question varchar(255) default NULL,
  poll_only TINYINT(1) DEFAULT '0' NOT NULL,
  PRIMARY KEY  (pid)
);";

$TABLE[] = "CREATE TABLE ibf_posts (
  pid int(10) NOT NULL auto_increment,
  append_edit tinyint(1) default '0',
  edit_time int(10) default NULL,
  author_id mediumint(8) NOT NULL default '0',
  author_name varchar(32) default NULL,
  use_sig tinyint(1) NOT NULL default '0',
  use_emo tinyint(1) NOT NULL default '0',
  ip_address varchar(16) NOT NULL default '',
  post_date int(10) default NULL,
  icon_id smallint(3) default NULL,
  post mediumtext NULL,
  queued tinyint(1) NOT NULL default '0',
  topic_id int(10) NOT NULL default '0',
  post_title varchar(255) default NULL,
  new_topic tinyint(1) default '0',
  edit_name varchar(255) default NULL,
  post_key varchar(32) NOT NULL default '0',
  post_parent int(10) NOT NULL default '0',
  post_htmlstate smallint(1) NOT NULL default '0',
  post_edit_reason VARCHAR(255) NOT NULL default '',
  PRIMARY KEY  (pid),
  KEY topic_id (topic_id,queued,pid,post_date),
  KEY author_id (author_id,topic_id),
  KEY post_date (post_date),
  KEY ip_address (ip_address),
  KEY post_key (post_key)
);";

$TABLE[] = "CREATE TABLE ibf_reg_antispam (
  regid varchar(32) NOT NULL default '',
  regcode varchar(8) NOT NULL default '',
  ip_address varchar(32) default NULL,
  ctime int(10) default NULL,
  PRIMARY KEY  (regid)
);";

$TABLE[] = "CREATE TABLE ibf_rss_export (
  rss_export_id int(10) NOT NULL auto_increment,
  rss_export_enabled tinyint(1) NOT NULL default '0',
  rss_export_title varchar(255) NOT NULL default '',
  rss_export_desc varchar(255) NOT NULL default '',
  rss_export_image varchar(255) NOT NULL default '',
  rss_export_forums text NULL,
  rss_export_include_post tinyint(1) NOT NULL default '0',
  rss_export_count smallint(3) NOT NULL default '0',
  rss_export_cache_time smallint(3) NOT NULL default '30',
  rss_export_cache_last int(10) NOT NULL default '0',
  rss_export_cache_content mediumtext NULL,
  rss_export_sort varchar(4) NOT NULL default 'DESC',
  rss_export_order varchar(20) NOT NULL default 'start_date',
  PRIMARY KEY  (rss_export_id)
);";

$TABLE[] = "CREATE TABLE ibf_rss_import (
  rss_import_id int(10) NOT NULL auto_increment,
  rss_import_enabled tinyint(1) NOT NULL default '0',
  rss_import_title varchar(255) NOT NULL default '',
  rss_import_url varchar(255) NOT NULL default '',
  rss_import_forum_id int(10) NOT NULL default '0',
  rss_import_mid mediumint(8) NOT NULL default '0',
  rss_import_pergo smallint(3) NOT NULL default '0',
  rss_import_time smallint(3) NOT NULL default '0',
  rss_import_last_import int(10) NOT NULL default '0',
  rss_import_showlink varchar(255) NOT NULL default '0',
  rss_import_topic_open tinyint(1) NOT NULL default '0',
  rss_import_topic_hide tinyint(1) NOT NULL default '0',
  rss_import_inc_pcount tinyint(1) NOT NULL default '0',
  rss_import_topic_pre varchar(50) NOT NULL default '',
  rss_import_charset VARCHAR(200) NOT NULL default '',
  rss_import_allow_html TINYINT(1) NOT NULL default '0',
  rss_import_auth TINYINT( 1 ) DEFAULT '0' NOT NULL ,
  rss_import_auth_user VARCHAR( 255 ) DEFAULT 'Not Needed' NOT NULL,
  rss_import_auth_pass VARCHAR( 255 ) DEFAULT 'Not Needed' NOT NULL,
  PRIMARY KEY  (rss_import_id)
);";

$TABLE[] = "CREATE TABLE ibf_rss_imported (
  rss_imported_guid char(32) NOT NULL default '0',
  rss_imported_tid int(10) NOT NULL default '0',
  rss_imported_impid int(10) NOT NULL default '0',
  PRIMARY KEY  (rss_imported_guid),
  KEY rss_imported_impid (rss_imported_impid)
);";


$TABLE[] = "CREATE TABLE ibf_search_results (
  id varchar(32) NOT NULL default '',
  topic_id text NULL,
  search_date int(12) NOT NULL default '0',
  topic_max int(3) NOT NULL default '0',
  sort_key varchar(32) NOT NULL default 'last_post',
  sort_order varchar(4) NOT NULL default 'desc',
  member_id mediumint(10) default '0',
  ip_address varchar(64) default NULL,
  post_id text NULL,
  post_max int(10) NOT NULL default '0',
  query_cache text NULL,
  PRIMARY KEY  (id),
  KEY search_date (search_date)
);";

$TABLE[] = "CREATE TABLE ibf_sessions (
  id varchar(60) NOT NULL default '0',
  member_name varchar(64) default NULL,
  member_id mediumint(8) NOT NULL default '0',
  ip_address varchar(16) default NULL,
  browser VARCHAR(200) NOT NULL default '',
  running_time int(10) default NULL,
  login_type char(3) default '',
  location varchar(40) default NULL,
  member_group smallint(3) default NULL,
  in_error tinyint(1) NOT NULL default '0',
  location_1_type varchar(10) NOT NULL default '',
  location_1_id int(10) NOT NULL default '0',
  location_2_type varchar(10) NOT NULL default '',
  location_2_id int(10) NOT NULL default '0',
  location_3_type varchar(10) NOT NULL default '',
  location_3_id int(10) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY location1 (location_1_type,location_1_id),
  KEY location2 (location_2_type,location_2_id),
  KEY location3 (location_3_type,location_3_id),
  KEY running_time (running_time)
);";

$TABLE[] = "CREATE TABLE ibf_skin_macro (
  macro_id smallint(3) NOT NULL auto_increment,
  macro_value varchar(200) default NULL,
  macro_replace text NULL,
  macro_can_remove tinyint(1) default '0',
  macro_set smallint(3) NOT NULL default '0',
  PRIMARY KEY  (macro_id),
  KEY macro_set (macro_set)
);";

$TABLE[] = "CREATE TABLE ibf_skin_sets (
  set_skin_set_id int(10) NOT NULL auto_increment,
  set_name varchar(150) NOT NULL default '',
  set_image_dir varchar(200) NOT NULL default '',
  set_hidden tinyint(1) NOT NULL default '0',
  set_default tinyint(1) NOT NULL default '0',
  set_css_method varchar(100) NOT NULL default 'inline',
  set_skin_set_parent smallint(5) NOT NULL default '-1',
  set_author_email varchar(255) NOT NULL default '',
  set_author_name varchar(255) NOT NULL default '',
  set_author_url varchar(255) NOT NULL default '',
  set_css mediumtext NULL,
  set_cache_macro mediumtext NULL,
  set_wrapper mediumtext NULL,
  set_css_updated int(10) NOT NULL default '0',
  set_cache_css mediumtext NULL,
  set_cache_wrapper mediumtext NULL,
  set_emoticon_folder varchar(60) NOT NULL default 'default',
  set_key VARCHAR( 32 ) NULL,
  set_protected TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (set_skin_set_id),
  KEY (set_key)
);";

$TABLE[] = "CREATE TABLE ibf_skin_url_mapping (
 map_id			INT(10) NOT NULL auto_increment,
 map_title		VARCHAR(200) NOT NULL default '',
 map_match_type	VARCHAR(10) NOT NULL default 'contains',
 map_url			VARCHAR(200) NOT NULL default '',
 map_skin_set_id	INT(10) UNSIGNED NOT NULL default '0',
 map_date_added	INT(10) UNSIGNED NOT NULL default '0',
 PRIMARY KEY (map_id)
);";

$TABLE[] = "CREATE TABLE ibf_skin_templates (
  suid int(10) NOT NULL auto_increment,
  set_id int(10) NOT NULL default '0',
  group_name varchar(255) NOT NULL default '',
  section_content mediumtext NULL,
  func_name varchar(255) default NULL,
  func_data text NULL,
  updated int(10) default NULL,
  group_names_secondary TEXT NULL,
  can_remove tinyint(4) default '0',
  PRIMARY KEY  (suid)
);";

$TABLE[] = "CREATE TABLE ibf_skin_template_links (
 link_id				INT(10) UNSIGNED NOT NULL auto_increment,
 link_set_id			INT(10) UNSIGNED NOT NULL default '0',
 link_group_name		VARCHAR(255) NOT NULL default '',
 link_template_name	VARCHAR(255) NOT NULL default '',
 link_used_in		VARCHAR(255) NOT NULL default '',
 PRIMARY KEY (link_id)
);";

$TABLE[] = "CREATE TABLE ibf_skin_templates_cache (
  template_id varchar(32) NOT NULL default '',
  template_group_name varchar(255) NOT NULL default '',
  template_group_content mediumtext NULL,
  template_set_id int(10) NOT NULL default '0',
  PRIMARY KEY  (template_id),
  KEY template_set_id (template_set_id),
  KEY template_group_name (template_group_name)
);";

$TABLE[] = "CREATE TABLE ibf_spider_logs (
  sid int(10) NOT NULL auto_increment,
  bot varchar(255) NOT NULL default '',
  query_string text NULL,
  entry_date int(10) NOT NULL default '0',
  ip_address varchar(16) NOT NULL default '',
  PRIMARY KEY  (sid)
);";

$TABLE[] = "CREATE TABLE ibf_subscription_currency (
  subcurrency_code varchar(10) NOT NULL default '',
  subcurrency_desc varchar(250) NOT NULL default '',
  subcurrency_exchange decimal(16,8) NOT NULL default '0.00000000',
  subcurrency_default tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (subcurrency_code)
);";

$TABLE[] = "CREATE TABLE ibf_subscription_extra (
  subextra_id smallint(5) NOT NULL auto_increment,
  subextra_sub_id smallint(5) NOT NULL default '0',
  subextra_method_id smallint(5) NOT NULL default '0',
  subextra_product_id varchar(250) NOT NULL default '0',
  subextra_can_upgrade tinyint(1) NOT NULL default '0',
  subextra_recurring tinyint(1) NOT NULL default '0',
  subextra_custom_1 text NULL,
  subextra_custom_2 text NULL,
  subextra_custom_3 text NULL,
  subextra_custom_4 text NULL,
  subextra_custom_5 text NULL,
  PRIMARY KEY  (subextra_id)
);";

$TABLE[] = "CREATE TABLE ibf_subscription_logs (
  sublog_id int(10) NOT NULL auto_increment,
  sublog_date int(10) NOT NULL default '0',
  sublog_member_id mediumint(8) NOT NULL default '0',
  sublog_transid int(10) NOT NULL default '0',
  sublog_ipaddress varchar(16) NOT NULL default '',
  sublog_data text NULL,
  sublog_postdata text NULL,
  PRIMARY KEY  (sublog_id)
);";

$TABLE[] = "CREATE TABLE ibf_subscription_methods (
  submethod_id smallint(5) NOT NULL auto_increment,
  submethod_title varchar(250) NOT NULL default '',
  submethod_name varchar(20) NOT NULL default '',
  submethod_email varchar(250) NOT NULL default '',
  submethod_sid text NULL,
  submethod_custom_1 text NULL,
  submethod_custom_2 text NULL,
  submethod_custom_3 text NULL,
  submethod_custom_4 text NULL,
  submethod_custom_5 text NULL,
  submethod_is_cc tinyint(1) NOT NULL default '0',
  submethod_is_auto tinyint(1) NOT NULL default '0',
  submethod_desc text NULL,
  submethod_logo text NULL,
  submethod_active tinyint(1) NOT NULL default '0',
  submethod_use_currency varchar(10) NOT NULL default 'USD',
  PRIMARY KEY  (submethod_id)
);";

$TABLE[] = "CREATE TABLE ibf_subscription_trans (
  subtrans_id int(10) NOT NULL auto_increment,
  subtrans_sub_id smallint(5) NOT NULL default '0',
  subtrans_member_id mediumint(8) NOT NULL default '0',
  subtrans_old_group smallint(5) NOT NULL default '0',
  subtrans_paid decimal(10,2) NOT NULL default '0.00',
  subtrans_cumulative decimal(10,2) NOT NULL default '0.00',
  subtrans_method varchar(20) NOT NULL default '',
  subtrans_start_date varchar(13) NOT NULL default '0',
  subtrans_end_date varchar(13) NOT NULL default '0',
  subtrans_state varchar(200) NOT NULL default '',
  subtrans_trxid varchar(200) NOT NULL default '',
  subtrans_subscrid varchar(200) NOT NULL default '',
  subtrans_currency varchar(10) NOT NULL default 'USD',
  PRIMARY KEY  (subtrans_id)
);";

$TABLE[] = "CREATE TABLE ibf_subscriptions (
  sub_id smallint(5) NOT NULL auto_increment,
  sub_title varchar(250) NOT NULL default '',
  sub_desc text NULL,
  sub_new_group mediumint(8) NOT NULL default '0',
  sub_length smallint(5) NOT NULL default '1',
  sub_unit char(2) NOT NULL default 'm',
  sub_cost decimal(10,2) NOT NULL default '0.00',
  sub_run_module varchar(250) NOT NULL default '',
  PRIMARY KEY  (sub_id)
);";

$TABLE[] = "CREATE TABLE ibf_task_logs (
  log_id int(10) NOT NULL auto_increment,
  log_title varchar(255) NOT NULL default '',
  log_date int(10) NOT NULL default '0',
  log_ip varchar(16) NOT NULL default '0',
  log_desc text NULL,
  PRIMARY KEY  (log_id)
);";

$TABLE[] = "CREATE TABLE ibf_task_manager (
  task_id int(10) NOT NULL auto_increment,
  task_title varchar(255) NOT NULL default '',
  task_file varchar(255) NOT NULL default '',
  task_next_run int(10) NOT NULL default '0',
  task_week_day tinyint(1) NOT NULL default '-1',
  task_month_day smallint(2) NOT NULL default '-1',
  task_hour smallint(2) NOT NULL default '-1',
  task_minute smallint(2) NOT NULL default '-1',
  task_cronkey varchar(32) NOT NULL default '',
  task_log tinyint(1) NOT NULL default '0',
  task_description text NULL,
  task_enabled tinyint(1) NOT NULL default '1',
  task_key varchar(30) NOT NULL default '',
  task_safemode tinyint(1) NOT NULL default '0',
  task_locked int(10) NOT NULL default '0',
  PRIMARY KEY  (task_id),
  KEY task_next_run (task_next_run)
);";

$TABLE[] = "CREATE TABLE ibf_templates_diff_import (
 diff_key			VARCHAR(255) NOT NULL,
 diff_func_group		VARCHAR(150) NOT NULL,
 diff_func_name		VARCHAR(250) NOT NULL,
 diff_func_data		TEXT NULL,
 diff_func_content	MEDIUMTEXT NULL,
 diff_session_id		INT(10) NOT NULL default '0',
 PRIMARY KEY (diff_key),
 KEY diff_func_group (diff_func_group),
 KEY diff_func_name (diff_func_name)
);";

$TABLE[] = "CREATE TABLE ibf_template_diff_session (
 diff_session_id				INT(10) NOT NULL auto_increment,
 diff_session_togo			INT(10) NOT NULL default '0',
 diff_session_done			INT(10) NOT NULL default '0',
 diff_session_updated		INT(10) NOT NULL default '0',
 diff_session_title			VARCHAR(255) NOT NULL default '',
 diff_session_ignore_missing INT(1) NOT NULL default '0',
 PRIMARY KEY (diff_session_id)
);";

$TABLE[] = "CREATE TABLE ibf_template_diff_changes (
 diff_change_key			VARCHAR(255) NOT NULL,
 diff_change_func_group	VARCHAR(150) NOT NULL,
 diff_change_func_name	VARCHAR(250) NOT NULL,
 diff_change_content		MEDIUMTEXT NULL,
 diff_change_type		INT(1) NOT NULL default '0',
 diff_session_id		    INT(10) NOT NULL default '0',
 PRIMARY KEY (diff_change_key),
 KEY diff_change_func_group (diff_change_func_group),
 KEY diff_change_type (diff_change_type)
);";


$TABLE[] = "CREATE TABLE ibf_titles (
  id smallint(5) NOT NULL auto_increment,
  posts int(10) default NULL,
  title varchar(128) default NULL,
  pips varchar(128) default NULL,
  PRIMARY KEY  (id),
  KEY posts (posts)
);";

$TABLE[] = "CREATE TABLE ibf_topic_markers (
  marker_member_id int(8) NOT NULL default '0',
  marker_forum_id int(10) NOT NULL default '0',
  marker_last_update int(10) NOT NULL default '0',
  marker_unread smallint(5) NOT NULL default '0',
  marker_topics_read text NULL,
  marker_last_cleared int(10) NOT NULL default '0',
  UNIQUE KEY marker_forum_id (marker_forum_id,marker_member_id),
  KEY marker_member_id (marker_member_id)
);";

$TABLE[] = "CREATE TABLE ibf_topic_mmod (
  mm_id smallint(5) NOT NULL auto_increment,
  mm_title varchar(250) NOT NULL default '',
  mm_enabled tinyint(1) NOT NULL default '0',
  topic_state varchar(10) NOT NULL default 'leave',
  topic_pin varchar(10) NOT NULL default 'leave',
  topic_move smallint(5) NOT NULL default '0',
  topic_move_link tinyint(1) NOT NULL default '0',
  topic_title_st varchar(250) NOT NULL default '',
  topic_title_end varchar(250) NOT NULL default '',
  topic_reply tinyint(1) NOT NULL default '0',
  topic_reply_content text NULL,
  topic_reply_postcount tinyint(1) NOT NULL default '0',
  mm_forums text NULL,
  topic_approve tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (mm_id)
);";

$TABLE[] = "CREATE TABLE ibf_topic_ratings (
  rating_id int(10) NOT NULL auto_increment,
  rating_tid int(10) NOT NULL default '0',
  rating_member_id mediumint(8) NOT NULL default '0',
  rating_value smallint(6) NOT NULL default '0',
  rating_ip_address varchar(16) NOT NULL default '',
  PRIMARY KEY  (rating_id),
  KEY rating_tid (rating_tid,rating_member_id)
);";

$TABLE[] = "CREATE TABLE ibf_topic_views (
  views_tid int(10) NOT NULL default '0'
);";

$TABLE[] = "CREATE TABLE ibf_topics (
  tid int(10) NOT NULL auto_increment,
  title varchar(250) NOT NULL default '',
  description varchar(70) default NULL,
  state varchar(8) default NULL,
  posts int(10) default NULL,
  starter_id mediumint(8) NOT NULL default '0',
  start_date int(10) default NULL,
  last_poster_id mediumint(8) NOT NULL default '0',
  last_post int(10) default NULL,
  icon_id tinyint(2) default NULL,
  starter_name varchar(32) default NULL,
  last_poster_name varchar(32) default NULL,
  poll_state varchar(8) default NULL,
  last_vote int(10) default NULL,
  views int(10) default NULL,
  forum_id smallint(5) NOT NULL default '0',
  approved tinyint(1) NOT NULL default '0',
  author_mode tinyint(1) default NULL,
  pinned tinyint(1) default NULL,
  moved_to varchar(64) default NULL,
  total_votes int(5) NOT NULL default '0',
  topic_hasattach smallint(5) NOT NULL default '0',
  topic_firstpost int(10) NOT NULL default '0',
  topic_queuedposts int(10) NOT NULL default '0',
  topic_open_time int(10) NOT NULL default '0',
  topic_close_time int(10) NOT NULL default '0',
  topic_rating_total smallint(5) unsigned NOT NULL default '0',
  topic_rating_hits smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (tid),
  KEY topic_firstpost (topic_firstpost),
  KEY last_post (forum_id,pinned,last_post),
  KEY forum_id (forum_id,pinned,approved),
  KEY starter_id (starter_id, forum_id, approved),
  KEY last_post_sorting (last_post,forum_id)
);";

$TABLE[] = "CREATE TABLE ibf_topics_read (
  read_tid int(10) NOT NULL default '0',
  read_mid mediumint(8) NOT NULL default '0',
  read_date int(10) NOT NULL default '0',
  UNIQUE KEY read_tid_mid (read_tid,read_mid)
);";

$TABLE[] = "CREATE TABLE ibf_tracker (
  trid mediumint(8) NOT NULL auto_increment,
  member_id mediumint(8) NOT NULL default '0',
  topic_id int(10) NOT NULL default '0',
  start_date int(10) default NULL,
  last_sent int(10) NOT NULL default '0',
  topic_track_type varchar(100) NOT NULL default 'delayed',
  PRIMARY KEY  (trid),
  KEY topic_id (topic_id)
);";

$TABLE[] = "CREATE TABLE ibf_upgrade_history (
  upgrade_id int(10) NOT NULL auto_increment,
  upgrade_version_id int(10) NOT NULL default '0',
  upgrade_version_human varchar(200) NOT NULL default '',
  upgrade_date int(10) NOT NULL default '0',
  upgrade_mid int(10) NOT NULL default '0',
  upgrade_notes text NULL,
  PRIMARY KEY  (upgrade_id)
);";

$TABLE[] = "CREATE TABLE ibf_validating (
  vid varchar(32) NOT NULL default '',
  member_id mediumint(8) NOT NULL default '0',
  real_group smallint(3) NOT NULL default '0',
  temp_group smallint(3) NOT NULL default '0',
  entry_date int(10) NOT NULL default '0',
  coppa_user tinyint(1) NOT NULL default '0',
  lost_pass tinyint(1) NOT NULL default '0',
  new_reg tinyint(1) NOT NULL default '0',
  email_chg tinyint(1) NOT NULL default '0',
  ip_address varchar(16) NOT NULL default '0',
  user_verified tinyint(1) NOT NULL default '0',
  prev_email VARCHAR(150) NOT NULL default '0',
  PRIMARY KEY  (vid),
  KEY new_reg (new_reg)
);";

$TABLE[] = "CREATE TABLE ibf_voters (
  vid int(10) NOT NULL auto_increment,
  ip_address varchar(16) NOT NULL default '',
  vote_date int(10) NOT NULL default '0',
  tid int(10) NOT NULL default '0',
  member_id varchar(32) default NULL,
  forum_id smallint(5) NOT NULL default '0',
  KEY (tid),
  PRIMARY KEY  (vid)
);";

$TABLE[] = "CREATE TABLE ibf_warn_logs (
  wlog_id int(10) NOT NULL auto_increment,
  wlog_mid mediumint(8) NOT NULL default '0',
  wlog_notes text NULL,
  wlog_contact varchar(250) NOT NULL default 'none',
  wlog_contact_content text NULL,
  wlog_date int(10) NOT NULL default '0',
  wlog_type varchar(6) NOT NULL default 'pos',
  wlog_addedby mediumint(8) NOT NULL default '0',
  PRIMARY KEY  (wlog_id)
);";
    
$TABLE[] = "CREATE TABLE ibf_api_log (
  api_log_id 		int(10) unsigned NOT NULL auto_increment,
  api_log_key 		VARCHAR(32) NOT NULL,
  api_log_ip 		VARCHAR(16) NOT NULL,
  api_log_date 		INT(10) NOT NULL,
  api_log_query 	TEXT NOT NULL,
  api_log_allowed 	TINYINT(1) unsigned NOT NULL,
  PRIMARY KEY  (api_log_id)
);";

$TABLE[] = "CREATE TABLE ibf_api_users (
  api_user_id		INT(4) unsigned NOT NULL auto_increment,
  api_user_key		CHAR(32) NOT NULL,
  api_user_name		VARCHAR(32) NOT NULL,
  api_user_perms 	TEXT NOT NULL,
  api_user_ip 		VARCHAR(16) NOT NULL,
  PRIMARY KEY  (api_user_id)
);";

?>