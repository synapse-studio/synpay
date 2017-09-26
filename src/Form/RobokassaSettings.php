<?php

namespace Drupal\synpay\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form controller.
 */
class RobokassaSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'robokassa_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['synpay_robokassa.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('synpay_robokassa.settings');

    $form['robokassa'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];
    $form["robokassa"]['login'] = array(
      '#title' => $this->t('Login'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('login'),
    );
    $form["robokassa"]['pass_1'] = array(
      '#title' => $this->t('Pass #1'),
      '#type' => 'password',
      '#required' => TRUE,
      '#default_value' => $config->get('pass_1'),
    );
    $form["robokassa"]['pass_2'] = array(
      '#title' => $this->t('Pass #2'),
      '#type' => 'password',
      '#required' => TRUE,
      '#default_value' => $config->get('pass_2'),
    );
    $form["robokassa"]['test'] = array(
      '#title' => $this->t('Is test'),
      '#type' => 'checkbox',
      '#required' => FALSE,
      '#default_value' => $config->get('test'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('synpay_robokassa.settings');
    $config
      ->set('test', $form_state->getValue('test'))
      ->set('pass_1', $form_state->getValue('pass_1'))
      ->set('pass_2', $form_state->getValue('pass_2'))
      ->set('login', $form_state->getValue('login'))
      ->save();
  }

}
