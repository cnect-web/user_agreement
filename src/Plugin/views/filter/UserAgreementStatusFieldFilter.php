<?php

namespace Drupal\user_agreement\Plugin\views\filter;

use Drupal\user_agreement\Entity\UserAgreementSubmission;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Filters by given list of node title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_agreement_status_field_filter")
 */
class UserAgreementStatusFieldFilter extends InOperator {

  use StringTranslationTrait;

  /**
   * Override options form element type.
   *
   * @var string
   */
  protected $valueFormType = 'select';

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('Status');
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * Override the query so that no filtering takes place if no option selected.
   */
  public function query() {
    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * Skip validation if no option selected.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * Helper function that generates the options.
   *
   * @return array
   *   An array with option_key => option_value.
   */
  public function generateOptions() {
    // Array keys are used to compare with the table field values.
    return [
      UserAgreementSubmission::ACCEPTED => $this->t("Accepted"),
      UserAgreementSubmission::REJECTED => $this->t("Rejected"),
    ];
  }

}
