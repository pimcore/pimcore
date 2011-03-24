<?php

/**
 * lessphp v0.2.0
 * http://leafo.net/lessphp
 *
 * LESS Css compiler, adapted from http://lesscss.org/docs.html
 *
 * Copyright 2010, Leaf Corcoran <leafot@gmail.com>
 * Licensed under MIT or GPLv3, see LICENSE
 */

//
// investigate trouble with ^M
// fix the alpha value with color when using a percent
//

class lessc {
	private $buffer;
	private $count;
	private $line;
	private $expandStack;

	private $env = array();

	public $vPrefix = '@';
	public $mPrefix = '$';
	public $imPrefix = '!';
	public $selfSelector = '&';

	static private $precedence = array(
		'+' => 0,
		'-' => 0,
		'*' => 1,
		'/' => 1,
		'%' => 1,
	);
	static private $operatorString; // regex string to match any of the operators

	static private $dtypes = array('expression', 'variable', 'function', 'negative'); // types with delayed computation
	static private $units = array(
		'px', '%', 'in', 'cm', 'mm', 'em', 'ex', 'pt', 'pc', 'ms', 's', 'deg');

	public $importDisabled = false;
	public $importDir = '';

	// compile chunk off the head of buffer
	function chunk() {
		if (empty($this->buffer)) return false;
		$s = $this->seek();

		// a property
		if ($this->keyword($key) && $this->assign() && $this->propertyValue($value) && $this->end()) {
			// look for important prefix
			if ($key{0} == $this->imPrefix && strlen($key) > 1) {
				$key = substr($key, 1);
				if ($value[0] == 'list' && $value[1] == ' ') {
					$value[2][] = array('keyword', '!important');
				} else {
					$value = array('list', ' ', array($value, array('keyword', '!important')));
				}
			}
			$this->append($key, $value);

			if (count($this->env) == 1)
				return $this->compileProperty($key, array($value))."\n";
			else
				return true;
		} else {
			$this->seek($s);
		}

		// look for special css @ directives
		if (count($this->env) == 1 && $this->count < strlen($this->buffer) && $this->buffer[$this->count] == '@') {
			// a font-face block
			if ($this->literal('@font-face') && $this->literal('{')) {
				$this->push();
				$this->set('__tags', array('@font-face'));
				$this->set('__dontsave', true);
				return true;
			} else {
				$this->seek($s);
			}

			// charset
			if ($this->literal('@charset') && $this->propertyValue($value) && $this->end()) {
				return "@charset ".$this->compileValue($value).";\n";
			} else {
				$this->seek($s);
			}
		}

		// opening abstract block
		if ($this->tag($tag, true) && $this->argumentDef($args) && $this->literal('{')) {
			$this->push();

			// move out of variable scope
			if ($tag{0} == $this->vPrefix) $tag[0] = $this->mPrefix;

			$this->set('__tags', array($tag));
			if (isset($args)) $this->set('__args', $args);

			return true;
		} else {
			$this->seek($s);
		}

		// opening css block
		if ($this->tags($tags) && $this->literal('{')) {
			//  move @ tags out of variable namespace!
			foreach($tags as &$tag) {
				if ($tag{0} == $this->vPrefix) $tag[0] = $this->mPrefix;
			}

			$this->push();
			$this->set('__tags', $tags);	

			return true;
		} else {
			$this->seek($s);
		}

		// closing block
		if ($this->literal('}')) {
			$tags = $this->multiplyTags();
			$env = end($this->env);
			$ctags = $env['__tags'];
			unset($env['__tags']);

			// insert the default arguments
			if (isset($env['__args'])) {
				foreach ($env['__args'] as $arg) {
					if (isset($arg[1])) {
						$this->prepend($this->vPrefix.$arg[0], $arg[1]);
					}
				}
			}

			if (!empty($tags))
				$out = $this->compileBlock($tags, $env);

			$this->pop();

			// make the block(s) available in the new current scope
			if (!isset($env['__dontsave'])) {
				foreach ($ctags as $t) {
					// if the block already exists then merge
					if ($this->get($t, array(end($this->env)))) {
						$this->merge($t, $env);
					} else {
						$this->set($t, $env);
					}
				}
			}

			return isset($out) ? $out : true;
		} 
		
		// import statement
		if ($this->import($url, $media)) {
			if ($this->importDisabled) return "/* import is disabled */\n";

			$full = $this->importDir.$url;
			if (file_exists($file = $full) || file_exists($file = $full.'.less')) {
				$loaded = $this->removeComments(ltrim(file_get_contents($file).";"));
				$this->buffer = substr($this->buffer, 0, $this->count).$loaded.substr($this->buffer, $this->count);
				return true;
			}

			return '@import url("'.$url.'")'.($media ? ' '.$media : '').";\n";
		}

		// setting variable
		if ($this->variable($name) && $this->assign() && $this->propertyValue($value) && $this->end()) {
			$this->append($this->vPrefix.$name, $value);
			return true;
		} else {
			$this->seek($s);
		}

		// mixin/function expand
		if ($this->tags($tags, true, '>') && ($this->argumentValues($argv) || true) && $this->end()) {
			$env = $this->getEnv($tags);
			if ($env == null) return true;

			// if we have arguments then insert them
			if (!empty($env['__args'])) {
				foreach($env['__args'] as $arg) {
					$vname = $this->vPrefix.$arg[0];
					$value = is_array($argv) ? array_shift($argv) : null;
					// copy default value if there isn't one supplied
					if ($value == null && isset($arg[1]))
						$value = $arg[1];

					// if ($value == null) continue; // don't define so it can search up

					// create new entry if var doesn't exist in scope
					if (isset($env[$vname])) {
						array_unshift($env[$vname], $value);
					} else {
						// new element
						$env[$vname] = array($value);
					}
				}
			}

			// set all properties
			ob_start();
			$blocks = array();
			foreach ($env as $name => $value) {
				// skip the metatdata
				if (preg_match('/^__/', $name)) continue;

				// if it is a block, remember it to compile after everything
				// is mixed in
				if (!isset($value[0]))
					$blocks[] = array($name, $value);

				// copy the data
				// don't overwrite previous value, look in current env for name
				if ($this->get($name, array(end($this->env)))) {
					while ($tval = array_shift($value))
						$this->append($name, $tval);
				} else 
					$this->set($name, $value); 
			}

			// render sub blocks
			foreach ($blocks as $b) {
				$rtags = $this->multiplyTags(array($b[0]));
				echo $this->compileBlock($rtags, $b[1]);
			}

			return ob_get_clean();
		} else {
			$this->seek($s);
		}

		// spare ;
		if ($this->literal(';')) return true;

		return false; // couldn't match anything, throw error
	}

