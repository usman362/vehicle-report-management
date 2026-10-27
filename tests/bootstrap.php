<?php
/**
 * Unit-test bootstrap.
 *
 * These tests cover domain logic that must not depend on WordPress being
 * loaded. ABSPATH is defined so plugin files guarded by it can be included.
 * WordPress integration tests are added with the milestones that need them.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../' );

require_once __DIR__ . '/../vendor/autoload.php';
