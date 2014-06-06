<?php

class MassMessageListContent extends TextContent {

	public function __construct( $text ) {
		parent::__construct( $text, 'MassMessageListContent' );
	}

	protected function getHtml() {

	}

	protected function validate() {
		$targets = FormatJson::decode( $this->getNativeData(), true );
		if ( !$targets ) {
			return false;
		}
		foreach ( $targets as $target ) {
			if ( !array_key_exists( 'title', $target ) {
				return false;
			}
		}
		return true;
	}
}