	// recursively find the cartesian product of all tags in stack
	function multiplyTags($tags = array(' '), $d = null) {
		if ($d === null) $d = count($this->env) - 1;

		$parents = $d == 0 ? $this->env[$d]['__tags']
			: $this->multiplyTags($this->env[$d]['__tags'], $d - 1);

		$rtags = array();
		foreach ($parents as $p) {
			foreach ($tags as $t) {
				if ($t{0} == $this->mPrefix) continue; // skip functions
				$d = ' ';
				if ($t{0} == ':' || $t{0} == $this->selfSelector) {
					$t = ltrim($t, $this->selfSelector);
					$d = '';
				}
				$rtags[] = trim($p.$d.$t);
			}
		}

		return $rtags;
	}

	// a list of expressions
	function expressionList(&$exps) {
		$values = array();	

		while ($this->expression($exp)) {
			$values[] = $exp;
		}
		
		if (count($values) == 0) return false;

		$exps = $this->compressList($values, ' ');
		return true;
	}

	// a single expression
	function expression(&$out) {
		$s = $this->seek();
		$needWhite = true;
		if ($this->literal('(') && $this->expression($exp) && $this->literal(')')) {
			$lhs = $exp;
			$needWhite = false;
		} elseif ($this->seek($s) && $this->value($val)) {
			$lhs = $val;
		} else {
			return false;
		}

		$out = $this->expHelper($lhs, 0, $needWhite);
		return true;
	}

	// resursively parse infix equation with $lhs at precedence $minP
	function expHelper($lhs, $minP, $needWhite = true) {
		$ss = $this->seek();
		// try to find a valid operator
		while ($this->match(self::$operatorString.($needWhite ? '\s+' : ''), $m) && self::$precedence[$m[1]] >= $minP) {
			$needWhite = true;
			// get rhs
			$s = $this->seek();
			if ($this->literal('(') && $this->expression($exp) && $this->literal(')')) {
				$needWhite = false;
				$rhs = $exp;
			} elseif ($this->seek($s) && $this->value($val)) {
				$rhs = $val;
			} else break;

			// peek for next operator to see what to do with rhs
			if ($this->peek(self::$operatorString, $next) && self::$precedence[$next[1]] > $minP) {
				$rhs = $this->expHelper($rhs, self::$precedence[$next[1]]);
			}

			// don't evaluate yet if it is dynamic
			if (in_array($rhs[0], self::$dtypes) || in_array($lhs[0], self::$dtypes))
				$lhs = array('expression', $m[1], $lhs, $rhs);
			else
				$lhs = $this->evaluate($m[1], $lhs, $rhs);

			$ss = $this->seek();
		}
		$this->seek($ss);

		return $lhs;
	}

