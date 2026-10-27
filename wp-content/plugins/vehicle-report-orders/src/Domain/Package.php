<?php
/**
 * One purchasable report package.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * A tier the customer can buy: a name, the checks it promises, and the
 * WooCommerce product that carries its price.
 *
 * A package only promises checks. What was actually found for a particular
 * vehicle is recorded per source at fulfilment and repeated to the customer,
 * so buying the widest tier never implies that every check returned something.
 */
final class Package {

	/**
	 * Build a package.
	 *
	 * @param string            $slug       Stable identifier, used in URLs and order meta.
	 * @param string            $name       Customer-facing name.
	 * @param int               $product_id WooCommerce product that carries the price.
	 * @param array<int,string> $checks     Check slugs promised by this tier.
	 * @param int               $reports    How many vehicles the tier covers.
	 * @param bool              $featured   Whether this tier is the one put forward.
	 */
	public function __construct(
		private string $slug,
		private string $name,
		private int $product_id,
		private array $checks,
		private int $reports = 1,
		private bool $featured = false
	) {
		$this->checks = VehicleCheck::filter( $checks );
	}

	/**
	 * Stable identifier.
	 *
	 * @return string
	 */
	public function slug(): string {
		return $this->slug;
	}

	/**
	 * Customer-facing name.
	 *
	 * @return string
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * The WooCommerce product id, or 0 when the tier has not been created yet.
	 *
	 * @return int
	 */
	public function product_id(): int {
		return $this->product_id;
	}

	/**
	 * Check slugs this tier promises, in catalogue order.
	 *
	 * @return array<int, string>
	 */
	public function checks(): array {
		return $this->checks;
	}

	/**
	 * How many vehicles the tier covers.
	 *
	 * @return int
	 */
	public function reports(): int {
		return max( 1, $this->reports );
	}

	/**
	 * Whether this is the tier the merchant puts forward.
	 *
	 * Only a presentation choice: a featured tier is drawn with more weight,
	 * never described as more popular than the others, because this site has
	 * no sales figures to support such a claim.
	 *
	 * @return bool
	 */
	public function is_featured(): bool {
		return $this->featured;
	}

	/**
	 * The WooCommerce product, when it exists.
	 *
	 * @return WC_Product|null
	 */
	public function product(): ?WC_Product {
		if ( $this->product_id < 1 || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( $this->product_id );

		return $product instanceof WC_Product ? $product : null;
	}

	/**
	 * The price as a number, or null while the merchant has not set one.
	 *
	 * @return float|null
	 */
	public function price(): ?float {
		$product = $this->product();

		if ( ! $product || '' === (string) $product->get_price() ) {
			return null;
		}

		$price = (float) $product->get_price();

		return $price > 0 ? $price : null;
	}

	/**
	 * The price formatted for display, or an empty string.
	 *
	 * @return string
	 */
	public function formatted_price(): string {
		$price = $this->price();

		return null !== $price && function_exists( 'wc_price' ) ? (string) wc_price( $price ) : '';
	}

	/**
	 * Whether this tier can actually be sold right now.
	 *
	 * @return bool
	 */
	public function is_purchasable(): bool {
		return null !== $this->price();
	}

	/**
	 * The tier's own page, or an empty string while it has no product.
	 *
	 * @return string
	 */
	public function url(): string {
		$product = $this->product();

		return $product ? (string) get_permalink( $product->get_id() ) : '';
	}

	/**
	 * What is recorded against an order, so a later change to the tier cannot
	 * rewrite what a customer was promised.
	 *
	 * @return array<string, mixed>
	 */
	public function snapshot(): array {
		return array(
			'slug'    => $this->slug,
			'name'    => $this->name,
			'checks'  => $this->checks,
			'reports' => $this->reports(),
			'price'   => $this->price(),
		);
	}
}
