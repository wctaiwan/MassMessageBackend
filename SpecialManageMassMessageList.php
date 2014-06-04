<?php

class SpecialManageMassMessageList extends FormSpecialPage {

	protected $titleText;
	protected $isTitleValid;

	public function __construct() {
		parent::__construct( 'ManageMassMessageList' );
	}

	protected function setParameter( $par ) {
		if ( $par === null || $par === '' ) {
			$this->titleText = '';
			$this->isTitleValid = true;
		} else {
			$title = Title::newFromText( $par );

			if ( $title !== null && $title->exists()
				&& $title->hasContentModel( 'MassMessageListContent' )
			) {
				$this->titleText = $title->getPrefixedText(); // Use the canonical form.
				$this->isTitleValid = true;
			} else {
				$this->isTitleValid = false;
			}
		}
	}

	protected function getFormFields() {
		$fields = array();

		// If the title is valid or empty
		if ( $this->isTitleValid ) {
			$fields['title'] = array(
				'type' => 'text',
				'label-message' => 'massmessage-manage-title',
			);
			$fields['content'] = array(
				'type' => 'textarea',
				'label-message' => 'massmessage-manage-content'
			);

			// If modifying an existing list
			if ( $this->titleText !== '' ) {
				// Set the title and prevent modification.
				$fields['title']['default'] = $this->titleText;
				$fields['title']['disabled'] = true;

				// Set the default content.
				$page = WikiPage::factory( Title::newFromText( $this->titleText ) );
				$fields['content']['default'] = $this->convertFromJson(
					$page->getContent()->getNativeData()
				);
			}
		}
		return $fields;
	}

	protected function alterForm( HTMLForm $form ) {
		if ( $this->isTitleValid ) {
			$form->setWrapperLegendMsg( 'managemassmessagelist' );
		} else { // Hide the form if the title is invalid.
			$form->setWrapperLegend( false );
			$form->suppressDefaultSubmit( true );
		}
	}

	protected function preText() {
		if ( $this->isTitleValid ) {
			$msgKey = 'massmessage-manage-header';
		} else {
			$msgKey = 'massmessage-manage-invalidtitle';
		}
		return '<p>' . $this->msg( $msgKey )->text() . '</p>';
	}

	public function onSubmit( array $data ) {
		$title = Title::newFromText( $data['title'] );
		if ( !$title ) {
			return Status::newFatal( 'massmessage-manage-invalidtitle' );
		} else if ( $title->exists() && $this->titleText === '' ) {
			return Status::newFatal( 'massmessage-manage-exists' );
		} else if ( !$title->userCan( 'edit' ) || !$title->exists() && !$title->userCan( 'create' )	) {
			return Status::newFatal( 'massmessage-manage-nopermission' );
		}

		$jsonText = $this->convertToJson( $data['content'] );
		if ( !$jsonText ) {
			return Status::newFatal( 'massmessage-manage-tojsonerror' );
		}
		$content = new MassMessageListContent( $jsonText );

		$result = WikiPage::factory( $title )->doEditContent(
			$content,
			$this->msg( 'massmessage-manage-editsummary' )->text()
		);
		if ( $result->isOK() ) {
			$this->getOutput()->redirect( $title->getFullUrl() );
		}
		return $result;
	}

	public function onSuccess() {
		// No-op: We have already redirected.
	}

	protected function convertToJson( $textInput ) {
		$lines = array_filter( explode( "\n", $textInput ), 'trim' ); // Array of non-empty lines

		$targets = array();
		foreach ( $lines as $line ) {
			$delimiterPos = strrpos( $line, '@' );
			if ( $delimiterPos !== false ) {
				$titleText = substr( $line, 0, $delimiterPos );
				$domain = strtolower( substr( $line, $delimiterPos+1 ) );
			} else {
				$titleText = $line;
				$domain = null;
			}

			$title = Title::newFromText( $titleText );
			if ( !$title ) {
				continue; // Silently skip invalid titles.
			}
			$titleText = $title->getPrefixedText(); // Use the canonical form.

			if ( $domain ) {
				$targets[] = array( 'title' => $titleText, 'domain' => $domain );
			} else {
				$targets[] = array( 'title' => $titleText );
			}
		}

		// Remove duplicates and sort.
		$targets = array_map( 'unserialize', array_unique( array_map( 'serialize', $targets ) ) );
		usort( $targets, array( $this, 'compareTargets' ) );

		return FormatJson::encode( $targets );
	}

	protected function compareTargets( $a, $b ) {
		if ( !array_key_exists( 'domain', $a ) && array_key_exists( 'domain', $b ) ) {
			return -1;
		} else if ( array_key_exists( 'domain', $a ) && !array_key_exists( 'domain', $b ) ) {
			return 1;
		} else if ( array_key_exists( 'domain', $a ) && array_key_exists( 'domain', $b )
			&& $a['domain'] !== $b['domain']
		) {
			return strcmp( $a['domain'], $b['domain'] );
		} else {
			return strcmp( $a['title'], $b['title'] );
		}
	}

	protected function convertFromJson( $jsonInput ) {
		$targets = FormatJson::decode( $jsonInput, true );
		if ( !$targets ) {
			return $this->msg( 'massmassage-manage-fromjsonerror' )->escaped(); //
		}
		$lines = array();
		foreach ( $targets as $target ) {
			if ( array_key_exists( 'domain', $target ) ) {
				$lines[] = $target['title'] . '@' . $target['domain'];
			} else {
				$lines[] = $target['title'];
			}
		}
		return implode( "\n", $lines );
	}
}
