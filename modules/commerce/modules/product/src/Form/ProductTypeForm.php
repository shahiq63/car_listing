<?php

namespace Drupal\commerce_product\Form;

use Drupal\commerce\EntityHelper;
use Drupal\commerce\EntityTraitManagerInterface;
use Drupal\commerce\Form\CommerceBundleEntityFormBase;
use Drupal\commerce_order\Entity\OrderItemTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductTypeForm extends CommerceBundleEntityFormBase {

  /**
   * The variation type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $variationTypeStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Creates a new ProductTypeForm object.
   *
   * @param \Drupal\commerce\EntityTraitManagerInterface $trait_manager
   *   The entity trait manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTraitManagerInterface $trait_manager, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($trait_manager);

    $this->variationTypeStorage = $entity_type_manager->getStorage('commerce_product_variation_type');
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_entity_trait'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $this->entity;
    $variation_types = $this->variationTypeStorage->loadMultiple();
    // Create an empty product to get the default status value.
    // @todo Clean up once https://www.drupal.org/node/2318187 is fixed.
    if ($this->operation == 'add') {
      $product = $this->entityTypeManager->getStorage('commerce_product')->create(['type' => $product_type->uuid()]);
    }
    else {
      $product = $this->entityTypeManager->getStorage('commerce_product')->create(['type' => $product_type->id()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $product_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $product_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product\Entity\ProductType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('This text will be displayed on the <em>Add product</em> page.'),
      '#default_value' => $product_type->getDescription(),
    ];
    $form['variationType'] = [
      '#type' => 'select',
      '#title' => $this->t('Product variation type'),
      '#default_value' => $product_type->getVariationTypeId(),
      '#options' => EntityHelper::extractLabels($variation_types),
      '#disabled' => !$product_type->isNew(),
    ];
    if ($product_type->isNew()) {
      $form['variationType']['#empty_option'] = $this->t('- Create new -');
      $form['variationType']['#description'] = $this->t('If an existing product variation type is not selected, a new one will be created.');
    }
    $form['injectVariationFields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inject product variation fields into the rendered product.'),
      '#default_value' => $product_type->shouldInjectVariationFields(),
    ];
    $form['product_status'] = [
      '#type' => 'checkbox',
      '#title' => t('Publish new products of this type by default.'),
      '#default_value' => $product->isPublished(),
    ];
    $form = $this->buildTraitForm($form, $form_state);

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'commerce_product',
          'bundle' => $product_type->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('commerce_product', $product_type->id()),
      ];
      $form['#submit'][] = 'language_configuration_element_submit';
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateTraitForm($form, $form_state);

    if (empty($form_state->getValue('variationType'))) {
      $id = $form_state->getValue('id');
      if (!empty($this->entityTypeManager->getStorage('commerce_product_variation_type')->load($id))) {
        $form_state->setError($form['variationType'], $this->t('A product variation type with the machine name @id already exists. Select an existing product variation type or change the machine name for this product type.', [
          '@id' => $id,
        ]));
      }

      if ($this->moduleHandler->moduleExists('commerce_order')) {
        $order_item_type_ids = $this->getOrderItemTypeIds();
        if (empty($order_item_type_ids)) {
          $form_state->setError($form['variationType'], $this->t('A new product variation type cannot be created, because no order item types were found. Select an existing product variation type or retry after creating a new order item type.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $this->entity;
    // Create a new product variation type.
    if (empty($form_state->getValue('variationType'))) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
      $variation_type = $this->entityTypeManager->getStorage('commerce_product_variation_type')->create([
        'id' => $form_state->getValue('id'),
        'label' => $form_state->getValue('label'),
      ]);
      if ($this->moduleHandler->moduleExists('commerce_order')) {
        $order_item_type_ids = $this->getOrderItemTypeIds();
        $order_item_type_id = isset($types['default']) ? 'default' : reset($order_item_type_ids);
        $variation_type->setOrderItemTypeId($order_item_type_id);
      }
      $variation_type->save();
      $product_type->setVariationTypeId($form_state->getValue('id'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    // Update the default value of the status field.
    $product = $this->entityTypeManager->getStorage('commerce_product')->create(['type' => $this->entity->id()]);
    $value = (bool) $form_state->getValue('product_status');
    if ($product->status->value != $value) {
      $fields = $this->entityFieldManager->getFieldDefinitions('commerce_product', $this->entity->id());
      $fields['status']->getConfig($this->entity->id())->setDefaultValue($value)->save();
      $this->entityFieldManager->clearCachedFieldDefinitions();
    }
    $this->submitTraitForm($form, $form_state);

    $this->messenger()->addMessage($this->t('The product type %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_product_type.collection');
    if ($status == SAVED_NEW) {
      commerce_product_add_stores_field($this->entity);
      commerce_product_add_body_field($this->entity);
      commerce_product_add_variations_field($this->entity);
    }
  }

  /**
   * Gets the available order item type IDs.
   *
   * Only order item types that can be used to purchase product variations
   * are included.
   *
   * @return string[]
   *   The order item type IDs.
   */
  protected function getOrderItemTypeIds() {
    $order_item_type_storage = $this->entityTypeManager->getStorage('commerce_order_item_type');
    $order_item_types = $order_item_type_storage->loadMultiple();
    $order_item_types = array_filter($order_item_types, function (OrderItemTypeInterface $type) {
      return $type->getPurchasableEntityTypeId() == 'commerce_product_variation';
    });

    return array_keys($order_item_types);
  }

}
