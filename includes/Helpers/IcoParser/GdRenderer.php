<?php

namespace FeedReader\Helpers\IcoParser;

class GdRenderer {
	/**
	 * @param  IconImage $image
	 * @return resource|\GdImage|false
	 */
	public function render( $image ) {
		if ( $image->isPng() ) {
			$im = $this->renderPngImage( $image );
		} else {
			$im = $this->renderBmpImage( $image );
		}

		return $im;
	}

	/**
	 * @param  IconImage $image
	 * @return resource|\GdImage|false
	 */
	protected function renderPngImage( $image ) {
		$im = imagecreatefromstring( $image->pngData );
		imagesavealpha( $im, true );

		return $im;
	}

	/**
	 * @param  IconImage $image
	 * @return resource|\GdImage|false
	 */
	protected function renderBmpImage( $image ) {
		// Create image filled with desired background color.
		$w  = $image->width;
		$h  = $image->height;
		$im = imagecreatetruecolor( $w, $h );

		imagealphablending( $im, false );
		$colVal = $this->allocateColor( $im, 255, 255, 255, 127 );
		imagefilledrectangle( $im, 0, 0, $w, $h, $colVal );
		imagesavealpha( $im, true );

		// Now paint pixels based on bit count.
		switch ( $image->bitCount ) {
			case 32:
				$this->render32bit( $image, $im );
				break;

			case 24:
				$this->render24bit( $image, $im );
				break;

			case 8:
				$this->render8bit( $image, $im );
				break;

			case 4:
				$this->render4bit( $image, $im );
				break;

			case 1:
				$this->render1bit( $image, $im );
				break;
		}

		return $im;
	}

	/**
	 * @param  IconImage         $image
	 * @param  resource|\GdImage $im
	 * @return void
	 */
	protected function render32bit( $image, $im ) {
		$offset = 0;
		$binary = $image->bmpData;

		for ( $i = $image->height - 1; $i >= 0; $i-- ) {
			for ( $j = 0; $j < $image->width; $j++ ) {
				// We translate the BGRA to aRGB ourselves, which is twice as
				// fast as calling `imagecolorallocatealpha`.
				$alpha7 = ( ( ~ord( $binary[ $offset + 3 ] ) ) & 0xff ) >> 1;

				if ( $alpha7 < 127 ) {
					$col = ( $alpha7 << 24 ) |
						( ord( $binary[ $offset + 2 ] ) << 16 ) |
						( ord( $binary[ $offset + 1 ] ) << 8 ) |
						( ord( $binary[ $offset ] ) );

					imagesetpixel( $im, $j, $i, $col );
				}

				$offset += 4;
			}
		}
	}

	/**
	 * @param  IconImage         $image
	 * @param  resource|\GdImage $im
	 * @return void
	 */
	protected function render24bit( $image, $im ) {
		$offset   = 0;
		$binary   = $image->bmpData;
		$maskBits = $this->buildMaskBits( $image );
		$maskpos  = 0;

		for ( $i = $image->height - 1; $i >= 0; $i-- ) {
			for ( $j = 0; $j < $image->width; $j++ ) {
				if ( 0 == $maskBits[ $maskpos ] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					// Translate BGR to RGB.
					$col = ( ord( $binary[ $offset + 2 ] ) << 16 )
						| ( ord( $binary[ $offset + 1 ] ) << 8 )
						| ( ord( $binary[ $offset ] ) );

					imagesetpixel( $im, $j, $i, $col );
				}

				$offset += 3;
				$maskpos++;
			}
		}
	}

	/**
	 * @param  IconImage         $image
	 * @param  resource|\GdImage $im
	 * @return void
	 */
	protected function render8bit( $image, $im ) {
		$palette  = $this->buildPalette( $image, $im );
		$maskBits = $this->buildMaskBits( $image );
		$offset   = 0;

		for ( $i = $image->height - 1; $i >= 0; $i-- ) {
			for ( $j = 0; $j < $image->width; $j++ ) {
				if ( 0 == $maskBits[ $offset ] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$color = ord( $image->bmpData[ $offset ] );
					imagesetpixel( $im, $j, $i, $palette[ $color ] );
				}

				$offset++;
			}
		}
	}

