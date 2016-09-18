<?php

class SpecialCategoryChecker extends SpecialPage {
	static $assessmentCategories = [];
	static $versionCategories = [];
	static $categorizationCategories = [];
	
	public function __construct() {
		parent::__construct( 'CategoryChecker', 'autopatrol' );
	}
	
	function execute( $par ) {
		if (  !$this->userCanExecute( $this->getUser() )  ) {
			$this->displayRestrictionError();
			return;
		}
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
		
		self::calcCategories();
		
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
		"page",
		"page_id",
		[
			"page_namespace" => "0",
			"page_is_redirect" => "0"
		]
		);
		
		$versionOut = "The following pages have no versioning category. Disambiguation, patch, wiki, and mod pages are excluded.\n";
		$assessmentOut = "\nThe following pages have no assessment.\n";
		$categoryOut = "\nThe following pages are uncategorized (needing editing, verisoning, and hidden categories not considered).\n";
		foreach( $res as $row ) {
			$page = WikiPage::newFromID( $row->page_id );
			
			if ( self::showVersionPage( $page ) ) {
				self::appendPage( $page, $versionOut );
			}
			if ( self::showAssessmentPage( $page ) ) {
				self::appendPage( $page, $assessmentOut );
			}
			if ( self::showCategoryPage( $page ) ) {
				self::appendPage( $page, $categoryOut );
			}
		}
		$output->addWikiText( $versionOut );
		$output->addWikiText( $assessmentOut );
		$output->addWikiText( $categoryOut );
	}
	
	static function calcCategories() {
		self::calcSubCategories( "Articles by version", self::$versionCategories );
		self::calcSubCategories( "Mods", self::$versionCategories );
		self::$versionCategories[] = "Disambiguation";
		self::$versionCategories[] = "Patches";
		self::$versionCategories[] = "Wiki";
		self::$versionCategories[] = "Mods";
		
		self::calcSubCategories( "Articles by quality", self::$assessmentCategories );
		self::calcSubCategories( "Articles by version", self::$categorizationCategories );
		self::calcSubCategories( "Need editing", self::$categorizationCategories );
		self::calcSubCategories( "Hidden categories", self::$categorizationCategories );
		self::calcSubCategories( "Status categories", self::$categorizationCategories );
	}
	
	static function calcSubCategories( $category, array &$out ) {
		$category = Category::newFromName( $category );
		foreach( $category->getMembers() as $subCat ) {
			if ( $subCat->getNamespace() == NS_CATEGORY ) {
				$out[] = $subCat->getText();
			}
		}
	}
	
	static function showVersionPage( WikiPage &$page ) {
		$categories = $page->getCategories();
		foreach( $categories as $category ) {
			if ( in_array( $category->getText(), self::$versionCategories ) ) {
				return false;
			}
		}
		return true;
	}
	
	static function showAssessmentPage( WikiPage &$page ) {
		$talkPage = WikiPage::factory( $page->getTitle()->getTalkPage() );
		if ( !$talkPage->exists() ) {
			return true;
		}
		$categories = $talkPage->getCategories();
		foreach( $categories as $category ) {
			if ( in_array( $category->getText(), self::$assessmentCategories ) ) {
				return false;
			}
		}
		return true;
	}
	
	static function showCategoryPage( WikiPage &$page ) {
		$categories = $page->getCategories();
		foreach( $categories as $category ) {
			if ( !in_array( $category->getText(), self::$categorizationCategories ) ) {
				return false;
			}
		}
		return true;
	}
	
	static function appendPage( &$page, &$out ) {
		$out .= "# [[" . $page->getTitle()->getText() . "]]\n";
	}
	
	protected function getGroupName() {
		return 'pages';
	}
}