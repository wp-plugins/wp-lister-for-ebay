<?php
/* Generated on 6/26/15 3:23 AM by globalsync
 * $Id: $
 * $Log: $
 */

require_once 'AbstractResponseType.php';
require_once 'SellingManagerFolderDetailsType.php';

/**
  * Returns the folder structure of the input folderID.
  * 
 **/

class GetSellingManagerInventoryFolderResponseType extends AbstractResponseType
{
	/**
	* @var SellingManagerFolderDetailsType
	**/
	protected $Folder;


	/**
	 * Class Constructor 
	 **/
	function __construct()
	{
		parent::__construct('GetSellingManagerInventoryFolderResponseType', 'urn:ebay:apis:eBLBaseComponents');
		if (!isset(self::$_elements[__CLASS__]))
		{
			self::$_elements[__CLASS__] = array_merge(self::$_elements[get_parent_class()],
			array(
				'Folder' =>
				array(
					'required' => false,
					'type' => 'SellingManagerFolderDetailsType',
					'nsURI' => 'urn:ebay:apis:eBLBaseComponents',
					'array' => false,
					'cardinality' => '0..1'
				)));
		}
		$this->_attributes = array_merge($this->_attributes,
		array(
));
	}

	/**
	 * @return SellingManagerFolderDetailsType
	 **/
	function getFolder()
	{
		return $this->Folder;
	}

	/**
	 * @return void
	 **/
	function setFolder($value)
	{
		$this->Folder = $value;
	}

}
?>
