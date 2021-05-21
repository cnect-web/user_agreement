<?php

namespace Drupal\user_agreement\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class UserAgreementSettingsForm.
 *
 * Provides user agreement settings form.
 *
 * @ingroup user_agreement
 */
class UserAgreementSettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'user_agreement_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user_agreement.user_agreement_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('user_agreement.user_agreement_settings');

    $form['redirect_url'] = [
      '#title' => $this->t("Redirect to URL"),
      '#type' => 'textfield',
      '#description' => $this->t("Leave empty to be redirected to the page from where you invoked the login.<\br>If you wish to override this behaviour, specify the destination here.<\br>Internal paths should start with a /, e.g. /documentation"),
      '#default_value' => $config->get('redirect_url'),
    ];

    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('user_agreement.user_agreement_settings')
      ->set('redirect_url', $form_state->getValue('redirect_url'))
      ->save();
  }

}