	// consume a list of values for a property
	function propertyValue(&$value) {
		$values = array();	
		
		$s = null;
		while ($this->expressionList($v)) {
			$values[] = $v;
			$s = $this->seek();
			if (!$this->literal(',')) break;
		}

		if ($s) $this->seek($s);

		if (count($values) == 0) return false;

		$value = $this->compressList($values, ', ');
		return true;
	}

	// a single value
	function value(&$value) {
		// try a unit
		if ($this->unit($value)) return true;	

		// see if there is a negation
		$s = $this->seek();
		if ($this->literal('-', false) && $this->variable($vname)) {
			$value = array('negative', array('variable', $this->vPrefix.$vname));
			return true;
		} else {
			$this->seek($s);
		}

		// accessor 
		// must be done before color
		// this needs negation too
		if ($this->accessor($a)) {
			$tmp = $this->getEnv($a[0]);
			if ($tmp && isset($tmp[$a[1]]))
				$value = end($tmp[$a[1]]);
			return true;
		}
		
		// color
		if ($this->color($value)) return true;

		// css function
		// must be done after color
		if ($this->func($value)) return true;

		// string
		if ($this->string($tmp, $d)) {
			$value = array('string', $d.$tmp.$d);
			return true;
		}

		// try a keyword
		if ($this->keyword($word)) {
			$value = array('keyword', $word);
			return true;
		}

		// try a variable
		if ($this->variable($vname)) {
			$value = array('variable', $this->vPrefix.$vname);
			return true;
		}

		return false;
	}

	// an import statement
	function import(&$url, &$media) {
		$s = $this->seek();
		if (!$this->literal('@import')) return false;

		// @import "something.css" media;
		// @import url("something.css") media;
		// @import url(something.css) media; 

		if ($this->literal('url(')) $parens = true; else $parens = false;

		if (!$this->string($url)) {
			if ($parens && $this->to(')', $url)) {
				$parens = false; // got em
			} else {
				$this->seek($s);
				return false;
			}
		}

		if ($parens && !$this->literal(')')) {
			$this->seek($s);
			return false;
		}

		// now the rest is media
		return $this->to(';', $media, false, true);
	}

	// a scoped value accessor
	// .hello > @scope1 > @scope2['value'];
	function accessor(&$var) {
		$s = $this->seek();

		if (!$this->tags($scope, true, '>') || !$this->literal('[')) {
			$this->seek($s);
			return false;
		}

		// either it is a variable or a property
		// why is a property wrapped in quotes, who knows!
		if ($this->variable($name)) {
			$name = $this->vPrefix.$name;
		} elseif($this->literal("'") && $this->keyword($name) && $this->literal("'")) {
			// .. $this->count is messed up if we wanted to test another access type
		} else {
			$this->seek($s);
			return false;
		}

		if (!$this->literal(']')) {
			$this->seek($s);
			return false;
		}

		$var = array($scope, $name);
		return true;
	}

	// a string 
	function string(&$string, &$d = null) {
		$s = $this->seek();
		if ($this->literal('"', false)) {
			$delim = '"';
		} else if($this->literal("'", false)) {
			$delim = "'";
		} else {
			return false;
		}

		if (!$this->to($delim, $string)) {
			$this->seek($s);
			return false;
		}
		
		$d = $delim;
		return true;
	}

	// a numerical unit
	function unit(&$unit, $allowed = null) {
		$simpleCase = $allowed == null;
		if (!$allowed) $allowed = self::$units;

		if ($this->match('(-?[0-9]*(\.)?[0-9]+)('.implode('|', $allowed).')?', $m, !$simpleCase)) {
			if (!isset($m[3])) $m[3] = 'number';
			$unit = array($m[3], $m[1]);

			// check for size/height font unit.. should this even be here?
			if ($simpleCase) {
				$s = $this->seek();
				if ($this->literal('/', false) && $this->unit($right, self::$units)) {
					$unit = array('keyword', $this->compileValue($unit).'/'.$this->compileValue($right));
				} else {
					// get rid of whitespace
					$this->seek($s);
					$this->match('', $_);
				}
			}

			return true;
		}

		return false;
	}

