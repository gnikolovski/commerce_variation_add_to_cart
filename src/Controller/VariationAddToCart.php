<?php

namespace Drupal\commerce_variation_add_to_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Entity\ProductAttributeInterface;

/**
 * variation add to cart form controller.
 */
class VariationAddToCart extends ControllerBase {

  /**
   * Add item to cart.
   */
  public function addItem() {

    // Get item data from post request.
    $product_id = (integer) \Drupal::request()->request->get('product_id');
    $variation_id = (integer) \Drupal::request()->request->get('variation_id');
    $quantity = (integer) \Drupal::request()->request->get('quantity');

    if ($product_id > 0 && $variation_id > 0 && $quantity > 0) {

      // Get current user.
      $user = \Drupal::currentUser();
      $uid = $user->id();

      // Load product variation and get store.
      $variation = \Drupal\commerce_product\Entity\ProductVariation::load($variation_id);
      $variation_price = $variation->getPrice();
      $stores = $variation->getStores();
      $store = reset($stores);

      // Get current user's cart.
      $query = \Drupal::entityQuery('commerce_order')
        ->condition('cart', TRUE)
        ->condition('uid', $uid);
      $result = $query->execute();
      $cart_id = reset($result);

      // Create cart for user if it already doesn't exist.
      if (!$cart_id) {
        $cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $store);
        $cart_id = $cart->id();
      }

      // Load order, create order item and save it.
      $order = Order::load($cart_id);
      $order_item = \Drupal\commerce_order\Entity\OrderItem::create([
        'type' => 'default',
        'purchased_entity' => $variation_id,
        'quantity' => $quantity,
        'unit_price' => $variation_price,
      ]);
      $order_item->save();
      $order->addItem($order_item);
      $order->save();

      // Redirect back to product.
      drupal_set_message($this->t('Added to cart'), 'status', TRUE);
      $response = new RedirectResponse('/product/' . $product_id);
      return $response;
    }
    drupal_set_message($this->t('Item not added to cart'), 'error', TRUE);
    $response = new RedirectResponse('/product/' . $product_id);
    return $response;
  }

}
