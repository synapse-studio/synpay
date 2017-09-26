<?php

namespace Drupal\synpay\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the credit card payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "cash",
 *   label = @Translation("Cash"),
 *   create_label = @Translation("On delivery"),
 * )
 */
class Cash extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $args = [
      '@cash_name' => $payment_method->cash_name->value,
    ];
    return $this->t('Сохраненный профиль @cash_name', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['cash_name'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Cash name'))
      ->setDescription(t('What name o.O'));

    return $fields;
  }

}
