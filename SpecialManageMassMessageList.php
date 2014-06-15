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
			$fields['description'] = array(
				'type' => 'textarea',
				'rows' => 5,
				'label-message' => 'massmessage-manage-description',
			);
			$fields['content'] = array(
				'type' => 'textarea',
				'label-message' => 'massmessage-manage-content',
			);

			// If modifying an existing list
			if ( $this->titleText !== '' ) {
				// Set the title and prevent modification.
				$fields['title']['default'] = $this->titleText;
				$fields['title']['disabled'] = true;

				// Fill in existing description and targets.
				$content = Revision::newFromTitle(
					Title::newFromText( $this->titleText )
				)->getContent();
				$description = $content->getDescription();
				$targets = $content->getTargets();
				$fields['description']['default'] = ( $description !== null ) ? $description : '';
				$fields['content']['default'] = ( $targets !== null ) ?
					self::parseTargets( $targets ) : '';
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
		return '<p>' . $this->msg( $msgKey )->parse() . '</p>';
	}

	public function onSubmit( array $data ) {
		$title = Title::newFromText( $data['title'] );
		if ( !$title ) {
			return Status::newFatal( 'massmessage-manage-invalidtitle' );
		} else if ( $title->exists() && $this->titleText === '' ) {
			return Status::newFatal( 'massmessage-manage-exists' );
		} else if ( !$title->userCan( 'edit' )
			|| !$title->exists() && !$title->userCan( 'create' )
		) {
			return Status::newFatal( 'massmessage-manage-nopermission' );
		}

		$jsonText = self::convertToJson( $data['description'], $data['content'] );
		if ( !$jsonText ) {
			return Status::newFatal( 'massmessage-manage-tojsonerror' );
		}
		$content = new MassMessageListContent( $jsonText );

		$result = WikiPage::factory( $title )->doEditContent(
			$content,
			$this->msg( 'massmessage-manage-editsummary' )->escaped()
		);
		if ( $result->isOK() ) {
			$this->getOutput()->redirect( $title->getFullUrl() );
		}
		return $result;
	}

	public function onSuccess() {
		// No-op: We have already redirected.
	}

	protected static function parseTargets( $targets ) {
		$lines = array();
		foreach ( $targets as $target ) {
			if ( array_key_exists( 'site', $target ) ) {
				$lines[] = $target['title'] . '@' . $target['site'];
			} else {
				$lines[] = $target['title'];
			}
		}
		return implode( "\n", $lines );
	}

	protected static function convertToJson( $description, $targetsText ) {
		$lines = array_filter( explode( "\n", $targetsText ), 'trim' ); // Array of non-empty lines

		$targets = array();
		foreach ( $lines as $line ) {
			$delimiterPos = strrpos( $line, '@' );
			if ( $delimiterPos !== false ) {
				$titleText = substr( $line, 0, $delimiterPos );
				$site = strtolower( substr( $line, $delimiterPos+1 ) );
			} else {
				$titleText = $line;
				$site = null;
			}

			$title = Title::newFromText( $titleText );
			if ( !$title ) {
				continue; // Silently skip invalid titles.
			}
			$titleText = $title->getPrefixedText(); // Use the canonical form.

			if ( $site ) {
				$targets[] = array( 'title' => $titleText, 'site' => $site );
			} else {
				$targets[] = array( 'title' => $titleText );
			}
		}

		// Remove duplicates and sort.
		$targets = array_map( 'unserialize', array_unique( array_map( 'serialize', $targets ) ) );
		usort( $targets, 'self::compareTargets' );

		return FormatJson::encode( array( 'description' => $description, 'targets' => $targets ) );
	}

	protected static function compareTargets( $a, $b ) {
		if ( !array_key_exists( 'site', $a ) && array_key_exists( 'site', $b ) ) {
			return -1;
		} else if ( array_key_exists( 'site', $a ) && !array_key_exists( 'site', $b ) ) {
			return 1;
		} else if ( array_key_exists( 'site', $a ) && array_key_exists( 'site', $b )
			&& $a['site'] !== $b['site']
		) {
			return strcmp( $a['site'], $b['site'] );
		} else {
			return strcmp( $a['title'], $b['title'] );
		}
	}
}
