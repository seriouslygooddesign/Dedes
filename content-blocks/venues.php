<?php
$block_args = [
    'modifier' => basename(__FILE__, '.php')
];
get_template_part('components/block', 'start', $block_args);
switch_to_blog(1);
if (have_rows('sites', 'option')) : ?>
    <?php while (have_rows('sites', 'option')) : the_row();
        get_template_part('components/content-gallery');
    endwhile; ?>
<?php
endif;
restore_current_blog();
get_template_part('components/block', 'end');
