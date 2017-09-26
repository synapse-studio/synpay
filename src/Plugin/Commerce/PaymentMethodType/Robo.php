<?php

namespace Drupal\synpay\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the credit card payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "robo",
 *   label = @Translation("Robo"),
 *   create_label = @Translation("Via service"),
 * )
 */
class Robo extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $args = [
      '@robo_name' => $payment_method->robo_name->value,
    ];
    return $this->t('@robo_name', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['robo_name'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Robo name'))
      ->setDescription(t('Robo name'));

    return $fields;
  }

}
