<?php

class MassMessageListContent extends TextContent {

	protected $description;

	protected $targets;

	protected $decoded;

	public function __construct( $text ) {
		parent::__construct( $text, 'MassMessageListContent' );
	}

	public function validate() {
		if ( !$this->decoded ) {
			$this->decode();
		}
		if ( $this->description === null || $this->targets === null ) {
			return false;
		}
		foreach ( $this->targets as $target ) {
			if ( !array_key_exists( 'title', $target ) ) {
				return false;
			}
		}
		return true;
	}

	public function getDescription() {
		if ( !$this->decoded ) {
			$this->decode();
		}
		return $this->description;
	}

	public function getTargets() {
		if ( !$this->decoded ) {
			$this->decode();
		}
		return $this->targets;
	}

	protected function decode() {
		if ( $this->decoded ) {
			return;
		}
		$data = FormatJson::decode( $this->getNativeData(), true );
		$this->description = array_key_exists( 'description', $data ) ?
			$data['description'] : null;
		$this->targets = array_key_exists( 'targets', $data ) ? $data['targets'] : null;
		$this->decoded = true;
	}

	//TODO: Change getHtml and the special page to fit the new schema
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
}
