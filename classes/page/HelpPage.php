<?php
/**
 * HelpPage class
 * 
 */

class HelpPage extends WPL_Page {

	const slug = 'tutorial';

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Tutorial' ), __('Tutorial','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'tutorial' ), array( &$this, 'onDisplayHelpPage' ) );
	}


	public function onDisplayHelpPage() {
		WPL_Setup::checkSetup('tutorial');

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-settings'
		);
		$this->display( 'tutorial_page', $aData );
	}


}
