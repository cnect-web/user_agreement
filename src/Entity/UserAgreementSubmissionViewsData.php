<?php

namespace Drupal\user_agreement\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for User agreement submission entities.
 */
class UserAgreementSubmissionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['user_agreement_submission']['status']['field']['id'] = 'user_agreement_status_field';
    $data['user_agreement_submission']['status']['filter']['id'] = 'user_agreement_status_field_filter';
    return $data;
  }

}
