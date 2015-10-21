<?php
/**
 * WPLE_ValidationHelper class
 *
 * provides static methods to validate UPCs and EANs
 * 
 */

class WPLE_ValidationHelper {


    // check if a number is a valid UPC
    static function isValidUPC( $value ) {

	    // check if barcode is 12 digits long
        if ( ! preg_match( '/^\d{12}$/', $value ) ) {
            return;
        }

	    // validate UPC
		$lastDigitIndex = strlen($value) - 1;
		$accumulator    = 0;
		$checkDigit     = (int) $value[ $lastDigitIndex ];

        // reverse the actual digits (excluding the check digit)
        $str = strrev( substr( $value, 0, $lastDigitIndex ) );

        /**
         *  Moving from right to left
         *  Even digits are just added
         *  Odd digits are multiplied by three
         */
        $accumulator = 0;
        for ( $i = 0; $i < $lastDigitIndex; $i++ ) {
            $accumulator += $i % 2 ? (int) $value[$i] : (int) $value[$i] * 3;
        }

        $checksum = ( 10 - ($accumulator % 10) ) % 10;

        if ( $checksum !== $checkDigit ) {
            return false;
        }

        return true;
    } // isValidUPC()


    // check if a number is a valid EAN
    static function isValidEAN( $digits ) {

	    // check if barcode is 13 digits long
	    if (!preg_match("/^[0-9]{13}$/", $digits)) {
	        return false;
	    }

	    // 1. Add the values of the digits in the 
	    // even-numbered positions: 2, 4, 6, etc.
	    $even_sum = $digits[1] + $digits[3] + $digits[5] +
	                $digits[7] + $digits[9] + $digits[11];

	    // 2. Multiply this result by 3.
	    $even_sum_three = $even_sum * 3;

	    // 3. Add the values of the digits in the 
	    // odd-numbered positions: 1, 3, 5, etc.
	    $odd_sum = $digits[0] + $digits[2] + $digits[4] +
	               $digits[6] + $digits[8] + $digits[10];

	    // 4. Sum the results of steps 2 and 3.
	    $total_sum = $even_sum_three + $odd_sum;

	    // 5. The check character is the smallest number which,
	    // when added to the result in step 4, produces a multiple of 10.
	    $next_ten = (ceil($total_sum / 10)) * 10;
	    $check_digit = $next_ten - $total_sum;

	    // if the check digit and the last digit of the 
	    // barcode are OK return true;
	    if ($check_digit == $digits[12]) {
	        return true;
	    }

	    return false;
	} // isValidEAN()


} // class WPLE_ValidationHelper