	// a # color
	function color(&$out) {
		$color = array('color');

		if ($this->match('(#([0-9a-f]{6})|#([0-9a-f]{3}))', $m)) {
			if (isset($m[3])) {
				$num = $m[3];
				$width = 16;
			} else {
				$num = $m[2];
				$width = 256;
			}

			$num = hexdec($num);
			foreach(array(3,2,1) as $i) {
				$t = $num % $width;
				$num /= $width;

				$color[$i] = $t * (256/$width) + $t * floor(16/$width);
			}
			
			$out = $color;
			return true;
		} 

		return false;
	}

	// consume a list of property values delimited by ; and wrapped in ()
	function argumentValues(&$args, $delim = ';') {
		$s = $this->seek();
		if (!$this->literal('(')) return false;

		$values = array();
		while ($this->propertyValue($value)) {
			$values[] = $value;
			if (!$this->literal($delim)) break;
		}

		if (!$this->literal(')')) {
			$this->seek($s);
			return false;
		}
		
		$args = $values;
		return true;
	}

	// consume an argument definition list surrounded by (), each argument is a variable name with optional value
	function argumentDef(&$args, $delim = ';') {
		$s = $this->seek();
		if (!$this->literal('(')) return false;

		$values = array();
		while ($this->variable($vname)) {
			$arg = array($vname);
			if ($this->assign() && $this->propertyValue($value)) {
				$arg[] = $value;
				// let the : slide if there is no value
			}

			$values[] = $arg;
			if (!$this->literal($delim)) break;
		}

		if (!$this->literal(')')) {
			$this->seek($s);
			return false;
		}

		$args = $values;
		return true;
	}

	// consume a list of tags
	// this accepts a hanging delimiter
	function tags(&$tags, $simple = false, $delim = ',') {
		$tags = array();
		while ($this->tag($tt, $simple)) {
			$tags[] = $tt;
			if (!$this->literal($delim)) break;
		}
		if (count($tags) == 0) return false;

		return true;
	}

	// a single tag
	function tag(&$tag, $simple = false) {
		if ($simple)
			$chars = '^,:;{}\][>\(\) ';
		else
			$chars = '^,;{}[';

		$tag = '';
		while ($this->match('(['.$chars.'0-9]['.$chars.']*)', $m)) {
			$tag.= $m[1];
			if ($simple) break;

			$s = $this->seek();
			if ($this->literal('[') && $this->to(']', $c, true) && $this->literal(']')) {
				$tag .= '['.$c.'] ';
			} else {
				$this->seek($s);
				break;
			}
		}
		$tag = trim($tag);
		if ($tag == '') return false;

		return true;
	}

	// a css function
	function func(&$func) {
		$s = $this->seek();

		if ($this->match('([\w\-_][\w\-_:\.]*)', $m) && $this->literal('(')) {
			$fname = $m[1];
			if ($fname == 'url') {
				$this->to(')', $content, true);
				$args = array('string', $content);
			} else {
				$args = array();
				while (true) {
					$ss = $this->seek();
					if ($this->keyword($name) && $this->literal('=') && $this->expressionList($value)) {
						$args[] = array('list', '=', array(array('keyword', $name), $value));
					} else {
						$this->seek($ss);
						if ($this->expressionList($value)) {
							$args[] = $value;
						}
					}

					if (!$this->literal(',')) break;
				}
				$args = array('list', ',', $args);
			}

			if ($this->literal(')')) {
				$func = array('function', $fname, $args);
				return true;
			}
		}

		$this->seek($s);
		return false;
	}

	// consume a less variable
	function variable(&$name) {
		$s = $this->seek();
		if ($this->literal($this->vPrefix, false) && $this->keyword($name)) {
			return true;	
		}
		return false;
	}

	// consume an assignment operator
	function assign() {
		return $this->literal(':') || $this->literal('=');
	}

	// consume a keyword
	function keyword(&$word) {
		if ($this->match('([\w_\-\*!"][\w\-_"]*)', $m)) {
			$word = $m[1];
			return true;
		}
		return false;
	}

	// consume an end of statement delimiter
	function end() {
		if ($this->literal(';'))
			return true;
		elseif ($this->count == strlen($this->buffer) || $this->buffer{$this->count} == '}') {
			// if there is end of file or a closing block next then we don't need a ;
			return true;
		}
		return false;
	}

