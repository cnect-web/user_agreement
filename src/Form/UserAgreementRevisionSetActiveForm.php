<?php

namespace Drupal\user_agreement\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user_agreement\Entity\UserAgreementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a User agreement revision.
 *
 * @ingroup user_agreement
 */
class UserAgreementRevisionSetActiveForm extends ConfirmFormBase {

  use StringTranslationTrait;

  /**
   * The User agreement revision.
   *
   * @var \Drupal\user_agreement\Entity\UserAgreementInterface
   */
  protected $revision;

  /**
   * The User agreement storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userAgreementStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->userAgreementStorage = $container->get('entity_type.manager')->getStorage('user_agreement');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_agreement_revision_set_active_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to set this revision as active?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.user_agreement.version_history', ['user_agreement' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Set as active');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user_agreement_revision = NULL) {
    $this->revision = $this->userAgreementStorage->loadRevision($user_agreement_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = $this->t('Set as active on %date.', [
      '%date' => $this->dateFormatter->format($original_revision_timestamp),
    ]);
    $this->revision->save();

    $this->logger('content')->notice('User agreement: reverted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()->addMessage($this->t('User agreement %title has been reverted to the revision from %revision-date.', [
      '%title' => $this->revision->label(),
      '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
    ]));
    $form_state->setRedirect(
      'entity.user_agreement.version_history',
      ['user_agreement' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\user_agreement\Entity\UserAgreementInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\user_agreement\Entity\UserAgreementInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(UserAgreementInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision(FALSE);
    $revision->set('status', 1);
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
