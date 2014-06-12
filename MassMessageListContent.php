<?php

class MassMessageListContent extends TextContent {

	/**
	 * @var string|null
	 * Description wikitext
	 */
	protected $description;


	/**
	 * @var array|null
	 * Array of target pages
	 */
	protected $targets;

	/**
	 * @var bool
	 * Whether $description and $targets have been populated
	 */
	protected $decoded = false;

	public function __construct( $text ) {
		parent::__construct( $text, 'MassMessageListContent' );
	}

	public function validate() {
		if ( !$this->decoded ) {
			$this->decode();
		}
		if ( !is_string( $this->description ) || !is_array( $this->targets ) ) {
			return false;
		}
		foreach ( $this->targets as $target ) {
			if ( !is_array( $target ) || !array_key_exists( 'title', $target ) ) {
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
		if ( is_array( $data ) ) {
			$this->description = array_key_exists( 'description', $data ) ?
				$data['description'] : null;
			$this->targets = array_key_exists( 'targets', $data ) ? $data['targets'] : null;
		}
		$this->decoded = true;
	}

	protected function fillParserOutput( Title $title, $revId, ParserOptions $options,
		$generateHtml, ParserOutput &$output
	) {
		global $wgParser;

		if ( !$this->validate() ) {
			$output->setText(
				'<p class="error">' . wfMessage( 'massmessage-content-invalid' )->text() . '</p>'
			);
			return;
		}

		$output = $wgParser->parse( $this->getDescription(), $title, $options, true, true, $revId );

		if ( $generateHtml ) {
			$output->setText( $output->getText() . $this->getTargetsHtml() );
		} else {
			$output->setText( '' );
		}
	}

	protected function getTargetsHtml() {
		$domains = $this->getTargetsByDomain();

		// Determine whether there are targets on external wikis
		$printSites = ( count( $domains ) === 1 && array_key_exists( 'local', $domains ) ) ?
			false : true;

		$html = '<h2>' . wfMessage( 'massmessage-content-pages' )->text() . "</h2>\n";

		foreach ( $domains as $domain => $targets ) {
			if ( $printSites ) {
				if ( $domain === 'local' ) {
					$html .= '<p>' . wfMessage( 'massmessage-content-pagesonwiki' )->text()
						. "</p>\n";
				} else {
					$html .= '<p>' . wfMessage( 'massmessage-content-pagesondomain', $domain )->text()
						. "</p>\n";
				}
			}

			$html .= "<ul>\n";
			foreach ( $targets as $target ) {
				if ( $domain === 'local' ) {
					$html .= '<li>' . Linker::link( Title::newFromText( $target ) ) . "</li>\n";
				} else {
					$html .= '<li>' . $target . "</li>\n";
				}
			}
			$html .= "</ul>\n";
		}

		return $html;
	}

	protected function getTargetsByDomain() {
		$targets = $this->getTargets();
		$results = array();
		foreach ( $targets as $target ) {
			if ( array_key_exists( 'domain', $target ) ) {
				$results[$target['domain']][] = $target['title'];
			} else {
				$results['local'][] = $target['title'];
			}
		}
		return $results;
	}
}
