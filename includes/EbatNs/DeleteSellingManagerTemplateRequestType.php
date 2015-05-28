<?php
/* Generated on 4/29/15 3:23 AM by globalsync
 * $Id: $
 * $Log: $
 */

require_once 'AbstractRequestType.php';

/**
  * Deletes a Selling Manager template.
  * This call is subject to change without notice; the
  * deprecation process is inapplicable to this call.
  * 
 **/

class DeleteSellingManagerTemplateRequestType extends AbstractRequestType
{
	/**
	* @var long
	**/
	protected $SaleTemplateID;


	/**
	 * Class Constructor 
	 **/
	function __construct()
	{
		parent::__construct('DeleteSellingManagerTemplateRequestType', 'urn:ebay:apis:eBLBaseComponents');
		if (!isset(self::$_elements[__CLASS__]))
		{
			self::$_elements[__CLASS__] = array_merge(self::$_elements[get_parent_class()],
			array(
				'SaleTemplateID' =>
				array(
					'required' => false,
					'type' => 'long',
					'nsURI' => 'http://www.w3.org/2001/XMLSchema',
					'array' => false,
					'cardinality' => '0..1'
				)));
		}
		$this->_attributes = array_merge($this->_attributes,
		array(
));
	}

	/**
	 * @return long
	 **/
	function getSaleTemplateID()
	{
		return $this->SaleTemplateID;
	}

	/**
	 * @return void
	 **/
	function setSaleTemplateID($value)
	{
		$this->SaleTemplateID = $value;
	}

}
?>