	/**
	 * @param  IconImage         $image
	 * @param  resource|\GdImage $im
	 * @return void
	 */
	protected function render4bit( $image, $im ) {
		$palette    = $this->buildPalette( $image, $im );
		$maskBits   = $this->buildMaskBits( $image );
		$offset     = 0;
		$maskoffset = 0;

		for ( $i = $image->height - 1; $i >= 0; $i-- ) {
			for ( $j = 0; $j < $image->width; $j += 2 ) {
				$colorByte  = ord( $image->bmpData[ $offset ] );
				$lowNibble  = $colorByte & 0x0f;
				$highNibble = ( $colorByte & 0xf0 ) >> 4;

				if ( 0 == $maskBits[ $maskoffset++ ] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					imagesetpixel( $im, $j, $i, $palette[ $highNibble ] );
				}

				if ( 0 == $maskBits[ $maskoffset++ ] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					imagesetpixel( $im, $j + 1, $i, $palette[ $lowNibble ] );
				}

				$offset++;
			}
		}
	}

	/**
	 * @param  IconImage         $image
	 * @param  resource|\GdImage $im
	 * @return void
	 */
	protected function render1bit( $image, $im ) {
		$palette   = $this->buildPalette( $image, $im );
		$maskBits  = $this->buildMaskBits( $image );
		$colorbits = '';
		$total     = strlen( $image->bmpData );

		for ( $i = 0; $i < $total; $i++ ) {
			$colorbits .= str_pad( decbin( ord( $image->bmpData[ $i ] ) ), 8, '0', STR_PAD_LEFT );
		}

		$offset = 0;

		for ( $i = $image->height - 1; $i >= 0; $i-- ) {
			for ( $j = 0; $j < $image->width; $j++ ) {
				if ( 0 == $maskBits[ $offset ] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					imagesetpixel( $im, $j, $i, $palette[ $colorbits[ $offset ] ] );
				}

				$offset++;
			}
		}
	}

	/**
	 * @param  IconImage $image
	 * @return string
	 */
	protected function buildMaskBits( $image ) {
		$width = $image->width;

		if ( ( $width % 32 ) > 0 ) {
			$width += ( 32 - ( $image->width % 32 ) );
		}

		$offset          = $image->width * $image->height * $image->bitCount / 8;
		$total_bytes     = $width * $image->height / 8;
		$maskBits        = '';
		$bytes           = 0;
		$bytes_per_line  = $image->width / 8;
		$bytes_to_remove = ( $width - $image->width ) / 8;

		for ( $i = 0; $i < $total_bytes; ++$i ) {
			$maskBits .= str_pad( decbin( ord( $image->bmpData[ $offset + $i ] ) ), 8, '0', STR_PAD_LEFT );
			$bytes++;

			if ( $bytes === $bytes_per_line ) {
				$i    += $bytes_to_remove;
				$bytes = 0;
			}
		}

		return $maskBits;
	}

	/**
	 * @param  IconImage         $image
	 * @param  resource|\GdImage $im
	 * @return array
	 */
	protected function buildPalette( $image, $im ) {
		$palette = array();

		if ( 24 !== $image->bitCount ) {
			for ( $i = 0; $i < $image->colorCount; $i++ ) {
				$palette[ $i ] = $this->allocateColor(
					$im,
					$image->palette[ $i ]['red'],
					$image->palette[ $i ]['green'],
					$image->palette[ $i ]['blue'],
					round( $image->palette[ $i ]['reserved'] / 255 * 127 )
				);
			}
		}

		return $palette;
	}

	protected function allocateColor( $im, $red, $green, $blue, $alpha = 0 ) {
		$c = imagecolorexactalpha( $im, $red, $green, $blue, $alpha );

		if ( $c >= 0 ) {
			return $c;
		}

		return imagecolorallocatealpha( $im, $red, $green, $blue, $alpha );
	}
}
