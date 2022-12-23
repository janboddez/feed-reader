<?php

namespace FeedReader\Helpers\IcoParser;

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

	/**
	 * @param  array $data
	 * @return void
	 */
	public function __construct( $data ) {
		foreach ( $data as $name => $value ) {
			$this->$name = $value;
		}
	}

	/**
	 * @param  string $pngData
	 * @return void
	 */
	public function setPngData( $pngData ) {
		$this->pngData = $pngData;
	}

	/**
	 * @return bool
	 */
	public function isPng() {
		return ! empty( $this->pngData );
	}

	/**
	 * @param  array $bmpInfo
	 * @return void
	 */
	public function setBitmapInfoHeader( $bmpInfo ) {
		// `ICONDIRENTRY` bit depth can be zero; we trust the bitmap header more.
		$this->bitCount = $bmpInfo['BitCount'];

		// Need this to calculate offsets when rendering.
		$this->bmpHeaderWidth  = $bmpInfo['Width'];
		$this->bmpHeaderHeight = $bmpInfo['Height'];
		$this->bmpHeaderSize   = $bmpInfo['Size'];
	}

	/**
	 * @param  string $bmpData
	 * @return void
	 */
	public function setBitmapData( $bmpData ) {
		$this->bmpData = $bmpData;
	}

	/**
	 * @param  int $red
	 * @param  int $green
	 * @param  int $blue
	 * @param  int $reserved
	 * @return void
	 */
	public function addToBmpPalette( $red, $green, $blue, $reserved ) {
		$this->palette[] = array(
			'red'      => $red,
			'green'    => $green,
			'blue'     => $blue,
			'reserved' => $reserved,
		);
	}
}
