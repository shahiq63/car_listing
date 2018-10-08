<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the price splitter.
 *
 * @coversDefaultClass \Drupal\commerce_order\PriceSplitter
 *
 * @group commerce
 */
class PriceSplitterTest extends CommerceKernelTestBase {

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The price splitter.
   *
   * @var \Drupal\commerce_order\PriceSplitterInterface
   */
  protected $splitter;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store->id(),
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);

    $this->splitter = $this->container->get('commerce_order.price_splitter');
  }

  /**
   * @covers ::split
   */
  public function testSplit() {
    // 6 x 3 + 6 x 3 = 36.
    $amount = new Price('6', 'USD');
    $order_items = $this->buildOrderItems([$amount, $amount]);
    $this->order->setItems($order_items);
    $this->order->save();

    // Each order item should be discounted by half (9 USD).
    $amounts = $this->splitter->split($this->order, new Price('18', 'USD'));
    $expected_amount = new Price('9', 'USD');
    $this->assertEquals([$expected_amount, $expected_amount], array_values($amounts));

    // Same result with an explicit percentage.
    $amounts = $this->splitter->split($this->order, new Price('18', 'USD'), '0.5');
    $expected_amount = new Price('9', 'USD');
    $this->assertEquals([$expected_amount, $expected_amount], array_values($amounts));

    // 9.99 x 3 + 1.01 x 3 = 33.
    $first_amount = new Price('9.99', 'USD');
    $second_amount = new Price('1.01', 'USD');
    $order_items = $this->buildOrderItems([$first_amount, $second_amount]);
    $this->order->setItems($order_items);
    $this->order->save();

    $amount = new Price('5', 'USD');
    $amounts = $this->splitter->split($this->order, $amount);
    $first_expected_amount = new Price('4.54', 'USD');
    $second_expected_amount = new Price('0.46', 'USD');
    $this->assertEquals($amount, $first_expected_amount->add($second_expected_amount));
    $this->assertEquals([$first_expected_amount, $second_expected_amount], array_values($amounts));

    // Split an amount that has a reminder.
    $amount = new Price('3.98', 'USD');
    $amounts = $this->splitter->split($this->order, $amount);
    $first_expected_amount = new Price('3.62', 'USD');
    $second_expected_amount = new Price('0.36', 'USD');
    $this->assertEquals($amount, $first_expected_amount->add($second_expected_amount));
    $this->assertEquals([$first_expected_amount, $second_expected_amount], array_values($amounts));
  }

  /**
   * Builds the order items for the given unit prices.
   *
   * @param \Drupal\commerce_price\Price[] $unit_prices
   *   The unit prices.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface[]
   *   The order items.
   */
  protected function buildOrderItems(array $unit_prices) {
    $order_items = [];
    foreach ($unit_prices as $unit_price) {
      $order_item = OrderItem::create([
        'type' => 'test',
        'unit_price' => $unit_price,
        'quantity' => '3',
      ]);
      $order_item->save();

      $order_items[] = $order_item;
    }

    return $order_items;
  }

}
