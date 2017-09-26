<?php

namespace Drupal\synpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "synapse_robo_pay",
 *   label = "Synapse off site pay",
 *   display_label = "Synapse off site",
 *   forms = {
 *     "offsite-payment" = "Drupal\synpay\PluginForm\OffsitePay\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"robo"},
 * )
 */
class OffsitePay extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['payment_method_types'][] = 'robo';
    $config['redirect_method'] = 'post';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // A real gateway would always know which redirect method should be used,
    // it's made configurable here for test purposes.
    $form['redirect_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirect method'),
      '#options' => [
        'get' => $this->t('Redirect via GET (302 header)'),
        'post' => $this->t('Redirect via POST'),
      ],
      '#default_value' => $this->configuration['redirect_method'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['redirect_method'] = $values['redirect_method'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $type = $request->query->has('type') ? $request->query->get('type') : FALSE;
    if (!$type == 'cash') {
      throw new PaymentGatewayException('Нет оплаты');
      // $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      // $payment = $payment_storage->create([
      //   'state' => 'authorization',
      //   'amount' => $order->getTotalPrice(),
      //   'payment_gateway' => $this->entityId,
      //   'order_id' => $order->id(),
      //   'test' => $this->getMode() == 'test',
      //   'remote_id' => $request->query->get('txn_id'),
      //   'remote_state' => $request->query->get('payment_status'),
      //   'authorized' => \Drupal::time()->getRequestTime(),
      // ]);
      // $payment->save();
    }
  }

}
