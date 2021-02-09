<?php

namespace Drupal\user_agreement\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user_agreement\Entity\UserAgreementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a User agreement revision for a single trans.
 *
 * @ingroup user_agreement
 */
class UserAgreementRevisionRevertTranslationForm extends UserAgreementRevisionRevertForm {

  /**
   * The language to be reverted.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The User agreement storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userAgreementStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->languageManager = $container->get('language_manager');
    $instance->time = $container->get('datetime.time');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->userAgreementStorage = $container->get('entity_type.manager')->getStorage('user_agreement');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_agreement_revision_revert_translation_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert @language translation to the revision from %revision-date?', [
      '@language' => $this->languageManager->getLanguageName($this->langcode),
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user_agreement_revision = NULL, $langcode = NULL) {
    $this->langcode = $langcode;
    $form = parent::buildForm($form, $form_state, $user_agreement_revision);

    $form['revert_untranslated_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Revert content shared among translations'),
      '#default_value' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareRevertedRevision(UserAgreementInterface $revision, FormStateInterface $form_state) {
    $revert_untranslated_fields = $form_state->getValue('revert_untranslated_fields');

    /** @var \Drupal\user_agreement\Entity\UserAgreementInterface $default_revision */
    $latest_revision = $this->userAgreementStorage->loadRevision($revision->getRevisionId());
    $latest_revision_translation = $latest_revision->getTranslation($this->langcode);

    $revision_translation = $revision->getTranslation($this->langcode);

    foreach ($latest_revision_translation->getFieldDefinitions() as $field_name => $definition) {
      if ($definition->isTranslatable() || $revert_untranslated_fields) {
        $latest_revision_translation->set($field_name, $revision_translation->get($field_name)->getValue());
      }
    }

    $latest_revision_translation->setNewRevision(FALSE);
    $latest_revision_translation->isDefaultRevision(TRUE);
    $revision->setRevisionCreationTime($this->time->getRequestTime());

    // Unset revision_default flag from another revisions.
    \Drupal::database()->update('user_agreement_revision')
      ->fields(['revision_default' => 0])
      ->condition('revision_default', 1)
      ->condition('vid', $revision->getRevisionId(), '<>')
      ->condition('id', $revision->id())
      ->execute();

    return $latest_revision_translation;
  }

}
