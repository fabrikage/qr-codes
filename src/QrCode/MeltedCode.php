<?php

namespace Fabrikage\QR\QrCode;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRMarkupSVG;
use Closure;

class MeltedCode extends QRMarkupSVG
{
	protected function path(string $path, int $M_TYPE): string
	{
		// omit the "fill" and "opacity" attributes on the path element
		return sprintf('<path class="%s" d="%s"/>', $this->getCssClass($M_TYPE), $path);
	}

	protected function collectModules(Closure $transform): array
	{
		$paths = [];
		$melt  = $this->options->melt; // avoid magic getter in long loops

		// collect the modules for each type
		foreach ($this->matrix->getMatrix() as $y => $row) {
			foreach ($row as $x => $M_TYPE) {
				$M_TYPE_LAYER = $M_TYPE;

				if ($this->connectPaths && !$this->matrix->checkTypeIn($x, $y, $this->excludeFromConnect)) {
					// to connect paths we'll redeclare the $M_TYPE_LAYER to data only
					$M_TYPE_LAYER = QRMatrix::M_DATA;

					if ($this->matrix->isDark($M_TYPE)) {
						$M_TYPE_LAYER = QRMatrix::M_DATA_DARK;
					}
				}

				// if we're going to "melt" the matrix, we'll declare *all* modules as dark,
				// so that light modules with dark parts are rendered in the same path
				if ($melt) {
					$M_TYPE_LAYER |= QRMatrix::IS_DARK;
				}

				// collect the modules per $M_TYPE
				$module = $transform($x, $y, $M_TYPE, $M_TYPE_LAYER);

				if (!empty($module)) {
					$paths[$M_TYPE_LAYER][] = $module;
				}
			}
		}

		// beautify output
		ksort($paths);

		return $paths;
	}

	protected function module(int $x, int $y, int $M_TYPE): string
	{
		$bits     = $this->matrix->checkNeighbours($x, $y, null);
		$check    = fn(int $all, int $any = 0): bool => ($bits & ($all | (~$any & 0xff))) === $all;

		$template = ($M_TYPE & QRMatrix::IS_DARK) === QRMatrix::IS_DARK
			? $this->darkModule($check, $this->options->inverseMelt)
			: $this->lightModule($check, $this->options->inverseMelt);

		$r = $this->options->meltRadius;

		return sprintf($template, $x, $y, $r, (1 - $r), (1 - 2 * $r));
	}

