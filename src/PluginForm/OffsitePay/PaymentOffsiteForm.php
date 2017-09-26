<?php

namespace Drupal\synpay\PluginForm\OffsitePay;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $redirect_method = $payment_gateway_plugin->getConfiguration()['redirect_method'];
    $redirect_url = Url::fromRoute('synpay.choose_method', [], ['absolute' => TRUE])->toString();
    $bilingProfile = $payment->getOrder()->getBillingProfile();
    $data = [
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'total' => $payment->getAmount()->getNumber(),
      'id' => $payment->getOrderId(),
      'email' => isset($bilingProfile->field_customer_email->value) ? $bilingProfile->field_customer_email->value : '',
      'fio' => isset($bilingProfile->field_customer_fie->value) ? $bilingProfile->field_customer_fie->value : '',
    ];

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

  /**
   * Builds the redirect form.
   *
   * @param array $form
   *   The plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $redirect_url
   *   The redirect url.
   * @param array $data
   *   Data that should be sent along.
   * @param string $redirect_method
   *   The redirect method (REDIRECT_GET or REDIRECT_POST constant).
   *
   * @return array
   *   The redirect form, if $redirect_method is REDIRECT_POST.
   *
   * @throws NeedsRedirectException
   *   The redirect exception, if $redirect_method is REDIRECT_GET.
   */
  protected function buildRedirectForm(array $form, FormStateInterface $form_state, $redirect_url, array $data, $redirect_method = self::REDIRECT_GET) {
    $form['commerce_message'] = [
      '#markup' => '<div class="checkout-help"></div>',
      '#weight' => -10,
      // Plugin forms are embedded using #process, so it's too late to attach
      // another #process to $form itself, it must be on a sub-element.
      '#process' => [
        [get_class($this), 'processRedirectForm'],
      ],
      '#action' => $redirect_url,
    ];
    foreach ($data as $key => $value) {
      $form[$key] = [
        '#type' => 'hidden',
        '#value' => $value,
        // Ensure the correct keys by sending values from the form root.
        '#parents' => [$key],
      ];
    }
    $form['choose_option'] = [
      '#type' => 'select',
      '#options' => [
        'cash' => 'Оплата наличными средствами',
        'online' => 'Оплатить онлайн на сайте',
      ],
      '#default_value' => 'cash',
      '#parents' => ['choose_option'],
    ];

    return $form;
  }

}
