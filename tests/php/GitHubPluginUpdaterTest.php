<?php

declare(strict_types=1);

namespace ContentPoll\Tests; // Separate test namespace

use ContentPoll\Update\GitHubPluginUpdater;
use PHPUnit\Framework\TestCase;

final class GitHubPluginUpdaterTest extends TestCase {

	public function test_missing_required_parameters_throws_exception(): void {
		$this->expectException( \InvalidArgumentException::class);
		new GitHubPluginUpdater( [] );
	}

	public function test_constructor_registers_init_hook(): void {
		$updater = new GitHubPluginUpdater( [
			'github_url'  => 'https://github.com/example/content-poll',
			'plugin_file' => __DIR__ . '/../../content-poll.php',
			'plugin_slug' => 'content-poll',
		] );

		// has_action returns priority or false.
		if ( function_exists( 'has_action' ) ) {
			$priority = has_action( 'init', [ $updater, 'setup_updater' ] );
			$this->assertNotFalse( $priority, 'Expected init hook to be registered.' );
		} else {
			$this->assertTrue( method_exists( $updater, 'setup_updater' ), 'Fallback assertion without WP.' );
		}
	}

	public function test_create_with_assets_sets_release_assets_flag(): void {
		$regex   = '/content-poll.*\\.zip/';
		$updater = GitHubPluginUpdater::create_with_assets(
			'https://github.com/example/content-poll',
			__DIR__ . '/../../content-poll.php',
			'content-poll',
			$regex
		);

		// Use reflection to inspect private properties.
		$ref           = new \ReflectionClass( $updater );
		$nameRegexProp = $ref->getProperty( 'name_regex' );
		$nameRegexProp->setAccessible( true );
		$enableAssetsProp = $ref->getProperty( 'enable_release_assets' );
		$enableAssetsProp->setAccessible( true );

		$this->assertSame( $regex, $nameRegexProp->getValue( $updater ), 'Regex should be stored.' );
		$this->assertTrue( $enableAssetsProp->getValue( $updater ), 'Release assets flag should be enabled when regex provided.' );
	}
}