	/**
	 * returns a dark module for the given values
	 */
	protected function darkModule(Closure $check, bool $invert): string
	{

		switch (true) {
				// 4 rounded
			case !$invert && $check(0b00000000, 0b01010101):
			case  $invert && $check(0b00000000, 0b00000000):
				return 'M%1$s,%2$s m0,%3$s v%5$s q0,%3$s %3$s,%3$s h%5$s q%3$s,0 %3$s,-%3$s v-%5$s q0,-%3$s -%3$s,-%3$s h-%5$s q-%3$s,0 -%3$s,%3$sZ';

				// 3 rounded
			case $invert && $check(0b01000000, 0b00000000):  // 135
				return 'M%1$s,%2$s m0,1 h%4$s q%3$s,0 %3$s,-%3$s v-%5$s q0,-%3$s -%3$s,-%3$s h-%5$s q-%3$s,0 -%3$s,%3$sZ';
			case $invert && $check(0b00000001, 0b00000000):  // 357
				return 'M%1$s,%2$s v%4$s q0,%3$s %3$s,%3$s h%5$s q%3$s,0 %3$s,-%3$s v-%5$s q0,-%3$s -%3$s,-%3$sZ';
			case $invert && $check(0b00000100, 0b00000000):  // 571
				return 'M%1$s,%2$s m1,0 v%4$s q0,%3$s -%3$s,%3$s h-%5$s q-%3$s,0 -%3$s,-%3$s v-%5$s q0,-%3$s %3$s,-%3$sZ';
			case $invert && $check(0b00010000, 0b00000000):  // 713
				return 'M%1$s,%2$s m1,1 h-%4$s q-%3$s,0 -%3$s,-%3$s v-%5$s q0,-%3$s %3$s,-%3$s h%5$s q%3$s,0 %3$s,%3$sZ';

				// 2 rounded
			case !$invert && $check(0b00100000, 0b01010101): // 13
			case  $invert && $check(0b00000000, 0b01110000):
				return 'M%1$s,%2$s m0,1 h1 v-%4$s q0,-%3$s -%3$s,-%3$s h-%5$s q-%3$s,0 -%3$s,%3$sZ';
			case !$invert && $check(0b10000000, 0b01010101): // 35
			case  $invert && $check(0b00000000, 0b11000001):
				return 'M%1$s,%2$s v1 h%4$s q%3$s,0 %3$s,-%3$s v-%5$s q0,-%3$s -%3$s,-%3$sZ';
			case !$invert && $check(0b00000010, 0b01010101): // 57
			case  $invert && $check(0b00000000, 0b00000111):
				return 'M%1$s,%2$s v%4$s q0,%3$s %3$s,%3$s h%5$s q%3$s,0 %3$s,-%3$s v-%4$sZ';
			case !$invert && $check(0b00001000, 0b01010101): // 71
			case  $invert && $check(0b00000000, 0b00011100):
				return 'M%1$s,%2$s m1,1 v-1 h-%4$s q-%3$s,0 -%3$s,%3$s v%5$s q0,%3$s %3$s,%3$sZ';
				// diagonal
			case  $invert && $check(0b01000100, 0b00000000):  // 15
				return 'M%1$s,%2$s m0,1 h%4$s q%3$s,0 %3$s,-%3$s v-%4$s h-%4$s q-%3$s,0 -%3$s,%3$sZ';
			case  $invert && $check(0b00010001, 0b00000000):  // 37
				return 'M%1$s,%2$s h%4$s q%3$s,0 %3$s,%3$s v%4$s h-%4$s q-%3$s,0 -%3$s,-%3$sZ';

				// 1 rounded
			case !$invert && $check(0b00101000, 0b01010101): // 1
			case  $invert && $check(0b00000000, 0b01111100):
				return 'M%1$s,%2$s m0,1 h1 v-1 h-%4$s q-%3$s,0 -%3$s,%3$sZ';
			case !$invert && $check(0b10100000, 0b01010101): // 3
			case  $invert && $check(0b00000000, 0b11110001):
				return 'M%1$s,%2$s h%4$s q%3$s,0 %3$s,%3$s v%4$s h-1Z';
			case !$invert && $check(0b10000010, 0b01010101): // 5
			case  $invert && $check(0b00000000, 0b11000111):
				return 'M%1$s,%2$s h1 v%4$s q0,%3$s -%3$s,%3$s h-%4$sZ';
			case !$invert && $check(0b00001010, 0b01010101): // 7
			case  $invert && $check(0b00000000, 0b00011111):
				return 'M%1$s,%2$s v%4$s q0,%3$s %3$s,%3$s h%4$s v-1Z';
			default:
				// full square
				return 'M%1$s,%2$s h1 v1 h-1Z';
		}
	}

