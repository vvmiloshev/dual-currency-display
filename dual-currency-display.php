<?php
/**
 * Plugin Name: Dual Currency Display (BGN + EUR)
 * Plugin URI: https://github.com/vvmiloshev/dual-currency-display
 * Description: Показва всички цени в лева и евро. Поръчките се извършват в лева до 31.12.2025 и в евро след това. Двойно показване до 31.12.2026.
 * Version: 1.0.0
 * Author: Vladimir Miloshev
 * Author URI: https://github.com/vvmiloshev
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dual-currency-display
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

const DCDU_EXCHANGE_RATE = 1.95583;
const DCDU_SWITCH_DATE = '2026-01-01 00:00:00';
const DCDU_DUAL_DISPLAY_CUTOFF = '2026-12-31 23:59:59';

function dcdu_should_show_dual() {
    return time() <= strtotime(DCDU_DUAL_DISPLAY_CUTOFF);
}

function dcdu_get_secondary_price($price, $currency) {
    if ($currency === 'BGN') {
        return number_format($price / DCDU_EXCHANGE_RATE, 2) . ' €';
    } elseif ($currency === 'EUR') {
        return number_format($price * DCDU_EXCHANGE_RATE, 2) . ' лв';
    }
    return '';
}

add_filter('woocommerce_get_price_html', 'dcdu_show_dual_price', 100, 2);
function dcdu_show_dual_price($price_html, $product) {
    if (!dcdu_should_show_dual()) return $price_html;

    $currency = get_woocommerce_currency();
    $price = (float) $product->get_price();
    $secondary = dcdu_get_secondary_price($price, $currency);

    return $price_html . ' <span class="dcdu-secondary">(' . $secondary . ')</span>';
}

add_filter('woocommerce_currency', 'dcdu_dynamic_currency');
function dcdu_dynamic_currency($currency) {
    return (time() >= strtotime(DCDU_SWITCH_DATE)) ? 'EUR' : 'BGN';
}

// В количката и чекаута
add_filter('woocommerce_cart_item_price', 'dcdu_cart_item_price', 100, 3);
add_filter('woocommerce_cart_item_subtotal', 'dcdu_cart_item_price', 100, 3);
function dcdu_cart_item_price($price_html, $cart_item, $cart_item_key) {
    if (!dcdu_should_show_dual()) return $price_html;

    $currency = get_woocommerce_currency();
    $price = $cart_item['data']->get_price();
    $secondary = dcdu_get_secondary_price($price, $currency);

    return $price_html . ' <span class="dcdu-secondary">(' . $secondary . ')</span>';
}

add_filter('woocommerce_order_formatted_line_subtotal', 'dcdu_order_line_dual', 100, 3);
function dcdu_order_line_dual($subtotal, $item, $order) {
    if (!dcdu_should_show_dual()) return $subtotal;

    $currency = $order->get_currency();
    $price = $item->get_total() / $item->get_quantity();
    $secondary = dcdu_get_secondary_price($price, $currency);

    return $subtotal . ' <span class="dcdu-secondary">(' . $secondary . ')</span>';
}

add_action('wp_head', function () {
    echo '<style>.dcdu-secondary { color: #777; font-size: 0.9em; margin-left: 4px; }</style>';
});
