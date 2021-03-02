<?php

namespace Drupal\user_agreement\Plugin\views\field;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user_agreement\Entity\UserAgreementSubmission;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Render user agreement status field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_agreement_status_field")
 */
class UserAgreementStatusField extends FieldPluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    switch ($value) {
      case UserAgreementSubmission::REJECTED:
        $value = $this->t("Rejected");
        break;

      case UserAgreementSubmission::ACCEPTED:
        $value = $this->t("Accepted");
        break;

    }
    return $value;
  }

}