	function compressList($items, $delim) {
		if (count($items) == 1) return $items[0];	
		else return array('list', $delim, $items);
	}

	function compileBlock($rtags, $env) {
		// don't render functions
		// todo: this shouldn't need to happen because multiplyTags prunes them, verify
		/*
		foreach ($rtags as $i => $tag) {
			if (preg_match('/( |^)%/', $tag))
				unset($rtags[$i]);
		}
		 */
		if (empty($rtags)) return '';

		$props = 0;
		// print all the visible properties
		ob_start();
		foreach ($env as $name => $value) {
			// todo: change this, poor hack
			// make a better name storage system!!! (value types are fine)
			// but.. don't render special properties (blocks, vars, metadata)
			if (isset($value[0]) && $name{0} != $this->vPrefix && $name != '__args') {
				echo $this->compileProperty($name, $value, 1)."\n";
				$props++;
			}
		}
		$list = ob_get_clean();

		if ($props == 0) return '';

		// do some formatting
		if ($props == 1) $list = ' '.trim($list).' ';
		return implode(", ", $rtags).' {'.($props  > 1 ? "\n" : '').
			$list."}\n";

	}

	function compileProperty($name, $value, $level = 0) {
		// output all repeated properties
		foreach ($value as $v)
			$props[] = str_repeat('  ', $level).
				$name.':'.$this->compileValue($v).';';

		return implode("\n", $props);
	}

	function compileValue($value) {
		switch($value[0]) {
		case 'list':
			// [1] - delimiter
			// [2] - array of values
			return implode($value[1], array_map(array($this, 'compileValue'), $value[2]));
		case 'keyword':
			// [1] - the keyword 
		case 'number':
			// [1] - the number 
			return $value[1];
		case 'expression':
			// [1] - operator
			// [2] - value of left hand side
			// [3] - value of right
			return $this->compileValue($this->evaluate($value[1], $value[2], $value[3]));
		case 'string':
			// [1] - contents of string (includes quotes)
			
			// search for inline variables to replace
			$replace = array();
			if (preg_match_all('/{(@[\w-_][0-9\w-_]*)}/', $value[1], $m)) {
				foreach($m[1] as $name) {
					if (!isset($replace[$name]))
						$replace[$name] = $this->compileValue(array('variable', $name));
				}
			}
			foreach ($replace as $var=>$val) {
				// strip quotes
				if (preg_match('/^(["\']).*?(\1)$/', $val)) {
					$val = substr($val, 1, -1);
				}
				$value[1] = str_replace('{'.$var.'}', $val, $value[1]);
			}

			return $value[1];
		case 'color':
			// [1] - red component (either number for a %)
			// [2] - green component
			// [3] - blue component
			// [4] - optional alpha component
			if (count($value) == 5) { // rgba
				return 'rgba('.$value[1].','.$value[2].','.$value[3].','.$value[4].')';
			}

			$out = '#';
			foreach (range(1,3) as $i)
				$out .= ($value[$i] < 16 ? '0' : '').dechex($value[$i]);
			return $out;
		case 'variable':
			// [1] - the name of the variable including @
			$tmp = $this->compileValue(
				$this->getVal($value[1], $this->pushName($value[1]))
			);
			$this->popName();

			return $tmp;
		case 'negative':
			// [1] - some value that needs to become negative
			return $this->compileValue($this->reduce($value));
		case 'function':
			// [1] - function name
			// [2] - some value representing arguments

			// see if there is a library function for this func
			$f = array($this, 'lib_'.$value[1]);
			if (is_callable($f)) {
				return call_user_func($f, $value[2]);
			}

			return $value[1].'('.$this->compileValue($value[2]).')';

		default: // assumed to be unit	
			return $value[1].$value[0];
		}
	}

	function lib_quote($arg) {
		return '"'.$this->compileValue($arg).'"';
	}

	function lib_unquote($arg) {
		$out = $this->compileValue($arg);
		if ($this->quoted($out)) $out = substr($out, 1, -1);
		return $out;
	}

	// is a string surrounded in quotes? returns the quoting char if true
	function quoted($s) {
		if (preg_match('/^("|\').*?\1$/', $s, $m))
			return $m[1];
		else return false;
	}

