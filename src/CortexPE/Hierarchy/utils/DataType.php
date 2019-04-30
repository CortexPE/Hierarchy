<?php

/***
 *        __  ___                           __
 *       / / / (_)__  _________ ___________/ /_  __  __
 *      / /_/ / / _ \/ ___/ __ `/ ___/ ___/ __ \/ / / /
 *     / __  / /  __/ /  / /_/ / /  / /__/ / / / /_/ /
 *    /_/ /_/_/\___/_/   \__,_/_/   \___/_/ /_/\__, /
 *                                            /____/
 *
 * Hierarchy - Role-based permission management system
 * Copyright (C) 2019-Present CortexPE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

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