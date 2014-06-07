<?php

class MassMessageListContent extends TextContent {

	public function __construct( $text ) {
		parent::__construct( $text, 'MassMessageListContent' );
	}

	public function validate() {
		$targets = $this->getTargets();
		if ( !$targets ) {
			return false;
		}
		foreach ( $targets as $target ) {
			if ( !array_key_exists( 'title', $target ) ) {
				return false;
			}
		}
		return true;
	}

	protected function getHtml() {
		if ( !$this->validate() ) {
			return '<p class="error">' . wfMessage( 'massmessage-content-invalid' )->text() . '</p>';
		}
	}

	protected function getTargets() {
		return FormatJson::decode( $this->getNativeData(), true );
	}
}