	// convert rgb, rgba into color type suitable for math
	// todo: add hsl
	function funcToColor($func) {
		$fname = $func[1];
		if (!preg_match('/^(rgb|rgba)$/', $fname)) return false;
		if ($func[2][0] != 'list') return false; // need a list of arguments

		$components = array();
		$i = 1;
		foreach	($func[2][2] as $c) {
			$c = $this->reduce($c);
			if ($i < 4) {
				if ($c[0] == '%') $components[] = 255 * ($c[1] / 100);
				else $components[] = floatval($c[1]); 
			} elseif ($i == 4) {
				if ($c[0] == '%') $components[] = 1.0 * ($c[1] / 100);
				else $components[] = floatval($c[1]);
			} else break;

			$i++;
		}
		while (count($components) < 3) $components[] = 0;

		array_unshift($components, 'color');
		return $this->fixColor($components);
	}

	// reduce a delayed type to its final value
	// dereference variables and solve equations
	function reduce($var, $defaultValue = array('number', 0)) {
		$pushed = 0; // number of variable names pushed

		while (in_array($var[0], self::$dtypes)) {
			if ($var[0] == 'expression') {
				$var = $this->evaluate($var[1], $var[2], $var[3]);
			} else if ($var[0] == 'variable') {
				$var = $this->getVal($var[1], $this->pushName($var[1]), $defaultValue);
				$pushed++;
			} else if ($var[0] == 'function') {
				$color = $this->funcToColor($var);
				if ($color) $var = $color;
				break; // no where to go after a function
			} else if ($var[0] == 'negative') {
				$value = $this->reduce($var[1]);
				if (is_numeric($value[1])) {
					$value[1] = -1*$value[1];
				} 
				$var = $value;
			}
		}

		while ($pushed != 0) { $this->popName(); $pushed--; }
		return $var;
	}

	// evaluate an expression
	function evaluate($op, $left, $right) {
		$left = $this->reduce($left);
		$right = $this->reduce($right);

		if ($left[0] == 'color' && $right[0] == 'color') {
			$out = $this->op_color_color($op, $left, $right);
			return $out;
		}

		if ($left[0] == 'color') {
			return $this->op_color_number($op, $left, $right);
		}

		if ($right[0] == 'color') {
			return $this->op_number_color($op, $left, $right);
		}

		// concatenate strings
		if ($op == '+' && $left[0] == 'string') {
			$append = $this->compileValue($right);
			if ($this->quoted($append)) $append = substr($append, 1, -1);

			$lhs = $this->compileValue($left);
			if ($q = $this->quoted($lhs)) $lhs = substr($lhs, 1, -1);
			if (!$q) $q = '';

			return array('string', $q.$lhs.$append.$q);
		}

		if ($left[0] == 'keyword' || $right[0] == 'keyword' ||
			$left[0] == 'string' || $right[0] == 'string')
		{
			// look for negative op
			if ($op == '-') $right[1] = '-'.$right[1];
			return array('keyword', $this->compileValue($left) .' '. $this->compileValue($right));
		}
	
		// default to number operation
		return $this->op_number_number($op, $left, $right);
	}

	// make sure a color's components don't go out of bounds
	function fixColor($c) {
		foreach (range(1, 3) as $i) {
			if ($c[$i] < 0) $c[$i] = 0;
			if ($c[$i] > 255) $c[$i] = 255;
			$c[$i] = floor($c[$i]);
		}

		return $c;
	}

	function op_number_color($op, $lft, $rgt) {
		if ($op == '+' || $op = '*') {
			return $this->op_color_number($op, $rgt, $lft);
		}
	}

	function op_color_number($op, $lft, $rgt) {
		if ($rgt[0] == '%') $rgt[1] /= 100;

		return $this->op_color_color($op, $lft,
			array_fill(1, count($lft) - 1, $rgt[1]));
	}

	function op_color_color($op, $left, $right) {
		$out = array('color');
		$max = count($left) > count($right) ? count($left) : count($right);
		foreach (range(1, $max - 1) as $i) {
			$lval = isset($left[$i]) ? $left[$i] : 0;
			$rval = isset($right[$i]) ? $right[$i] : 0;
			switch ($op) {
			case '+':
				$out[] = $lval + $rval;
				break;
			case '-':
				$out[] = $lval - $rval;
				break;
			case '*':
				$out[] = $lval * $rval;
				break;
			case '%':
				$out[] = $lval % $rval;
				break;
			case '/':
				if ($rval == 0) throw new exception("evaluate error: can't divide by zero");
				$out[] = $lval / $rval;
				break;
			default:
				throw new exception('evaluate error: color op number failed on op '.$op);
			}
		}
		return $this->fixColor($out);
	}

