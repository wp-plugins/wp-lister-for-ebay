<?php
/* Generated on 4/29/15 3:23 AM by globalsync
 * $Id: $
 * $Log: $
 */

require_once 'EbatNs_ComplexType.php';

/** 
  * This type defines the International Standard Book Number (ISBN) feature, and whether
  * this feature is enabled at the site level. An empty ISBNIdentifierEnabled
  * field is returned under the FeatureDefinitions container in GetCategoryFeatures
  * if the feature is applicable to the site and if ISBNIdentifierEnabled is
  * passed in as a FeatureID (or if no FeatureID is passed in, hence all features are
  * returned).
  * 
 **/

class ISBNIdentifierEnabledDefinitionType extends EbatNs_ComplexType
{

	/**
	 * Class Constructor 
	 **/
	function __construct()
	{
		parent::__construct('ISBNIdentifierEnabledDefinitionType', 'urn:ebay:apis:eBLBaseComponents');
		if (!isset(self::$_elements[__CLASS__]))
		{
			self::$_elements[__CLASS__] = array_merge(self::$_elements[get_parent_class()],
			array(
));
		}
		$this->_attributes = array_merge($this->_attributes,
		array(
));
	}

}
?>
