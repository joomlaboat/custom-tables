<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

class compareImages
{
	public function compare($a, $b)
	{
		/*main function. returns the hammering distance of two images' bit value*/
		$i1 = $this->createImage($a);
		$i2 = $this->createImage($b);
		if (!$i1 || !$i2) {
			return false;
		}
		$i1 = $this->resizeImage($a);
		$i2 = $this->resizeImage($b);
		imagefilter($i1, IMG_FILTER_GRAYSCALE);
		imagefilter($i2, IMG_FILTER_GRAYSCALE);
		$colorMean1 = $this->colorMeanValue($i1);
		$colorMean2 = $this->colorMeanValue($i2);
		$bits1 = $this->bits($colorMean1);
		$bits2 = $this->bits($colorMean2);
		$hammeringDistance = 0;
		for ($a = 0; $a < 64; $a++) {
			if ($bits1[$a] != $bits2[$a]) {
				$hammeringDistance++;
			}
		}
		return $hammeringDistance;
	}

	private function createImage($i)
	{
		/*returns image resource or false if it's not jpg or png*/
		$mime = $this->mimeType($i);
		if ($mime[2] == 'jpg') {
			return imagecreatefromjpeg($i);
		} else if ($mime[2] == 'png') {
			return imagecreatefrompng($i);
		} else {
			return false;
		}
	}

	private function mimeType($i)
	{
		/*returns array with mime type and if its jpg or png. Returns false if it isn't jpg or png*/
		$mime = getimagesize($i);
		$return = array($mime[0], $mime[1]);
		switch ($mime['mime']) {
			case 'image/jpeg':
				$return[] = 'jpg';
				return $return;
			case 'image/png':
				$return[] = 'png';
				return $return;
			default:
				return false;
		}
	}

	private function resizeImage($source)
	{
		/*resizes the image to a 8x8 square and returns as image resource*/
		$mime = $this->mimeType($source);
		$t = imagecreatetruecolor(8, 8);
		$source = $this->createImage($source);
		imagecopyresized($t, $source, 0, 0, 0, 0, 8, 8, $mime[0], $mime[1]);
		return $t;
	}

	private function colorMeanValue($i): array
	{
		/*returns the mean value of the colors and the list of all pixel's colors*/
		$colorList = array();
		$colorSum = 0;
		for ($a = 0; $a < 8; $a++) {
			for ($b = 0; $b < 8; $b++) {
				$rgb = imagecolorat($i, $a, $b);
				$colorList[] = $rgb & 0xFF;
				$colorSum += $rgb & 0xFF;
			}
		}
		return array($colorSum / 64, $colorList);
	}

	private function bits($colorMean): array
	{
		/*returns an array with 1 and zeros. If a color is bigger than the mean value of colors it is 1*/
		$bits = array();
		foreach ($colorMean[1] as $color) {
			$bits[] = ($color >= $colorMean[0]) ? 1 : 0;
		}

		return $bits;

	}
}