	// operator on two numbers
	function op_number_number($op, $left, $right) {
		if ($right[0] == '%') $right[1] /= 100;

		// figure out type
		if ($right[0] == 'number' || $right[0] == '%') $type = $left[0];
		else $type = $right[0];

		$value = 0;
		switch($op) {
		case '+':
			$value = $left[1] + $right[1];
			break;	
		case '*':
			$value = $left[1] * $right[1];
			break;	
		case '-':
			$value = $left[1] - $right[1];
			break;	
		case '%':
			$value = $left[1] % $right[1];
			break;	
		case '/':
			if ($right[1] == 0) throw new exception('parse error: divide by zero');
			$value = $left[1] / $right[1];
			break;
		default:
			throw new exception('parse error: unknown number operator: '.$op);	
		}

		return array($type, $value);
	}


	/* environment functions */

	// push name on expand stack, and return its 
	// count before being pushed
	function pushName($name) {
		$count = array_count_values($this->expandStack);
		$count = isset($count[$name]) ? $count[$name] : 0;

		$this->expandStack[] = $name;

		return $count;
	}

	// pop name off expand stack and return it
	function popName() {
		return array_pop($this->expandStack);
	}

	// push a new environment
	function push() {
		$this->level++;
		$this->env[] = array();
	}

	// pop environment off the stack
	function pop() {
		if ($this->level == 1)
			throw new exception('parse error: unexpected end of block');

		$this->level--;
		return array_pop($this->env);
	}

	// set something in the current env
	function set($name, $value) {
		$this->env[count($this->env) - 1][$name] = $value;
	}

	// append to array in the current env
	function append($name, $value) {
		$this->env[count($this->env) - 1][$name][] = $value;
	}

	// put on the front of the value
	function prepend($name, $value) {
		if (isset($this->env[count($this->env) - 1][$name]))
			array_unshift($this->env[count($this->env) - 1][$name], $value);
		else $this->append($name, $value);
	}

	// get the highest occurrence of value
	function get($name, $env = null) {
		if (empty($env)) $env = $this->env;

		for ($i = count($env) - 1; $i >= 0; $i--)
			if (isset($env[$i][$name])) return $env[$i][$name];

		return null;
	}

	// get the most recent value of a variable
	// return default if it isn't found
	// $skip is number of vars to skip
	function getVal($name, $skip = 0, $default = array('keyword', '')) {
		$val = $this->get($name);
		if ($val == null) return $default;

		$tmp = $this->env;
		while (!isset($tmp[count($tmp) - 1][$name])) array_pop($tmp);
		while ($skip > 0) {
			$skip--;

			if (!empty($val)) {
				array_pop($val);
			}

			if (empty($val)) {
				array_pop($tmp);
				$val = $this->get($name, $tmp);
			}

			if (empty($val)) return $default;
		}

		return end($val);
	}

	// get the environment described by path, an array of env names
	function getEnv($path) {
		if (!is_array($path)) $path = array($path);

		//  move @ tags out of variable namespace
		foreach($path as &$tag)
			if ($tag{0} == $this->vPrefix) $tag[0] = $this->mPrefix;

		$env = $this->get(array_shift($path));
		while ($sub = array_shift($path)) {
			if (isset($env[$sub]))  // todo add a type check for environment
				$env = $env[$sub];
			else {
				$env = null;
				break;
			}
		}
		return $env;
	}

	// merge a block into the current env
	function merge($name, $value) {
		// if the current block isn't there then just set
		$top =& $this->env[count($this->env) - 1];
		if (!isset($top[$name])) return $this->set($name, $value);

		// copy the block into the old one, including meta data
		foreach ($value as $k=>$v) {
			// todo: merge property values instead of replacing
			// have to check type for this
			$top[$name][$k] = $v;
		}
	}

	function literal($what, $eatWhitespace = true) {
		// this is here mainly prevent notice from { } string accessor 
		if ($this->count >= strlen($this->buffer)) return false;

		// shortcut on single letter
		if (!$eatWhitespace and strlen($what) == 1) {
			if ($this->buffer{$this->count} == $what) {
				$this->count++;
				return true;
			}
			else return false;
		}

		return $this->match($this->preg_quote($what), $m, $eatWhitespace);
	}

	function preg_quote($what) {
		return preg_quote($what, '/');
	}

