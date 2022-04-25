<?php

namespace Drupal\commerce_coupon_bulk_modify;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_promotion\Entity\PromotionInterface;

/**
 * Batch class to process coupons.
 */
class ProcessCouponBatch {

  /**
   * Callback defined on the batch for process every order individually.
   *
   * @param int $limit
   *   The quantity of the coupons to process per operation. 
   * @param array $coupon_values
   *    The collection of input values.
   * @param array $context
   *   The batch context.
   */
  public static function processCoupons(array $chunk, $rows_count, $coupon_values, array &$context) {
    
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_node'] = 0;
      $context['sandbox']['max'] = $rows_count;
    }
    
    foreach($chunk as $coupon) {
        // load coupon entity
        $coupon->setUsageLimit($coupon_values['usage_limit']);
        $coupon->setCustomerUsageLimit($coupon_values['usage_limit_customer']);
        $coupon->save();

        $context['message'] = t('Updating coupon: @coupon_code - Promotion ID: @promotion_id', [
          '@coupon_code' => $coupon->getCode(),
          '@promotion_id' => $coupon_values['promotion_id'],
        ]);
        
        $context['sandbox']['current_node'] = $coupon->id();
        $context['sandbox']['progress']++;   
    }   
    
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }

    $context['results'] = $rows_count;
  }

  /**
   * Callback that check the end of the batch process.
   *
   * @param bool $success
   *   Boolean to flag if batch run was successful.
   * @param int $results
   *   Results of processed batch operations.
   * @param array $operations
   *   Operations ran in the batch job.
   */
  public static function processCouponsFinishedCallback(
    $success,
    $results,
    array $operations
  ) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        $results,
        'One coupon processed.', '@count coupons processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }

    drupal_set_message($message);

  }

}
