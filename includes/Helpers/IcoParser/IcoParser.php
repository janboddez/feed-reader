<?php
/**
 * This small package is essentially a fork of the Elphin IcoFileLoader,
 * slightly simplified and somewhat tweaked for backward compatibility, and
 * consistency.
 *
 * @copyright 2005-2022 Diogo Resende, Paul Dixon
 * @license MIT
 * @link https://github.com/lordelph/icofileloader
 */

namespace FeedReader\Helpers\IcoParser;

class IcoParser {
	/**
	 * @param  string $data            Binary image or icon data.
	 * @return resource|\GdImage|false Instance of `\GdImage`, or `false` if the data format is not supported.
	 */
	public function parse( $data ) {
		try {
			// Attempt to load as `.ico`.
			$icon = $this->parseIco( $data );
			$im   = ( new GdRenderer() )->render( $icon->findBest() );
		} catch ( \Exception $e ) {
			$im = imagecreatefromstring( $data );
		}

		return $im;
	}

	/**
	 * @param  string $data
	 * @return Icon
	 *
	 * @throws \Exception
	 */
	protected function parseIco( $data ) {
		$iconDir = $this->parseIconDir( $data );

		if ( ! $iconDir ) {
			throw new \DomainException( 'Invalid file format' );
		}

		$data = substr( $data, 6 );

		$icon      = new Icon();
		$data      = $this->parseIconDirEntries( $icon, $data, $iconDir['Count'] );
		$iconCount = count( $icon );

		for ( $i = 0; $i < $iconCount; $i++ ) {
			if ( $this->isPng( substr( $data, $icon[ $i ]->fileOffset, 4 ) ) ) {
				$this->parsePng( $icon[ $i ], $data );
			} else {
				$this->parseBmp( $icon[ $i ], $data );
			}
		}

		return $icon;
	}

	/**
	 * @param  string $data
	 * @return array|null
	 */
	protected function parseIconDir( $data ) {
		$iconDir = unpack( 'SReserved/SType/SCount', $data );

		if ( ! isset( $iconDir['Reserved'] ) || 0 !== $iconDir['Reserved'] ) {
			return null;
		}

		if ( ! isset( $iconDir['Type'] ) || 1 !== $iconDir['Type'] ) {
			return null;
		}

		return $iconDir;
	}

	/**
	 * @param  Icon   $icon
	 * @param  string $data
	 * @param  int    $count
	 * @return string
	 */
	protected function parseIconDirEntries( Icon $icon, $data, $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$iconDirEntry = unpack(
				'Cwidth/Cheight/CcolorCount/Creserved/Splanes/SbitCount/LsizeInBytes/LfileOffset',
				$data
			);

			$iconDirEntry['fileOffset'] -= ( $count * 16 ) + 6;

			$iconDirEntry['colorCount'] = ! empty( $iconDirEntry['colorCount'] ) ? $iconDirEntry['colorCount'] : 256;
			$iconDirEntry['width']      = ! empty( $iconDirEntry['width'] ) ? $iconDirEntry['width'] : 256;
			$iconDirEntry['height']     = ! empty( $iconDirEntry['height'] ) ? $iconDirEntry['height'] : 256;

			$image  = new IconImage( $iconDirEntry );
			$icon[] = $image;

			$data = substr( $data, 16 );
		}

		return $data;
	}

	/**
	 * @param  IconImage $image
	 * @param  string    $data
	 * @return void
	 */
	protected function parsePng( IconImage $image, $data ) {
		$png = substr( $data, $image->fileOffset, $image->sizeInBytes );

		$image->setPngData( $png );
	}

	/**
	 * @param  IconImage $image
	 * @param  string    $data
	 * @return void
	 */
	protected function parseBmp( IconImage $image, $data ) {
		$bitmapInfoHeader = unpack(
			'LSize/LWidth/LHeight/SPlanes/SBitCount/LCompression/LImageSize/LXpixelsPerM/LYpixelsPerM/LColorsUsed/LColorsImportant',
			substr( $data, $image->fileOffset, 40 )
		);

		$image->setBitmapInfoHeader( $bitmapInfoHeader );

		switch ( $image->bitCount ) {
			case 32:
			case 24:
				$this->parseTrueColorImageData( $image, $data );
				break;

			case 8:
			case 4:
			case 1:
				$this->parsePaletteImageData( $image, $data );
				break;
		}
	}

	/**
	 * @param  IconImage $image
	 * @param  string    $data
	 * @return void
	 */
	protected function parseTrueColorImageData( IconImage $image, $data ) {
		$length  = $image->bmpHeaderWidth * $image->bmpHeaderHeight * ( $image->bitCount / 8 );
		$bmpData = substr( $data, $image->fileOffset + $image->bmpHeaderSize, $length );

		$image->setBitmapData( $bmpData );
	}

	protected function parsePaletteImageData( IconImage $image, $data ) {
		$pal = substr( $data, $image->fileOffset + $image->bmpHeaderSize, $image->colorCount * 4 );
		$idx = 0;

		for ( $i = 0; $i < $image->colorCount; $i++ ) {
			$image->addToBmpPalette(
				ord( $pal[ $idx + 2 ] ),
				ord( $pal[ $idx + 1 ] ),
				ord( $pal[ $idx ] ),
				ord( $pal[ $idx + 3 ] )
			);

			$idx += 4;
		}

		$length  = $image->bmpHeaderWidth * $image->bmpHeaderHeight * ( 1 + $image->bitCount ) / $image->bitCount;
		$bmpData = substr( $data, $image->fileOffset + $image->bmpHeaderSize + $image->colorCount * 4, $length );

		$image->setBitmapData( $bmpData );
	}

	/**
	 * @param  string $data
	 * @return bool
	 */
	protected function isPng( $data ) {
		$signature = unpack( 'LFourCC', $data );

		return isset( $signature['FourCC'] ) && 0x474e5089 === $signature['FourCC'];
	}
}