	// advance counter to next occurrence of $what
	// $until - don't include $what in advance
	function to($what, &$out, $until = false, $allowNewline = false) {
		$validChars = $allowNewline ? "[^\n]" : '.';
		if (!$this->match('('.$validChars.'*?)'.$this->preg_quote($what), $m, !$until)) return false;
		if ($until) $this->count -= strlen($what); // give back $what
		$out = $m[1];
		return true;
	}
	
	// try to match something on head of buffer
	function match($regex, &$out, $eatWhitespace = true) {
		$r = '/'.$regex.($eatWhitespace ? '\s*' : '').'/Ais';
		if (preg_match($r, $this->buffer, $out, null, $this->count)) {
			$this->count += strlen($out[0]);
			return true;
		}
		return false;
	}


	// match something without consuming it
	function peek($regex, &$out = null) {
		$r = '/'.$regex.'/Ais';
		$result =  preg_match($r, $this->buffer, $out, null, $this->count);
		
		return $result;
	}

	// seek to a spot in the buffer or return where we are on no argument
	function seek($where = null) {
		if ($where === null) return $this->count;
		else $this->count = $where;
		return true;
	}

	// parse and compile buffer
	function parse($str = null) {
		if ($str) $this->buffer = $str;		

		$this->env = array();
		$this->expandStack = array();
		$this->count = 0;
		$this->line = 1;

		$this->buffer = $this->removeComments($this->buffer);
		$this->push(); // set up global scope
		$this->set('__tags', array('')); // equivalent to 1 in tag multiplication

		// trim whitespace on head
		if (preg_match('/^\s+/', $this->buffer, $m)) {
			$this->line  += substr_count($m[0], "\n");
			$this->buffer = ltrim($this->buffer);
		}

		$out = '';
		while (false !== ($compiled = $this->chunk())) {
			if (is_string($compiled)) $out .= $compiled;
		}

		if ($this->count != strlen($this->buffer)) $this->throwParseError();

		if (count($this->env) > 1)
			throw new exception('parse error: unclosed block');

		// print_r($this->env);
		return $out;
	}

	function throwParseError($msg = 'parse error') {
		$line = $this->line + substr_count(substr($this->buffer, 0, $this->count), "\n");
		if ($this->peek("(.*?)\n", $m))
			throw new exception($msg.': failed at `'.$m[1].'` line: '.$line);
	}

	function __construct($fname = null) {
		if (!self::$operatorString) {
			self::$operatorString = 
				'('.implode('|', array_map(array($this, 'preg_quote'), array_keys(self::$precedence))).')';
		}

		if ($fname) {
			if (!is_file($fname)) {
				throw new Exception('load error: failed to find '.$fname);
			}
			$pi = pathinfo($fname);

			$this->fileName = $fname;
			$this->importDir = $pi['dirname'].'/';
			$this->buffer = file_get_contents($fname);
		}
	}

	// remove comments from $text
	// todo: make it work for all functions, not just url
	// todo: make it not mess up line counter with block comments
	function removeComments($text) {
		$out = '';

		while (!empty($text) &&
			preg_match('/^(.*?)("|\'|\/\/|\/\*|url\(|$)/is', $text, $m))
		{
			if (!trim($text)) break;

			$out .= $m[1];
			$text = substr($text, strlen($m[0]));

			switch ($m[2]) {
			case 'url(':
				preg_match('/^(.*?)(\)|$)/is', $text, $inner);
				$text = substr($text, strlen($inner[0]));
				$out .= $m[2].$inner[1].$inner[2];
				break;
			case '//':
				preg_match("/^(.*?)(\n|$)/is", $text, $inner);
				// give back the newline
				$text = substr($text, strlen($inner[0]) - 1);
				break;
			case '/*';
				preg_match("/^(.*?)(\*\/|$)/is", $text, $inner);
				$text = substr($text, strlen($inner[0]));
				break;
			case '"':
			case "'":
				preg_match("/^(.*?)(".$m[2]."|$)/is", $text, $inner);
				$text = substr($text, strlen($inner[0]));
				$out .= $m[2].$inner[1].$inner[2];
				break;
			}
		}

		return $out;
	}


	// compile to $in to $out if $in is newer than $out
	// returns true when it compiles, false otherwise
	public static function ccompile($in, $out) {
		if (!is_file($out) || filemtime($in) > filemtime($out)) {
			$less = new lessc($in);
			file_put_contents($out, $less->parse());
			return true;
		}

		return false;
	}

}

