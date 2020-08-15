<?php
namespace Drupal\commerce_payulatam;

use Drupal\Core\Url;

class CommercePayuLatamEntityController extends EntityAPIController {

  public function buildContent($entity, $view_mode = 'full', $langcode = NULL, $content = array()) {
    $rows = array();

    $rows[] = array(t('Id'), $entity->id);
    $rows[] = array(t('Order'), Url::fromRoute($entity->order_id, 'admin/commerce/orders/' . $entity->order_id . '/view'));

    $rows[] = array(t('Created'), format_date($entity->created, 'custom', 'Y-m-d H:i:s'));

    $rows[] = array(t('State Transaction'), $entity->state_transaction);

    $rows[] = array(t('Reference Payulatam'), $entity->reference_payulatam);

    $rows[] = array(t('Value'), number_format($entity->value, 2, ',', '.'));

    $rows[] = array(t('Response'), '<pre>' . print_r($entity->response, TRUE) . '</pre>');

    $content['table_transaction'] = array(
      '#markup' => _theme('table', array(
        'rows' => $rows,
        'header' => array(
          t('Item'),
          t('Value'),
        ),
      ))
    );

    return parent::buildContent($entity, $view_mode, $langcode, $content);
  }

}
