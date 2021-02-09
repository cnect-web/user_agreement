<?php

namespace Drupal\user_agreement\Plugin\views\field;

use Drupal\Core\StringTranslation\StringTranslationTrait;
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
      case '0':
        $value = $this->t("Rejected");
        break;

      case '1':
        $value = $this->t("Accepted");
        break;

    }
    return $value;
  }

}
