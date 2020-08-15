<?php
/**
 * @file
 * Contains Drupal\welcome\Form\MessagesForm.
 */
namespace Drupal\commerce_payulatam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
\Drupal::logger('commerce_payulatam', 'wief');
class CommercePayulatamSettingsForm extends ConfigFormBase {

/**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_payulatam.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_payulatam_form';
  }

 /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('commerce_payulatam.adminsettings');

    $form['py_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('API KEY'),
      '#required' => TRUE,
      '#default_value' => isset($settings['py_api_key']) ? $settings['py_api_key'] : COMMERCE_PAYULTAM_APIKEY,
    );

    $form['py_merchant_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Merchant Id'),
      '#required' => TRUE,
      '#default_value' => isset($settings['py_merchant_id']) ? $settings['py_merchant_id'] : COMMERCE_PAYULATAM_MERCHANTID,
    );

    $form['py_account_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Account Id'),
      '#default_value' => isset($settings['py_account_id']) ? $settings['py_account_id'] : NULL,
    );

    $py_action = COMMERCE_PAYULATAM_URL_PRODUCTION;
    if (isset($settings['py_action_url_production'])) {
      $py_action = $settings['py_action_url_production'];
    }

    $form['py_action_url_production'] = array(
      '#title' => t('Path Form Action'),
      '#type' => 'textfield',
      '#size' => 50,
      '#default_value' => $py_action,
      '#required' => TRUE,
    );

    $py_action_test = COMMERCE_PAYULATAM_URL_TEST;
    if (isset($settings['py_action_url_test'])) {
      $py_action_test = $settings['py_action_url_test'];
    }

    $form['py_action_url_test'] = array(
      '#title' => t('Test Path Form Action'),
      '#type' => 'textfield',
      '#size' => 50,
      '#default_value' => $py_action_test,
      '#required' => TRUE,
    );

    $items = array();
    if (function_exists('commerce_tax_rates')) {
      $items = commerce_tax_rates();
    }

    $options = array();
    foreach ($items as $name => $item) {
      $options[$name] = $item['title'];
    }

    $form['py_tax'] = array(
      '#title' => t('Tax Rate'),
      '#type' => 'select',
      '#size' => 5,
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => isset($settings['py_tax']) ? $settings['py_tax'] : NULL,
    );

    $py_alias = isset($settings['py_alias']) ? $settings['py_alias'] : COMMERCE_PAYULATAM_ALIAS;
    $form['py_alias'] = array(
      '#title' => t('Alias'),
      '#description' => t('Is concatenated with the order number, example: @example', array(
        '@example' => '"' . $py_alias . '1"',
      )),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $py_alias,
    );

    $description = isset($settings['py_description']) ? $settings['py_description'] : COMMERCE_PAYULATAM_DESCRIPTION;

    $form['py_description'] = array(
      '#title' => t('Description'),
      '#description' => t('PAYULATAM purchase description, use @order_id to obtain the order number'),
      '#type' => 'textfield',
      '#size' => 50,
      '#default_value' => $description,
      '#required' => TRUE,
    );

    $status = [];
    $form['py_assign_status'] = array(
      '#title' => t('Assign Status'),
      '#type' => 'radios',
      '#options' => array(
        'A' => t('Automatic'),
        'M' => t('Manual'),
      ),
      '#default_value' => isset($settings['py_assign_status']) ? $settings['py_assign_status'] : 'M',
      '#required' => TRUE,
    );

    $form['py_status_' . COMMERCE_PAYMENT_STATUS_SUCCESS] = array(
      '#title' => t('Status Success'),
      '#type' => 'select',
      '#options' => $status,
      '#default_value' => isset($settings['py_status_' . COMMERCE_PAYMENT_STATUS_SUCCESS]) ? $settings['py_status_' . COMMERCE_PAYMENT_STATUS_SUCCESS] : 'pending',
      //'#required' => TRUE,
      '#states' => array(
        'visible' => array('input[name*="py_assign_status"]' => array('value' => 'M')),
      ),
    );

    $form['py_status_' . COMMERCE_PAYMENT_STATUS_FAILURE] = array(
      '#title' => t('Status Failure'),
      '#type' => 'select',
      '#options' => $status,
      '#default_value' => isset($settings['py_status_' . COMMERCE_PAYMENT_STATUS_FAILURE]) ? $settings['py_status_' . COMMERCE_PAYMENT_STATUS_FAILURE] : 'canceled',
      //'#required' => TRUE,
      '#states' => array(
        'visible' => array('input[name*="py_assign_status"]' => array('value' => 'M')),
      ),
    );

    $form['py_description'] = array(
      '#title' => t('Description'),
      '#description' => t('PAYULATAM purchase description, use @order_id to obtain the order number'),
      '#type' => 'textfield',
      '#size' => 50,
      '#default_value' => $description,
      '#required' => TRUE,
    );

    $form['py_testing'] = array(
      '#title' => t('Test Enabled'),
      '#type' => 'select',
      '#options' => array(
        '0' => t('No'),
        '1' => t('Yes'),
      ),
      '#required' => TRUE,
      '#default_value' => isset($settings['py_testing']) ? $settings['py_testing'] : NULL,
    );

    $form['py_language'] = array(
      '#title' => t('Language'),
      '#type' => 'select',
      '#options' => $this->commerce_payulatam_get_languages(),
      '#required' => TRUE,
      '#default_value' => isset($settings['py_language']) ? $settings['py_language'] : NULL,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('commerce_payulatam.adminsettings')
      ->set('commerce_payulatam_message', $form_state->getValue('commerce_payulatam_message'))
      ->save();
  }

  function commerce_payulatam_get_languages() {
    return array(
      'en' => t('English'),
      'es' => t('Spanish'),
      'pt' => t('Portuguese'),
    );
  }
}
