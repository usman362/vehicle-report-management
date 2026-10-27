<?php
/**
 * The packages the merchant offers.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the configured tiers and resolves the one attached to an order.
 *
 * The shape of the tiers ships with the plugin so the storefront has something
 * coherent to describe; the prices do not, because only the merchant can set
 * those. A tier without a price is shown as "price to be confirmed" and cannot
 * be bought, which is why `Settings::missing_for_live()` lists it.
 */
final class PackageRepository {

	public const SETTING = 'packages';

	/**
	 * The default tiers: names and promised checks, never prices.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function defaults(): array {
		return array(
			array(
				'slug'       => 'basic',
				'name'       => __( 'Basic', 'vehicle-report-orders' ),
				'product_id' => 0,
				'reports'    => 1,
				'checks'     => array( 'vehicle_details', 'tax_mot_status', 'keepers', 'plate_changes', 'valuation' ),
			),
			array(
				'slug'       => 'silver',
				'name'       => __( 'Silver', 'vehicle-report-orders' ),
				'product_id' => 0,
				'reports'    => 1,
				'featured'   => true,
				'checks'     => array( 'vehicle_details', 'tax_mot_status', 'keepers', 'plate_changes', 'valuation', 'write_off', 'stolen', 'finance' ),
			),
			array(
				'slug'       => 'classic',
				'name'       => __( 'Classic', 'vehicle-report-orders' ),
				'product_id' => 0,
				'reports'    => 1,
				'checks'     => VehicleCheck::keys(),
			),
		);
	}

	/**
	 * Every configured tier, in the order it is shown.
	 *
	 * @return array<int, Package>
	 */
	public static function all(): array {
		$stored = Settings::get( self::SETTING );
		$rows   = is_array( $stored ) && array() !== $stored ? $stored : self::defaults();

		$packages = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || '' === (string) ( $row['slug'] ?? '' ) ) {
				continue;
			}

			$packages[] = new Package(
				(string) $row['slug'],
				(string) ( $row['name'] ?? $row['slug'] ),
				(int) ( $row['product_id'] ?? 0 ),
				is_array( $row['checks'] ?? null ) ? $row['checks'] : array(),
				(int) ( $row['reports'] ?? 1 ),
				! empty( $row['featured'] )
			);
		}

		return $packages;
	}

	/**
	 * One tier by slug.
	 *
	 * @param string $slug Tier identifier.
	 *
	 * @return Package|null
	 */
	public static function find( string $slug ): ?Package {
		foreach ( self::all() as $package ) {
			if ( $package->slug() === $slug ) {
				return $package;
			}
		}

		return null;
	}

	/**
	 * One tier by the product that carries its price.
	 *
	 * @param int $product_id WooCommerce product id.
	 *
	 * @return Package|null
	 */
	public static function find_by_product( int $product_id ): ?Package {
		if ( $product_id < 1 ) {
			return null;
		}

		foreach ( self::all() as $package ) {
			if ( $package->product_id() === $product_id ) {
				return $package;
			}
		}

		return null;
	}

	/**
	 * The tiers that have a price and can actually be sold.
	 *
	 * @return array<int, Package>
	 */
	public static function purchasable(): array {
		return array_values( array_filter( self::all(), static fn( Package $package ): bool => $package->is_purchasable() ) );
	}

	/**
	 * The cheapest sellable tier, used when a visitor has not chosen one.
	 *
	 * @return Package|null
	 */
	public static function cheapest(): ?Package {
		$purchasable = self::purchasable();

		if ( array() === $purchasable ) {
			return null;
		}

		usort( $purchasable, static fn( Package $a, Package $b ): int => (int) ( ( $a->price() ?? 0.0 ) <=> ( $b->price() ?? 0.0 ) ) );

		return $purchasable[0];
	}

	/**
	 * The tier an order was placed against, resolved from the snapshot first
	 * so that later edits to the tier cannot rewrite history.
	 *
	 * @param WC_Order $order Order to inspect.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function snapshot_for( WC_Order $order ): ?array {
		$raw = (string) $order->get_meta( OrderMeta::TIER_SNAPSHOT );

		if ( '' !== $raw ) {
			$decoded = json_decode( $raw, true );

			if ( is_array( $decoded ) && isset( $decoded['slug'] ) ) {
				return $decoded;
			}
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! method_exists( $item, 'get_product_id' ) ) {
				continue;
			}

			$package = self::find_by_product( (int) $item->get_product_id() );

			if ( null !== $package ) {
				return $package->snapshot();
			}
		}

		return null;
	}

	/**
	 * Record the tier against the order, once, at the moment of purchase.
	 *
	 * @param WC_Order $order   Order being created.
	 * @param Package  $package Tier that was bought.
	 *
	 * @return void
	 */
	public static function remember( WC_Order $order, Package $package ): void {
		$order->update_meta_data( OrderMeta::TIER_SNAPSHOT, (string) wp_json_encode( $package->snapshot() ) );
	}
}
