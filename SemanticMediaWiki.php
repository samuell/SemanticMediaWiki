<?php

use SMW\NamespaceManager;
use SMW\ApplicationFactory;
use SMW\Setup;

/**
 * @codeCoverageIgnore
 *
 * ExtensionRegistry only maps classes and functions after all extensions have
 * been queued from the LocalSettings.php resulting in DefaultSettings not being
 * loaded in-time.
 *
 * When changing the load order, please ensure that this function is run either
 * via Composer's autoloading or as part of your internal registration.
 */
SemanticMediaWiki::load();

/**
 * @codeCoverageIgnore
 *
 * This documentation group collects source code files belonging to Semantic
 * MediaWiki.
 *
 * For documenting extensions of SMW, please do not use groups starting with
 * "SMW" but make your own groups instead. Browsing at
 * https://doc.semantic-mediawiki.org/ is assumed to be easier this way.
 *
 * @defgroup SMW Semantic MediaWiki
 */
class SemanticMediaWiki {

	/**
	 * @since 2.5
	 *
	 * @note It is expected that this function is loaded before LocalSettings.php
	 * to ensure that settings and global functions are available by the time
	 * the extension is activated.
	 */
	public static function load() {

		if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
			include_once __DIR__ . '/vendor/autoload.php';
		}

		include_once __DIR__ . '/src/Aliases.php';
		include_once __DIR__ . '/src/Defines.php';
		include_once __DIR__ . '/src/GlobalFunctions.php';

		// If the function is called more than once then this will fail on
		// purpose
		foreach ( include __DIR__ . '/DefaultSettings.php' as $key => $value ) {
			if ( !isset( $GLOBALS[$key] ) ) {
				$GLOBALS[$key] = $value;
			}
		}
	}

	/**
	 * @since 2.4
	 */
	public static function initExtension() {

		define( 'SMW_VERSION', '3.0.0-alpha' );

		if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
			include_once __DIR__ . '/vendor/autoload.php';
		}

		$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $GLOBALS['smwgIP'] . 'i18n/extra/SemanticMediaWiki.alias.php';
		$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $GLOBALS['smwgIP'] . 'i18n/extra/SemanticMediaWiki.magic.php';

		$GLOBALS['smwgSemanticsEnabled'] = true;

		self::onCanonicalNamespaces();
	}

	/**
	 * CanonicalNamespaces initialization
	 *
	 * @note According to T104954 registration via wgExtensionFunctions can be
	 * too late and should happen before that in case RequestContext::getLanguage
	 * invokes Language::getNamespaces before the `wgExtensionFunctions` execution.
	 *
	 * @see https://phabricator.wikimedia.org/T104954#2391291
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
	 * @Bug 34383
	 *
	 * @since 2.5
	 */
	public static function onCanonicalNamespaces() {
		$GLOBALS['wgHooks']['CanonicalNamespaces'][] = 'SMW\NamespaceManager::initCanonicalNamespaces';
	}

	/**
	 * Setup and initialization
	 *
	 * @note $wgExtensionFunctions variable is an array that stores
	 * functions to be called after most of MediaWiki initialization
	 * has finalized
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgExtensionFunctions
	 *
	 * @since  1.9
	 */
	public static function onExtensionFunction() {

		// 3.x reverse the order to ensure that smwgMainCacheType is used
		// as main and smwgCacheType being deprecated with 3.x
		$GLOBALS['smwgMainCacheType'] = $GLOBALS['smwgCacheType'];

		$applicationFactory = ApplicationFactory::getInstance();

		$namespace = new NamespaceManager( $GLOBALS );
		$namespace->init();

		$setup = new Setup( $applicationFactory, $GLOBALS, __DIR__ );
		$setup->run();
	}

	/**
	 * @since 2.4
	 *
	 * @return string|null
	 */
	public static function getVersion() {
		return SMW_VERSION;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public static function getEnvironment() {

		$store = '';

		if ( isset( $GLOBALS['smwgDefaultStore'] ) ) {
			$store = $GLOBALS['smwgDefaultStore'];
		};

		if ( strpos( strtolower( $store ), 'sparql' ) ) {
			$store .= '::' . strtolower( $GLOBALS['smwgSparqlDatabaseConnector'] );
		}

		return array(
			'store' => $store,
			'db'    => isset( $GLOBALS['wgDBtype'] ) ? $GLOBALS['wgDBtype'] : 'N/A'
		);
	}

}
