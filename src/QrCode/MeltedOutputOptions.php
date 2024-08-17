<?php

namespace Fabrikage\QR\QrCode;

use chillerlan\QRCode\QROptions;

class MeltedOutputOptions extends QROptions
{

	/**
	 * enable "melt" effect
	 */
	protected bool $melt = false;

	/**
	 * whether to let the melt effect flow along the dark or light modules
	 */
	protected bool $inverseMelt = false;

	/**
	 * the corner radius for melted modules
	 */
	protected float $meltRadius = 0.15;

	/**
	 * clamp/set melt corner radius
	 */
	protected function set_meltRadius(float $meltRadius): void
	{
		$this->meltRadius = max(0.01, min(0.5, $meltRadius));
	}
}