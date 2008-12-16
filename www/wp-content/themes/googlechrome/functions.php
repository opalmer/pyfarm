<?php
if ( function_exists('register_sidebar') ) {
    register_sidebar( array('before_widget' => '<div class="sidebarboxtop"><img src="' . get_bloginfo('template_url') . '/images/sidebartop.gif" alt="sidebartop" /></div><div class="sidebarbox">', 'after_widget' => '</div><div class="sidebarboxbottom"><img src="' . get_bloginfo('template_url') . '/images/sidebarbottom.gif" alt="sidebarbottom" /></div>', 'before_title' => '<h3>', 'after_title' => '</h3>') );
}

function custom_comment($comment, $args, $depth) {
       $GLOBALS['comment'] = $comment;
		?>
		<li <?php comment_class(); ?> id="comment-<?php comment_ID() ?>">
	        <?php echo get_avatar( get_comment_author_email(), '32' ); ?>
			<strong class="commentmetadata"><?php comment_author_link() ?></strong> | <small class="commentmetadata"><a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date('d M Y') ?> <?php comment_time() ?></a> <?php edit_comment_link('e','',''); ?></small>
			<?php if ($comment->comment_approved == '0') : ?>
			<em>Your comment is awaiting moderation.</em>
			<?php endif; ?>

			<?php comment_text() ?>
            <small class="replycomment"><?php comment_reply_link(array('reply_text' => 'Reply to this comment', 'depth' => $depth, 'max_depth'=> $args['max_depth'])) ?></small>
       <?php
}
?>