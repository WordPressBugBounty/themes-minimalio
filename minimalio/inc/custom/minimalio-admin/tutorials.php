<?php

/**
 * Tutorials Admin Page
 * 
 * @package minimalio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fetch remote tutorials from GitHub
 */
function minimalio_get_remote_tutorials() {
    $tutorials_url = 'https://raw.githubusercontent.com/MikulasKarpeta/demos/main/tutorials.json';
    $transient_key = 'minimalio_remote_tutorials';
    
    // Try to get cached data first (cache for 1 hour)
    $tutorials = get_transient($transient_key);
    
    if (false === $tutorials) {
        $response = wp_remote_get($tutorials_url, array(
            'timeout' => 10,
            'user-agent' => 'Minimalio Theme Tutorial Loader'
        ));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $tutorials_data = json_decode($body, true);
            
            if ($tutorials_data && isset($tutorials_data['tutorials'])) {
                $tutorials = $tutorials_data;
                // Cache for 1 hour
                set_transient($transient_key, $tutorials, HOUR_IN_SECONDS);
            } else {
                $tutorials = false;
            }
        } else {
            // Return false if remote loading fails
            $tutorials = false;
        }
    }
    
    return $tutorials;
}


/**
 * Group tutorials by category in specific order
 */
function minimalio_group_tutorials_by_category($tutorials) {
    $grouped = array();
    $category_order = array('beginners', 'content', 'theme options', 'demos');
    
    // Initialize categories in order
    foreach ($category_order as $category) {
        $grouped[$category] = array();
    }
    
    // Group tutorials by category
    foreach ($tutorials as $tutorial) {
        $category = $tutorial['category'];
        if (isset($grouped[$category])) {
            $grouped[$category][] = $tutorial;
        }
    }
    
    // Remove empty categories
    return array_filter($grouped);
}

/**
 * Get category display name
 */
function minimalio_get_category_display_name($category) {
    $names = array(
        'beginners' => __('Beginner Tutorials', 'minimalio'),
        'content' => __('Content Management', 'minimalio'),
        'demos' => __('Demos and Child Themes', 'minimalio'),
        'theme options' => __('Theme Options', 'minimalio')
    );
    
    return isset($names[$category]) ? $names[$category] : ucfirst($category);
}

/**
 * Tutorials page content
 */
