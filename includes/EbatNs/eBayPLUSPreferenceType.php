<?php
/* Generated on 6/26/15 3:23 AM by globalsync
 * $Id: $
 * $Log: $
 */

require_once 'EbatNs_ComplexType.php';
require_once 'CountryCodeType.php';

/**
  * This type is reserved for future use.
  * 
 **/

class eBayPLUSPreferenceType extends EbatNs_ComplexType
{
	/**
	* @var CountryCodeType
	**/
	protected $Country;

	/**
	* @var boolean
	**/
	protected $OptInStatus;

	/**
	* @var boolean
	**/
	protected $ListingPreference;


	/**
	 * Class Constructor 
	 **/
	function __construct()
	{
		parent::__construct('eBayPLUSPreferenceType', 'urn:ebay:apis:eBLBaseComponents');
		if (!isset(self::$_elements[__CLASS__]))
		{
			self::$_elements[__CLASS__] = array_merge(self::$_elements[get_parent_class()],
			array(
				'Country' =>
				array(
					'required' => false,
					'type' => 'CountryCodeType',
					'nsURI' => 'urn:ebay:apis:eBLBaseComponents',
					'array' => false,
					'cardinality' => '0..1'
				),
				'OptInStatus' =>
				array(
					'required' => false,
					'type' => 'boolean',
					'nsURI' => 'http://www.w3.org/2001/XMLSchema',
					'array' => false,
					'cardinality' => '0..1'
				),
				'ListingPreference' =>
				array(
					'required' => false,
					'type' => 'boolean',
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
	 * @return CountryCodeType
	 **/
	function getCountry()
	{
		return $this->Country;
	}

	/**
	 * @return void
	 **/
	function setCountry($value)
	{
		$this->Country = $value;
	}

	/**
	 * @return boolean
	 **/
	function getOptInStatus()
	{
		return $this->OptInStatus;
	}

	/**
	 * @return void
	 **/
	function setOptInStatus($value)
	{
		$this->OptInStatus = $value;
	}

	/**
	 * @return boolean
	 **/
	function getListingPreference()
	{
		return $this->ListingPreference;
	}

	/**
	 * @return void
	 **/
	function setListingPreference($value)
	{
		$this->ListingPreference = $value;
	}

}
?>
