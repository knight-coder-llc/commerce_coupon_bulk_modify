<?php

namespace Drupal\commerce_coupon_bulk_modify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_promotion\Entity\PromotionInterface;

/**
 * Form for the Coupon Modifier Batch tool.
 */
class CouponModifierForm extends FormBase {

  /**
   * The number of coupons to update in each batch.
   *
   * @var int
   */
  const BATCH_SIZE = 25;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_coupon_bulk_modify_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['modify_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity of the coupons for one modify operation'),
      '#min' => 1,
      '#step' => 1,
      '#default_value' => self::BATCH_SIZE,
    ];

    $form['limit'] = [
      '#type' => 'radios',
      '#title' => $this->t('Number of uses per coupon'),
      '#options' => [
        0 => $this->t('Unlimited'),
        1 => $this->t('Limited number of uses'),
      ],
      '#default_value' => 0,
    ];

    $form['usage_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of uses'),
      '#title_display' => 'invisible',
      '#default_value' => 1,
      '#min' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="limit"]' => ['value' => 0],
        ],
      ],
    ];

    $form['limit_customer'] = [
      '#type' => 'radios',
      '#title' => $this->t('Number of uses per customer per coupon'),
      '#options' => [
        0 => $this->t('Unlimited'),
        1 => $this->t('Limited number of uses'),
      ],
      '#default_value' => 0,
    ];

    $form['usage_limit_customer'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of uses per customer'),
      '#title_display' => 'invisible',
      '#default_value' => 1,
      '#min' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="limit_customer"]' => ['value' => 0],
        ],
      ],
    ];

    $form['promotion_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Promotion to add coupons'),
      '#options' => $this->getListOfPromotions(),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $coupon_values = [
      'promotion_id' => $values['promotion_id'],
      'usage_limit' => $values['limit'] ? $values['usage_limit'] : 0,
      'usage_limit_customer' => $values['limit_customer'] ? $values['usage_limit_customer'] : 0,
    ];

    $promotion = Promotion::load($coupon_values['promotion_id']);
    $coupons = $promotion->getCoupons();

    $modify_limit = $form_state->getValue('modify_limit');
    $coupon_operation_function = '\Drupal\commerce_coupon_bulk_modify\ProcessCouponBatch::processCoupons';

    $count = count($coupons);

    $operations = [];
    
    foreach(array_chunk($coupons, $modify_limit) as $chunk) {
      $operations[] = [
        $coupon_operation_function, [$chunk, $count, $coupon_values],
      ];
    }
    
    

    $batch = [
      'title' => t('Updating coupons...'),
      'operations' => $operations,
      'finished' => '\Drupal\commerce_coupon_bulk_modify\ProcessCouponBatch::processCouponsFinishedCallback',
    ];

    batch_set($batch);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // $values = $form_state->getValues();
    // // Make sure that the total length doesn't exceed the database limit.
    // $code_length = strlen($values['prefix']) + strlen($values['suffix']) + $values['length'];
    // if ($code_length > self::MAX_CODE_LENGTH) {
    //   $form_state->setError($form['pattern'], $this->t('The total pattern length (@coupon_length) exceeds the maximum length allowed (@max_length).', [
    //     '@coupon_length' => $code_length,
    //     '@max_length' => self::MAX_CODE_LENGTH,
    //   ]));
    // }
  }

  /**
   * Get list of promotion ids.
   *
   * @return array
   *   Returns a list of promotions.
   */
  public function getListOfPromotions() {
    $entityQuery = \Drupal::entityQuery('commerce_promotion');
    $promotion_ids = $entityQuery->execute();
    $promotions = \Drupal::entityTypeManager()->getStorage('commerce_promotion')->loadMultiple($promotion_ids);

    $promotions_list = [];
    foreach ($promotions as $promotion_id => $promotion) {
      $promotions_list[$promotion_id] = $promotion->getName();
    }
    return $promotions_list;
  }

}
