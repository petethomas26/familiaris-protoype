<?php

namespace App\Debug;

 class DebugExtension extends \Twig_Extension {

 	public function getFunctions() {
 		return [
 			new \Twig_SimpleFunction('dump', [$this, 'dump']),
 		];
 	}

 	public function dump($var) {
 		return dump($var);
 	}

 }