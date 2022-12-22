<?php

namespace FeedReader\IcoParser;

class IconImage {
	public $width;
	public $height;
	public $colorCount;
	public $reserved;
	public $planes;
	public $bitCount;
	public $sizeInBytes;
	public $fileOffset;
	public $bmpHeaderSize;
	public $bmpHeaderWidth;
	public $bmpHeaderHeight;

	public $pngData = null;
	public $bmpData = null;
	public $palette = array();

	public function __construct( $data ) {
		foreach ( $data as $name => $value ) {
			$this->$name = $value;
		}
	}

	public function getDescription() {
		return sprintf(
			'%dx%d pixel %s @ %d bits/pixel',
			$this->width,
			$this->height,
			$this->isPng() ? 'PNG' : 'BMP',
			$this->bitCount
		);
	}

	public function setPngData( $pngData ) {
		$this->pngData = $pngData;
	}

	public function isPng() {
		return ! empty( $this->pngData );
	}

	public function isBmp() {
		return empty( $this->pngData );
	}

	public function setBitmapInfoHeader( $bmpInfo ) {
		// `ICONDIRENTRY` bit depth can be zero; we trust the bitmap header more.
		$this->bitCount = $bmpInfo['BitCount'];

		// Need this to calculate offsets when rendering.
		$this->bmpHeaderWidth  = $bmpInfo['Width'];
		$this->bmpHeaderHeight = $bmpInfo['Height'];
		$this->bmpHeaderSize   = $bmpInfo['Size'];
	}

	public function setBitmapData( $bmpData ) {
		$this->bmpData = $bmpData;
	}

	public function addToBmpPalette( $r, $g, $b, $reserved ) {
		$this->palette[] = array(
			'red'      => $r,
			'green'    => $g,
			'blue'     => $b,
			'reserved' => $reserved,
		);
	}
}
