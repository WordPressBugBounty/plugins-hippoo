<?php

class HippooDashboardWidget {
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'hippoo_blog_feed',
            __('Latest blog posts on Hippoo', 'hippoo'),
            array($this, 'render_widget_content')
        );
    }

    public function render_widget_content() {
        wp_widget_rss_output( [
            'url'          => 'https://hippoo.app/category/blog/feed/',
            'title'        => __('Latest blog posts on Hippoo', 'hippoo'),
            'items'        => 3,
            'show_summary' => 1,
            'show_author'  => 0,
            'show_date'    => 0,
        ] );
        ?>
        <div style="border-top: 1px solid #e7e7e7; padding-top: 12px !important; font-size: 14px;">
            <a href="https://hippoo.app/category/blog/" target="_blank"><?php _e('Read more on our blog', 'hippoo'); ?></a>
        </div>
        <?php
    }
}

new HippooDashboardWidget();