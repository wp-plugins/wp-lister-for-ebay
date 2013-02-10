    <?php  
        $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings'; 
    ?>  

	<h2 class="nav-tab-wrapper">  

        <?php if ( ! is_network_admin() ) : ?>
        <a href="<?php echo $wpl_settings_url; ?>&tab=settings"   class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">General Settings</a>  
        <?php endif; ?>


        <a href="<?php echo $wpl_settings_url; ?>&tab=developer"  class="nav-tab <?php echo $active_tab == 'developer' ? 'nav-tab-active' : ''; ?>">Developer</a>  
    </h2>  