function minimalio_tutorials_page() {
    // Get remote tutorials
    $tutorials_data = minimalio_get_remote_tutorials();
    $tutorials = ($tutorials_data && isset($tutorials_data['tutorials'])) ? $tutorials_data['tutorials'] : array();
    
    // Group tutorials by category
    $grouped_tutorials = minimalio_group_tutorials_by_category($tutorials);
    ?>
    <div class="wrap minimalio-admin-page">
        <h1><?php _e( 'Tutorials', 'minimalio' ); ?></h1>

        <?php if ( empty($tutorials) ): ?>
            <div class="notice notice-warning">
                <p><?php _e( 'Sorry, Minimalio is having trouble finding the tutorials, make sure you are connected to the internet and if the problem persists, please contact the support. Thank you.', 'minimalio' ); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ( $grouped_tutorials as $category => $category_tutorials ): ?>
                <?php if ( !empty($category_tutorials) ): ?>
                    <div class="minimalio-tutorials-category" id="<?php echo esc_attr($category); ?>">
                        <h2 class="minimalio-category-title"><?php echo esc_html( minimalio_get_category_display_name($category) ); ?></h2>
                        
                        <div class="minimalio-tutorials-grid">
                            <?php foreach ( $category_tutorials as $tutorial ): ?>
                                <div class="minimalio-tutorial-item" id="tutorial-<?php echo esc_attr( $tutorial['id'] ); ?>" data-tutorial-id="<?php echo esc_attr( $tutorial['id'] ); ?>">
                                    <div class="minimalio-tutorial-image">
                                        <img src="<?php echo esc_url( $tutorial['image'] ); ?>" alt="<?php echo esc_attr( $tutorial['name'] ); ?>">
                                    </div>
                                    <div class="minimalio-tutorial-content">
                                        <h3><?php echo esc_html( $tutorial['name'] ); ?></h3>
                                        <p><?php echo esc_html( $tutorial['description'] ); ?></p>
                                        <div class="minimalio-tutorial-actions">
                                            <a href="<?php echo esc_url( $tutorial['url'] ); ?>" class="button button-primary" target="_blank">
                                                <?php _e( 'View Tutorial', 'minimalio' ); ?>
                                            </a>
                                            <?php if ( !empty($tutorial['videoUrl']) ): ?>
                                                <a href="#" class="button minimalio-premium-button minimalio-play-video " data-video-id="<?php echo esc_attr( $tutorial['videoUrl'] ); ?>">
                                                    <?php _e( 'Play Video', 'minimalio' ); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="minimalio-tutorials-footer minimalio-admin-info">
            <h3><?php _e( 'Need More Help?', 'minimalio' ); ?></h3>
            <p><?php _e( 'If you have purchased the Premium Plugin, you should have received the support email address. Please contact the support there. Thank you.', 'minimalio' ); ?></p>
        </div>
        
        <?php if ( current_user_can( 'manage_options' ) ): ?>
            <div class="minimalio-admin-info">
                <h3><?php _e( 'Tutorial Management', 'minimalio' ); ?></h3>
                <p>
                    <a href="<?php echo add_query_arg( 'refresh_tutorials', '1' ); ?>" class="button">
                        <?php _e( 'Refresh Tutorials', 'minimalio' ); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle anchor links for direct category or tutorial navigation
        function handleAnchorNavigation() {
            var hash = window.location.hash;
            
            if (hash) {
                var target = $(hash);
                
                // If specific tutorial not found, try to find installation-related tutorial
                if (target.length === 0 && hash.includes('install')) {
                    $('.minimalio-tutorial-item').each(function() {
                        var tutorialId = $(this).attr('id');
                        var tutorialTitle = $(this).find('h3').text().toLowerCase();
                        
                        if (tutorialId && (tutorialId.includes('install') || tutorialTitle.includes('install'))) {
                            target = $(this);
                            return false; // Break the loop
                        }
                    });
                }
                
                if (target.length) {
                    // Scroll to the target
                    $('html, body').animate({
                        scrollTop: target.offset().top - 50
                    }, 500);
                    
                    // Check if it's a tutorial item or category
                    if (target.hasClass('minimalio-tutorial-item')) {
                        // Highlight the tutorial item
                        target.css({
                            'border': '3px solid #f86d5b',
                            'box-shadow': '0 0 20px rgba(248, 109, 91, 0.3)',
                            'transition': 'all 0.3s ease'
                        });
                        
                        // Remove highlight after 4 seconds
                        setTimeout(function() {
                            target.css({
                                'border': '',
                                'box-shadow': ''
                            });
                        }, 4000);
                    } else if (target.hasClass('minimalio-tutorials-category')) {
                        // Highlight the category title
                        target.find('.minimalio-category-title').css({
                            'background-color': '#f86d5b',
                            'color': '#fff',
                            'padding': '10px',
                            'margin': '-10px -10px 10px -10px',
                            'transition': 'all 0.3s ease'
                        });
                        
                        // Remove highlight after 3 seconds
                        setTimeout(function() {
                            target.find('.minimalio-category-title').css({
                                'background-color': '',
                                'color': '',
                                'padding': '',
                                'margin': ''
                            });
                        }, 3000);
                    }
                }
            }
        }
        
        // Handle on page load
        handleAnchorNavigation();
        
        // Handle when hash changes
        $(window).on('hashchange', handleAnchorNavigation);
        
        // Handle Play Video button clicks
        $('.minimalio-tutorial-item .minimalio-play-video').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var tutorialItem = button.closest('.minimalio-tutorial-item');
            var videoId = button.data('video-id');
            
            if (!videoId) {
                alert('Invalid video ID');
                return;
            }
            
            // Check if video is already showing (look for video container after this tutorial item)
            var existingVideo = tutorialItem.next('.minimalio-video-container');
            
            if (existingVideo.length > 0) {
                // Close video
                existingVideo.remove();
                button.text('<?php _e( 'Play Video', 'minimalio' ); ?>');
            } else {
                // Close any other open videos first with proper cleanup
                $('.minimalio-video-container').each(function() {
                    // Try to properly destroy any Muse.ai players before removing
                    var players = $(this).find('.muse-video-player');
                    players.each(function() {
                        try {
                            // Clear any event listeners or player instances
                            $(this).empty();
                        } catch (e) {
                            // Ignore cleanup errors
                        }
                    });
                });
                $('.minimalio-video-container').remove();
                $('.minimalio-play-video').text('<?php _e( 'Play Video', 'minimalio' ); ?>');
                
                // Add a small delay to ensure cleanup is complete
                setTimeout(function() {
                    // Create iframe directly for Muse.ai video
                    var iframeSrc = 'https://muse.ai/embed/' + videoId + '?logo=https://minimalio.sirv.com/Images/Minimalio-logo-muse.png&subtitles=auto&title=0';
                    
                    // Open video with direct iframe approach
                    var videoHtml = '<div class="minimalio-video-container" style="' +
                        'grid-column: 1 / -1; ' +
                        'width: 100%; ' +
                        'max-width: 1200px; ' +
                        'margin: 20px auto; ' +
                        'position: relative; ' +
                        'padding-bottom: 56.25%; ' +
                        'height: 0; ' +
                        'overflow: hidden; ' +
                        'background: #000;' +
                        '">' +
                        '<iframe src="' + iframeSrc + '" ' +
                        'style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" ' +
                        'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ' +
                        'allowfullscreen>' +
                        '</iframe>' +
                        '</div>';
                    
                    // Add the video container after the tutorial item
                    tutorialItem.after(videoHtml);
                    
                    button.text('<?php _e( 'Close Video', 'minimalio' ); ?>');
                }, 100);
            }
        });
    });
    </script>
    <?php
}

/**
 * Handle tutorial cache refresh
 */
add_action( 'admin_init', 'minimalio_handle_tutorial_refresh' );
function minimalio_handle_tutorial_refresh() {
    if ( isset( $_GET['refresh_tutorials'] ) && current_user_can( 'manage_options' ) ) {
        delete_transient( 'minimalio_remote_tutorials' );
        wp_redirect( remove_query_arg( 'refresh_tutorials' ) );
        exit;
    }
}