<?php  
    $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings'; 
    // if ( @$_REQUEST['page'] == 'wplister-settings-categories' ) $active_tab = 'categories';
?>  

<?php if ( @$_REQUEST['page'] == 'wplister-settings-categories' ) : ?>

    <h2><?php echo __('Categories','wplister') ?></h2>  

<?php elseif ( @$_REQUEST['page'] == 'wplister-settings-accounts' ) : ?>

    <h2><?php echo __('My Account','wplister') ?></h2>  

<?php else : ?>

	<h2 class="nav-tab-wrapper">  

        <?php if ( ! is_network_admin() ) : ?>
        <a href="<?php echo $wpl_settings_url; ?>&tab=settings"   class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php echo __('General Settings','wplister') ?></a>  
        <?php endif; ?>

        <a href="<?php echo $wpl_settings_url; ?>&tab=accounts"  class="nav-tab <?php echo $active_tab == 'accounts' ? 'nav-tab-active' : ''; ?>"><?php echo __('Accounts','wplister') ?></a>  

        <?php if ( ! is_network_admin() ) : ?>
        <a href="<?php echo $wpl_settings_url; ?>&tab=categories" class="nav-tab <?php echo $active_tab == 'categories' ? 'nav-tab-active' : ''; ?>"><?php echo __('Categories','wplister') ?></a>  
        <?php endif; ?>

        <a href="<?php echo $wpl_settings_url; ?>&tab=advanced"   class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php echo __('Advanced','wplister') ?></a>  

        <?php if ( ! defined('WPLISTER_RESELLER_VERSION') || ( $active_tab == 'developer' ) ) : ?>
        <a href="<?php echo $wpl_settings_url; ?>&tab=developer"  class="nav-tab <?php echo $active_tab == 'developer' ? 'nav-tab-active' : ''; ?>"><?php echo __('Developer','wplister') ?></a>  
        <?php endif; ?>


    </h2>  

<?php endif; ?>
