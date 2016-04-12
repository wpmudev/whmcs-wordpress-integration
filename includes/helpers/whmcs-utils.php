<?php
/**
 * array_replace() substitute for PHP < 5.3
 *
 * @$array
 *	@$$array1 [, $... ]
 */
if (!function_exists('array_replace') ):
	function array_replace(){
		$array=array();
		$n=func_num_args();
		while ($n-- >0) {
			$array+=func_get_arg($n);
		}
		return $array;
	}
endif;

/**
 * array_replace_recursive() substitute for PHP < 5.3
 *
 * @$array
 *	@$$array1 [, $... ]
 */
if(! function_exists('array_replace_recursive') ):
	function array_replace_recursive($base, $replacements)
	{
		foreach (array_slice(func_get_args(), 1) as $replacements) {
			$bref_stack = array(&$base);
			$head_stack = array($replacements);
			do {
				end($bref_stack);
				$bref = &$bref_stack[key($bref_stack)];
				$head = array_pop($head_stack);
				unset($bref_stack[key($bref_stack)]);
				foreach (array_keys($head) as $key) {
					if (isset($key, $bref) && is_array($bref[$key]) && is_array($head[$key])) {
						$bref_stack[] = &$bref[$key];
						$head_stack[] = $head[$key];
					} else {
						$bref[$key] = $head[$key];
					}
				}
			} while(count($head_stack));
		}
		return $base;
	}
endif;

/**
 * str_getcsv() substitute for PHP < 5.3
 *
 * @$input string - string to parse
 *	@$delimiter char - default ','
 * @$enclosure - default '"'
 * @$escape - character to escape enclosures or delimeters
 * @$eol - End of line character.
 */
if (!function_exists('str_getcsv')):
	function str_getcsv($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null) {
		$temp=fopen("php://memory", "rw");
		fwrite($temp, $input);
		fseek($temp, 0);
		$r = array();
		while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure)) !== false) {
			$r[] = $data;
		}
		fclose($temp);
		return $r;
	}
endif;