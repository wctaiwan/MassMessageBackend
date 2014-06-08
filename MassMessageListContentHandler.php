<?php

class MassMessageListContentHandler extends TextContentHandler {

	public function __construct( $modelId = 'MassMessageListContent' ) {
		parent::__construct( $modelId, array( CONTENT_FORMAT_JSON ) );
	}

	public function unserializeContent( $text, $format = null ) {
		$this->checkFormat( $format );
		return new MassMessageListContent( $text );
	}

	public function makeEmptyContent() {
		return new MassMessageListContent( '[]' );
	}

}
