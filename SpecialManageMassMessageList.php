<?php

class SpecialManageMassMessageList extends FormSpecialPage {

	protected $titleText;
	protected $isTitleValid;

	protected function setParameter( $par ) {
		if ( $par === null || $par === '' ) {
			$this->titleText = '';
			$this->isTitleValid = true;
		} else {
			$title = Title::newFromText( $par );
			if ( $title->exists() && $title->hasContentModel( '***TODO***' ) ) {
				$this->titleText = $title->getText(); // Use the canonical form.
				$this->isTitleValid = true;
			} else {
				$this->isTitleValid = false;
			}
		}
	}

}
