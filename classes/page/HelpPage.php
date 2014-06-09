<?php
/**
 * HelpPage class
 * 
 */

class HelpPage extends WPL_Page {

	const slug = 'tutorial';

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();
		if ( apply_filters( 'wplister_reseller_disable_tutorial_page', false ) ) return;

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Tutorial' ), __('Tutorial','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'tutorial' ), array( &$this, 'onDisplayHelpPage' ) );
	}


	public function onDisplayHelpPage() {
		$this->check_wplister_setup('tutorial');

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'content_help_setup'		=> $this->get_i8n_html('help_setup'),
			'content_help_listing'		=> $this->get_i8n_html('help_listing'),

			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-settings'
		);
		$this->display( 'tutorial_page', $aData );
	}


}
