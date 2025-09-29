<?php

/**
 * Import Demos Admin Page
 * 
 * @package minimalio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fetch remote demos from GitHub
 */
function minimalio_get_remote_demos() {
    $demos_url = 'https://raw.githubusercontent.com/MikulasKarpeta/demos/main/demos.json';
    $transient_key = 'minimalio_remote_demos';
    
    // Try to get cached data first (cache for 1 hour)
    $demos = get_transient($transient_key);
    
    if (false === $demos) {
        $response = wp_remote_get($demos_url, array(
            'timeout' => 10,
            'user-agent' => 'Minimalio Theme Demo Loader'
        ));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $demos_data = json_decode($body, true);
            
            if ($demos_data && isset($demos_data['demos'])) {
                $demos = $demos_data;
                // Cache for 1 hour
                set_transient($transient_key, $demos, HOUR_IN_SECONDS);
            } else {
                $demos = false;
            }
        } else {
            // Return false if remote loading fails
            $demos = false;
        }
    }
    
    return $demos;
}


/**
 * Import demos page content
 */
function minimalio_demos_page() {
    // Check if minimalio-portfolio plugin is active
    $is_portfolio_plugin_active = is_plugin_active( 'minimalio-portfolio/minimalio-portfolio.php' );
    
    // Get remote demos
    $demos_data = minimalio_get_remote_demos();
    $demos = ($demos_data && isset($demos_data['demos'])) ? $demos_data['demos'] : array();
    ?>
    <div class="wrap minimalio-admin-page">
        <h1><?php _e( 'Demos', 'minimalio' ); ?></h1>
        
        <?php if ( ! $is_portfolio_plugin_active ): ?>
            <div class="minimalio-admin-card">
                <h2><?php _e( 'Import Available Only with the Premium Plugin', 'minimalio' ); ?></h2>
                <p><?php _e( 'You can preview the demos, but importing them is only available with the Premium Plugin.', 'minimalio' ); ?></p>
                <a href="https://minimalio.org/premium-plugin/" target="_blank" class="button minimalio-premium-button">
                    <?php _e( 'Purchase Premium Plugin ($49)', 'minimalio' ); ?>
                </a>
                <a href="<?php echo admin_url( 'admin.php?page=minimalio-tutorials#install' ); ?>" class="button button-primary">
                    <?php _e( 'How to Install Tutorial', 'minimalio' ); ?>
                </a>
            </div>
        <?php endif; ?>

        <div class="minimalio-admin-card">
        <h2><?php _e( 'How to Import? Content First, Then Settings.', 'minimalio' ); ?></h2>
            <p class="max-width-62"><?php _e( 'Importing demos couldn\'t be easier. But please, check out the Import Demo Tutorial, to find out how it works, what it imports and how to use the demo content.', 'minimalio' ); ?></p>
            
                <a href="<?php echo admin_url( 'admin.php?page=minimalio-tutorials#tutorial-how-to-import-a-demo' ); ?>" class="button button-secondary minimalio-premium-button">
                    <?php _e( 'Import Demo Tutorial', 'minimalio' ); ?>
                </a>
                <a href="https://minimalio.org/demos" target="_blank" class="button button-primary">
                    <?php _e( 'View Demos on Minimalio Website', 'minimalio' ); ?>
                </a>
            

        </div>
        

        <div class="minimalio-demos-grid">
            <?php if ( empty($demos) ): ?>
                <div class="notice notice-warning">
                    <p><?php _e( 'Sorry, Minimalio is having trouble finding the demos, make sure you are connected to the internet or you can try to refresh demos at the bottom of this page. If the problem persists, please contact the support. Thank you.', 'minimalio' ); ?></p>
                </div>
            <?php else: ?>
                <?php foreach ( $demos as $demo ): ?>
                    <div class="minimalio-demo-item" data-demo-id="<?php echo esc_attr( $demo['id'] ); ?>">
                        <div class="minimalio-demo-preview">
                            <img src="<?php echo esc_url( $demo['image'] ); ?>" alt="<?php echo esc_attr( $demo['name'] . ' Demo' ); ?>">
                        </div>
                        <div class="minimalio-demo-info">
                            <h3><?php echo esc_html( $demo['name'] ); ?></h3>
                            <div class="minimalio-demo-actions">
                                <?php if ( $is_portfolio_plugin_active ): ?>
                                    <?php if ( !empty($demo['content_url']) ): ?>
                                        <a href="#" class="button button-primary minimalio-import-content" 
                                           data-demo-type="<?php echo esc_attr( $demo['id'] ); ?>"
                                           data-content-url="<?php echo esc_url( $demo['content_url'] ); ?>">
                                            <?php _e( 'Import Content', 'minimalio' ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ( !empty($demo['settings_url']) ): ?>
                                        <a href="#" class="button button-secondary minimalio-import-settings" 
                                           data-demo-type="<?php echo esc_attr( $demo['id'] ); ?>"
                                           data-settings-url="<?php echo esc_url( $demo['settings_url'] ); ?>">
                                            <?php _e( 'Import Settings', 'minimalio' ); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="button button-primary disabled" title="<?php _e( 'Requires Minimalio Portfolio Plugin', 'minimalio' ); ?>">
                                        <?php _e( 'Import Content', 'minimalio' ); ?>
                                    </span>
                                    <span class="button button-secondary disabled" title="<?php _e( 'Requires Minimalio Portfolio Plugin', 'minimalio' ); ?>">
                                        <?php _e( 'Import Settings', 'minimalio' ); ?>
                                    </span>
                                <?php endif; ?>
                                <a href="<?php echo esc_url( $demo['preview_url'] ); ?>" class="button minimalio-premium-button" target="_blank">
                                    <?php _e( 'Preview', 'minimalio' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ( current_user_can( 'manage_options' ) ): ?>
            <div class="minimalio-admin-info">
                <h3><?php _e( 'Demo Management', 'minimalio' ); ?></h3>
                <p>
                    <a href="<?php echo add_query_arg( 'refresh_demos', '1' ); ?>" class="button">
                        <?php _e( 'Refresh Demos', 'minimalio' ); ?>
                    </a>
                </p>
                
                <?php
                /**
                 * Allow plugins to add content to the demo management section
                 * 
                 * @since 1.0.0
                 */
                do_action( 'minimalio_demo_management_section' );
                ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Handle demo cache refresh
 */
add_action( 'admin_init', 'minimalio_handle_demo_refresh' );
function minimalio_handle_demo_refresh() {
    if ( isset( $_GET['refresh_demos'] ) && current_user_can( 'manage_options' ) ) {
        delete_transient( 'minimalio_remote_demos' );
        wp_redirect( remove_query_arg( 'refresh_demos' ) );
        exit;
    }
    
}