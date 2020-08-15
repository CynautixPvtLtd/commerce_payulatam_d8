<?php /**
 * @file
 * Contains \Drupal\commerce_payulatam\Controller\DefaultController.
 */

namespace Drupal\commerce_payulatam\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the commerce_payulatam module.
 */
class DefaultController extends ControllerBase {

  public function commerce_payulatam_access_page($order, $token, $option, $page, Drupal\Core\Session\AccountInterface $account) {
    $method = ($page == 'confirmation' ? $_POST : $_GET);
    $is_valid = commerce_payulatam_validate_signature($method, $order, $page);
    return $option == commerce_payulatam_get_md5($order->order_id, $token, $page) && $is_valid;
  }

  public function commerce_payulatam_response($order) {
    $param = commerce_payulatam_get_param($_GET);

    $param['value'] = number_format($param['value'], 1, '.', '');

    if ($param['transactionState'] == 6 && $param['polResponseCode'] == 5) {
      $param['message'] = t('Failed transaction');
    }
    elseif ($param['transactionState'] == 6 && $param['polResponseCode'] == 4) {
      $param['message'] = t('Transaction rejected');
    }
    elseif ($param['transactionState'] == 12 && $param['polResponseCode'] == 9994) {
      $param['message'] = t('Pending, Please check whether the debit was made in the Bank');
    }
    elseif ($param['transactionState'] == 4 && $param['polResponseCode'] == 1) {
      $param['message'] = t('Transaction approved');
    }
    else {
      // Nothing to do
    }

    $rows = [
      [t('Transaction state'), $param['message']],
      [
        t('Transaction ID'),
        $param['transactionId'],
      ],
      [t('Sale reference'), $param['reference_pol']],
      [
        t('Transaction Reference'),
        $param['referenceCode'],
      ],
      [t('Ammount'), $param['value']],
      [t('Currency'), $param['currency']],
      [
        t('Description'),
        $param['description'],
      ],
      [t('Entity'), $param['lapPaymentMethod']],
    ];

    if ($param['pseBank']) {
      $rows[] = [t('CUS'), $param['cus']];
      $rows[] = [t('Bank'), $param['pseBank']];
    }

    $content['table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
    ];

    \Drupal::moduleHandler()->alter('commerce_payulatam_response', $content, $param);

    return $content;
  }

  public function commerce_payulatam_confirmation($order) {
    $result = [
      'status' => 1,
      'message' => t('Confirmation success'),
    ];

    try {
      $param = commerce_payulatam_get_param($_POST);
      $transaction = commerce_payulatam_save_transation($param, $order);

      $payment = commerce_payment_method_instance_load($order->data['payment_method']);

      $default_status = [
        COMMERCE_PAYMENT_STATUS_SUCCESS => 'pending',
        COMMERCE_PAYMENT_STATUS_FAILURE => 'canceled',
      ];

      if (!isset($payment['settings']['py_assign_status']) || $payment['settings']['py_assign_status'] == 'M') {
        $status = $default_status[$transaction->status];

        if (isset($payment['settings']['py_status_' . $transaction->status])) {
          $status = $payment['settings']['py_status_' . $transaction->status];
        }

        commerce_order_status_update($order, $status);
        if ($transaction->status == COMMERCE_PAYMENT_STATUS_SUCCESS) {
          commerce_checkout_complete($order);
        }
      }
      elseif ($transaction->status == COMMERCE_PAYMENT_STATUS_SUCCESS) {
        commerce_payment_redirect_pane_next_page($order);
      }
      else {
        commerce_payment_redirect_pane_previous_page($order);
      }
      \Drupal::moduleHandler()->invokeAll('commerce_payulatam_confirmation', $order, $transaction);
    }

      catch (Exception $e) {
      \Drupal::logger('commerce_payulatam', $e->getMessage(), [], WATCHDOG_NOTICE, \Drupal\Core\Url::fromRoute("<current>")->toString());
      $result['message'] = $e->getMessage();
    }

    return $result;
  }

  public function commerce_payulatam_page_view($entity) {
    return entity_view('commerce_payulatam', [$entity]);
  }

}
