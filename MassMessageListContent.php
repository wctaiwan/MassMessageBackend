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
		global $wgAllowGlobalMessaging;

		if ( !$this->validate() ) {
			return '<p class="error">' . wfMessage( 'massmessage-content-invalid' )->text() . '</p>';
		}

		$targets = $this->getTargets();
		$rows = array();
		foreach ( $targets as $target ) {
			$row = array( $target['title'] );
			if ( $wgAllowGlobalMessaging ) {
				$row[] = array_key_exists( 'domain', $target ) ? $target['domain'] : '';
			}
			$rows[] = $row;
		}

		$headers = array( wfMessage( 'massmessage-content-title' )->text() );
		if ( $wgAllowGlobalMessaging ) {
			$headers[] = wfMessage( 'massmessage-content-site' )->text();
		}

		return Xml::buildTable( $rows, array( 'class' => 'wikitable' ), $headers );
	}

	protected function getTargets() {
		return FormatJson::decode( $this->getNativeData(), true );
	}
}
