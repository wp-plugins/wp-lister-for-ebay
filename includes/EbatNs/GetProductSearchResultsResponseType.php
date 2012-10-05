<?php
// autogenerated file 09.05.2012 13:19
// $Id: $
// $Log: $
//
//
require_once 'DataElementSetType.php';
require_once 'ProductSearchResultType.php';
require_once 'AbstractResponseType.php';

/**
 * GetProductSearchResults performs a product search and collects the results. 
 * Resultattributes for each product/product family are grouped and identified with 
 * aproduct ID. If more matches are found than the max amount specified per 
 * family,only the product family information is returned. In this case, 
 * callGetProductFamilyMembers to retrieve more products within the same family. 
 *
 * @link http://developer.ebay.com/DevZone/XML/docs/Reference/eBay/types/GetProductSearchResultsResponseType.html
 *
 */
class GetProductSearchResultsResponseType extends AbstractResponseType
{
	/**
	 * @var DataElementSetType
	 */
	protected $DataElementSets;
	/**
	 * @var ProductSearchResultType
	 */
	protected $ProductSearchResult;

	/**
	 * @return DataElementSetType
	 * @param integer $index 
	 */
	function getDataElementSets($index = null)
	{
		if ($index !== null) {
			return $this->DataElementSets[$index];
		} else {
			return $this->DataElementSets;
		}
	}
	/**
	 * @return void
	 * @param DataElementSetType $value 
	 * @param  $index 
	 */
	function setDataElementSets($value, $index = null)
	{
		if ($index !== null) {
			$this->DataElementSets[$index] = $value;
		} else {
			$this->DataElementSets = $value;
		}
	}
	/**
	 * @return void
	 * @param DataElementSetType $value 
	 */
	function addDataElementSets($value)
	{
		$this->DataElementSets[] = $value;
	}
	/**
	 * @return ProductSearchResultType
	 * @param integer $index 
	 */
	function getProductSearchResult($index = null)
	{
		if ($index !== null) {
			return $this->ProductSearchResult[$index];
		} else {
			return $this->ProductSearchResult;
		}
	}
	/**
	 * @return void
	 * @param ProductSearchResultType $value 
	 * @param  $index 
	 */
	function setProductSearchResult($value, $index = null)
	{
		if ($index !== null) {
			$this->ProductSearchResult[$index] = $value;
		} else {
			$this->ProductSearchResult = $value;
		}
	}
	/**
	 * @return void
	 * @param ProductSearchResultType $value 
	 */
	function addProductSearchResult($value)
	{
		$this->ProductSearchResult[] = $value;
	}
	/**
	 * @return 
	 */
	function __construct()
	{
		parent::__construct('GetProductSearchResultsResponseType', 'urn:ebay:apis:eBLBaseComponents');
		if (!isset(self::$_elements[__CLASS__]))
				self::$_elements[__CLASS__] = array_merge(self::$_elements[get_parent_class()],
				array(
					'DataElementSets' =>
					array(
						'required' => false,
						'type' => 'DataElementSetType',
						'nsURI' => 'urn:ebay:apis:eBLBaseComponents',
						'array' => true,
						'cardinality' => '0..*'
					),
					'ProductSearchResult' =>
					array(
						'required' => false,
						'type' => 'ProductSearchResultType',
						'nsURI' => 'urn:ebay:apis:eBLBaseComponents',
						'array' => true,
						'cardinality' => '0..*'
					)
				));
	}
}
?>