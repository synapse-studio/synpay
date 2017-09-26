<?php

namespace Drupal\synpay\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is a dummy controller for mocking an off-site gateway.
 */
class SynapseRedirectController implements ContainerInjectionInterface {

  public $roboPass2 = 'XXX';
  public $roboPass1 = 'YYY';
  public $roboLogin = 'ZZZ';
  public $roboTest = 1;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new DummyRedirectController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Выбор метода оплаты.
   */
  public function chooseMethod() {
    $cancel = $this->currentRequest->request->get('cancel');
    $return = $this->currentRequest->request->get('return');
    $option = $this->currentRequest->request->get('choose_option');
    if ($option == 'cash') {
      return new TrustedRedirectResponse($return . '?type=cash');
    }
    else {
      $config = \Drupal::config('synpay_robokassa.settings');
      $roboLogin = $config->get('login');
      $roboPass1 = $config->get('pass_1');
      $roboTest = $config->get('test');

      $outSumm = $this->currentRequest->request->get('total');
      $id = $this->currentRequest->request->get('id');
      $email = $this->currentRequest->request->get('email');
      $fio = $this->currentRequest->request->get('fio');
      $roboMainUrl = 'http://auth.robokassa.ru/Merchant/Index.aspx';
      $robokassa = [
        'MerchantLogin' => $roboLogin,
        'OutSum' => $outSumm,
        'InvoiceID' => $id,
        'Description' => $fio . ' ' . $email,
        'SignatureValue' => md5($roboLogin . ':' . $outSumm . ':' . $id . ':' . $roboPass1),
        'IsTest' => $roboTest,
      ];
      $roboUrl = $roboMainUrl . '?' . http_build_query($robokassa);
      return new TrustedRedirectResponse($roboUrl);
    }
    return [];
  }

  /**
   * Builds the URL to the "return" page.
   */
  protected function buildReturnUrl($id) {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $id,
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * Builds the URL to the "cancel" page.
   */
  protected function buildCancelUrl($id) {
    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $id,
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * Builds the URL to the "complete" page.
   */
  protected function buildCompleteUrl($id) {
    return Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $id,
      'step' => 'complete',
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * Ответ от робокассы.
   */
  public function result() {
    $output = '';
    $config = \Drupal::config('synpay_robokassa.settings');
    $roboPass2 = $config->get('pass_2');
    $mrh_pass2 = $roboPass2;

    // чтение параметров.
    if (!isset($_REQUEST["SignatureValue"])) {
      return [
        '#type' => 'markup',
        '#markup' => 'Нет параметров',
      ];
    }
    $out_summ = $_REQUEST["OutSum"];
    $inv_id = $_REQUEST["InvId"];

    $crc = $_REQUEST["SignatureValue"];

    $crc = strtoupper($crc);

    $my_crc = strtoupper(md5("$out_summ:$inv_id:$mrh_pass2"));

    // проверка корректности подписи.
    if ($my_crc != $crc) {
      $output .= 'bad sign';
    }
    elseif (TRUE) {
      $commerce_order = entity_load('commerce_order', $inv_id);
      $payment_gateway = $commerce_order->get('payment_gateway')->entity;
      $payment_gateway_plugin = $payment_gateway->getPlugin();
      $checkout_flow = $commerce_order->get('checkout_flow')->entity;
      $checkout_flow_plugin = $checkout_flow->getPlugin();
      $step_id = $commerce_order->checkout_step->value;
      $next_step_id = $checkout_flow_plugin->getNextStepId($step_id);

      $payment = entity_create('commerce_payment', [
        'state' => 'authorization',
        'amount' => $commerce_order->getTotalPrice(),
        'payment_gateway' => $payment_gateway->id(),
        'order_id' => $commerce_order->id(),
        'test' => $payment_gateway_plugin->getMode() == 'test',
        'remote_id' => $inv_id,
        'authorized' => \Drupal::time()->getRequestTime(),
      ]);
      $payment->save();

      $commerce_order->set('checkout_step', $next_step_id);
      if ($next_step_id == 'complete') {
        $transition = $commerce_order->getState()->getWorkflow()->getTransition('place');
        $commerce_order->getState()->applyTransition($transition);
      }
      $commerce_order->save();
    }

    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

  /**
   * Успешно.
   */
  public function success() {
    $output = '';
    $mrh_pass1 = $this->roboPass1;
    // чтение параметров
    // read parameters
    if (!isset($_REQUEST["SignatureValue"])) {
      return [
        '#type' => 'markup',
        '#markup' => 'Нет параметров',
      ];
    }
    $out_summ = $_REQUEST["OutSum"];
    $inv_id = $_REQUEST["InvId"];
    $crc = $_REQUEST["SignatureValue"];

    $crc = strtoupper($crc);

    $my_crc = strtoupper(md5("$out_summ:$inv_id:$mrh_pass1"));

    // проверка корректности подписи.
    if ($my_crc != $crc) {
      $output .= 'Не корректная подпись';
    }
    else {
      $complete = $this->buildCompleteUrl($inv_id);
      $response = new RedirectResponse($complete);
      $response->send();
    }

    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];

  }

  /**
   * Ошибка.
   */
  public function fail() {
    if (isset($_REQUEST["InvId"])) {
      $inv_id = $_REQUEST["InvId"];
      $cancel = $this->buildCancelUrl($inv_id);
      $response = new RedirectResponse($cancel);
      $response->send();
    }
    else {
      return [
        '#markup' => 'Отмена оплаты',
      ];
    }
  }

  /**
   * тестовая.
   */
  public function sendPay() {
    // регистрационная информация (логин, пароль #1)
    // registration info (login, password #1)
    $mrh_login = $this->roboLogin;
    $mrh_pass1 = $this->roboPass1;

    // номер заказа.
    $inv_id = 88;

    // описание заказа.
    $inv_desc = "ROBOKASSA Biz-panel test";

    // сумма заказа.
    $out_summ = "45.26";

    $isTest = $this->roboTest;

    // формирование подписи.
    $crc  = md5("$mrh_login:$out_summ:$inv_id:$mrh_pass1");

    // форма оплаты товара.
    $html = "<script language=JavaScript " .
      "src='https://auth.robokassa.ru/Merchant/PaymentForm/FormS.js?" .
      "MerchantLogin=$mrh_login&OutSum=$out_summ&InvoiceID=$inv_id" .
      "&Description=$inv_desc&SignatureValue=$crc&IsTest=$isTest'></script>";
    return [
      '#type' => 'markup',
      '#markup' => $html,
      '#allowed_tags' => ['form', 'input', 'script'],
    ];
  }

}
