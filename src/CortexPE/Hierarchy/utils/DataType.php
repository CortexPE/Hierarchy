<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 3/31/2019
 * Time: 7:52 AM
 */

namespace CortexPE\Hierarchy\utils;


use CortexPE\Hierarchy\exception\UnexpectedDataTypeError;

class DataType {
	public static function ensureBoolean($data){
		if(!is_bool($data)){
			throw new UnexpectedDataTypeError("Expecting boolean, " . gettype($data) . " given.");
		}

		return $data;
	}

	public static function ensureString($data){
		if(!is_string($data)){
			throw new UnexpectedDataTypeError("Expecting string, " . gettype($data) . " given.");
		}

		return $data;
	}

	public static function ensureInteger($data){
		if(!is_integer($data)){
			throw new UnexpectedDataTypeError("Expecting integer, " . gettype($data) . " given.");
		}

		return $data;
	}

	public static function ensureFloat($data){
		if(!is_float($data)){
			throw new UnexpectedDataTypeError("Expecting float, " . gettype($data) . " given.");
		}

		return $data;
	}
}