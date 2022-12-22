<?php

namespace FeedReader\IcoParser;

class Icon implements \ArrayAccess, \Countable, \Iterator {
	protected $images   = array();
	protected $position = 0;

	/**
	 * Returns the highest quality image in the icon.
	 *
	 * @return IconImage|null
	 */
	public function findBest() {
		$bestBitCount = 0;
		$bestWidth    = 0;
		$best         = null;

		foreach ( $this->images as $image ) {
			if ( $image->width > $bestWidth || ( $image->width === $bestWidth && $image->bitCount > $bestBitCount ) ) {
				$bestWidth    = $image->width;
				$bestBitCount = $image->bitCount;
				$best         = $image;
			}
		}

		return $best;
	}

	/**
	 * Implementation of `\Countable`, allowing you to do `count( $icon )`.
	 */
	public function count() {
		return count( $this->images );
	}

	/**
	 * Implementation of `\ArrayAccess`, allowing you to do `$icon[ $x ] = $image`.
	 *
	 * @param integer   $offset
	 * @param IconImage $value
	 *
	 * @throws \InvalidArgumentException
	 */
	public function offsetSet( $offset, $value ) {
		if ( ! $value instanceof IconImage ) {
			throw new \InvalidArgumentException( 'Can only add `IconImage` instances' );
		}

		if ( is_null( $offset ) ) {
			$this->images[] = $value;
		} else {
			$this->images[ $offset ] = $value;
		}
	}

	/**
	 * Implementation of `\ArrayAccess`, allowing you to do `isset( $icon[ $x ] )`.
	 *
	 * @param  integer $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return isset( $this->images[ $offset ] );
	}

	/**
	 * Implementation of `\ArrayAccess`, allowing you to do `unset( $icon[$x] )`.
	 *
	 * @param  integer $offset
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		unset( $this->images[ $offset ] );
	}

	/**
	 * Implementation of `\ArrayAccess`, allowing you to do `$image = $icon[ $x ]`.
	 *
	 * @param  integer $offset
	 * @return IconImage|null
	 */
	public function offsetGet( $offset ) {
		return isset( $this->images[ $offset ] )
			? $this->images[ $offset ]
			: null;
	}

	/**
	 * Implementation of `\Iterator`, allowing `foreach( $icon as $image ){}`.
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * Implementation of `\Iterator`, allowing `foreach( $icon as $image ){}`.
	 *
	 * @return IconImage
	 */
	public function current() {
		return $this->images[ $this->position ];
	}

	/**
	 * Implementation of `\Iterator`, allowing `foreach( $icon as $image ){}`.
	 *
	 * @return int
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Implementation of `\Iterator`, allowing `foreach( $icon as $image ){}`.
	 *
	 * @return void
	 */
	public function next() {
		$this->position++;
	}

	/**
	 * Implementation of `\Iterator`, allowing `foreach( $icon as $image ){}`.
	 *
	 * @return bool
	 */
	public function valid() {
		return isset( $this->images[ $this->position ] );
	}
}
