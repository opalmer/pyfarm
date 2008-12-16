    <div class='sidebar span-8 last'>
		<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>	
        <div class='sidebarboxtop'><img src="<?php bloginfo('template_url') ?>/images/sidebartop.gif" alt="sidebartop" /></div>
        <div class='sidebarbox'>
            <h3>Categories</h3>
            <ul>	
                <?php wp_list_cats('sort_column=name'); ?>				
            </ul>
        </div>
        <div class='sidebarboxbottom'><img src="<?php bloginfo('template_url') ?>/images/sidebarbottom.gif" alt="sidebarbottom" /></div>
        
        <div class='sidebarboxtop'><img src="<?php bloginfo('template_url') ?>/images/sidebartop.gif" alt="sidebartop" /></div>
        <div class='sidebarbox'>
                <?php wp_list_bookmarks(array('title_before' => '<h3>', 'title_after' => '</h3>',	'category_before' => '', 'category_after' => '')); ?>
        </div>
        <div class='sidebarboxbottom'><img src="<?php bloginfo('template_url') ?>/images/sidebarbottom.gif" alt="sidebarbottom" /></div>
        
        <div class='sidebarboxtop'><img src="<?php bloginfo('template_url') ?>/images/sidebartop.gif" alt="sidebartop" /></div>
        <div class='sidebarbox'>
			<?php if (function_exists('wp_tag_cloud')) { ?>
            <h3>Tag Cloud</h3>
            <? wp_tag_cloud(); ?>
            <?php } ?>
        </div>
        <div class='sidebarboxbottom'><img src="<?php bloginfo('template_url') ?>/images/sidebarbottom.gif" alt="sidebarbottom" /></div>
		<?php endif; ?>
    </div>