<?php

class MassMessageListContent extends TextContent {

	public function __construct( $text ) {
		parent::__construct( $text, 'MassMessageListContent' );
	}

	public function validate() {
		$targets = $this->getTargets();
		if ( $targets === null ) {
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

		$targets = $this->getTargets();

		// Determine whether to print the "site" column.
		$printSite = false;
		foreach ( $targets as $target ) {
			if ( array_key_exists( 'domain', $target ) ) {
				$printSite = true;
				break;
			}
		}

		$rows = array();
		foreach ( $targets as $target ) {
			$row = array();

			// Link to local pages.
			if ( !array_key_exists( 'domain', $target ) ) {
				$row[] = Linker::link( Title::newFromText( $target['title'] ) );
			} else {
				$row[] = $target['title'];
			}

			if ( $printSite ) {
				$row[] = array_key_exists( 'domain', $target ) ? $target['domain'] : '';
			}
			$rows[] = $row;
		}

		$headers = array( wfMessage( 'massmessage-content-title' )->text() );
		if ( $printSite ) {
			$headers[] = wfMessage( 'massmessage-content-site' )->text();
		}

		return Xml::buildTable( $rows, array( 'class' => 'wikitable' ), $headers, false );
	}

	protected function getTargets() {
		return FormatJson::decode( $this->getNativeData(), true );
	}
}