	/**
	 * returns a light module for the given values
	 */
	protected function lightModule(Closure $check, bool $invert): string
	{

		switch (true) {
				// 4 rounded
			case !$invert && $check(0b11111111, 0b01010101):
			case  $invert && $check(0b10101010, 0b01010101):
				return 'M%1$s,%2$s h%3$s q-%3$s,0 -%3$s,%3$sz m1,0 v%3$s q0,-%3$s -%3$s,-%3$sz m0,1 h-%3$s q%3$s,0 %3$s,-%3$sz m-1,0 v-%3$s q0,%3$s %3$s,%3$sZ';

				// 3 rounded
			case !$invert && $check(0b10111111, 0b00000000):  // 135
				return 'M%1$s,%2$s h%3$s q-%3$s,0 -%3$s,%3$sz m1,0 v%3$s q0,-%3$s -%3$s,-%3$sz m0,1 h-%3$s q%3$s,0 %3$s,-%3$sZ';
			case !$invert && $check(0b11111110, 0b00000000):  // 357
				return 'M%1$s,%2$s m1,0 v%3$s q0,-%3$s -%3$s,-%3$sz m0,1 h-%3$s q%3$s,0 %3$s,-%3$sz m-1,0 v-%3$s q0,%3$s %3$s,%3$sZ';
			case !$invert && $check(0b11111011, 0b00000000):  // 571
				return 'M%1$s,%2$s h%3$s q-%3$s,0 -%3$s,%3$sz m0,1 v-%3$s q0,%3$s %3$s,%3$sz m1,0 h-%3$s q%3$s,0 %3$s,-%3$sZ';
			case !$invert && $check(0b11101111, 0b00000000):  // 713
				return 'M%1$s,%2$s h%3$s q-%3$s,0 -%3$s,%3$sz m0,1 v-%3$s q0,%3$s %3$s,%3$sz m1,-1 v%3$s q0,-%3$s -%3$s,-%3$sZ';

				// 2 rounded
			case !$invert && $check(0b10001111, 0b01110000): // 13
			case  $invert && $check(0b10001010, 0b01010101):
				return 'M%1$s,%2$s h%3$s q-%3$s,0 -%3$s,%3$sz m1,0 v%3$s q0,-%3$s -%3$s,-%3$sZ';
			case !$invert && $check(0b00111110, 0b11000001): // 35
			case  $invert && $check(0b00101010, 0b01010101):
				return 'M%1$s,%2$s m1,0 v%3$s q0,-%3$s -%3$s,-%3$sz m0,1 h-%3$s q%3$s,0 %3$s,-%3$sZ';
			case !$invert && $check(0b11111000, 0b00000111): // 57
			case  $invert && $check(0b10101000, 0b01010101):
				return 'M%1$s,%2$s m1,1 h-%3$s q%3$s,0 %3$s,-%3$sz m-1,0 v-%3$s q0,%3$s %3$s,%3$sZ';
			case !$invert && $check(0b11100011, 0b00011100): // 71
			case  $invert && $check(0b10100010, 0b01010101):
				return 'M%1$s,%2$s h%3$s q-%3$s,0 -%3$s,%3$sz m0,1 v-%3$s q0,%3$s %3$s,%3$sZ';
				// diagonal
			case !$invert && $check(0b10111011, 0b00000000): // 15
				return 'M%1$s,%2$s h%3$s q-%3$s,0 -%3$s,%3$sz m1,1 h-%3$s q%3$s,0 %3$s,-%3$sZ';
			case !$invert && $check(0b11101110, 0b00000000): // 37
				return 'M%1$s,%2$s m1,0 v%3$s q0,-%3$s -%3$s,-%3$sz m-1,1 v-%3$s q0,%3$s %3$s,%3$sZ';

				// 1 rounded
			case !$invert && $check(0b10000011, 0b01111100): // 1
			case  $invert && $check(0b10000010, 0b01010101):
				return 'M%1$s,%2$s h%3$s q-%3$s,0 -%3$s,%3$sZ';
			case !$invert && $check(0b00001110, 0b11110001): // 3
			case  $invert && $check(0b00001010, 0b01010101):
				return 'M%1$s,%2$s m1,0 v%3$s q0,-%3$s -%3$s,-%3$sZ';
			case !$invert && $check(0b00111000, 0b11000111): // 5
			case  $invert && $check(0b00101000, 0b01010101):
				return 'M%1$s,%2$s m1,1 h-%3$s q%3$s,0 %3$s,-%3$sZ';
			case !$invert && $check(0b11100000, 0b00011111): // 7
			case  $invert && $check(0b10100000, 0b01010101):
				return 'M%1$s,%2$s m0,1 v-%3$s q0,%3$s %3$s,%3$sZ';
			default:
				// empty block
				return '';
		}
	}
}