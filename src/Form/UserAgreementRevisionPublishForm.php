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
class UserAgreementRevisionPublishForm extends ConfirmFormBase {

  use StringTranslationTrait;

  /**
   * The User agreement revision.
   *
   * @var \Drupal\user_agreement\Entity\UserAgreementInterface
   */
  protected $revision;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->time = $container->get('datetime.time');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_agreement_revision_publish_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to publish the revision %revision-number?', [
      '%revision-number' => $this->revision->getRevisionId(),
    ]);
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
    return $this->t('Publish');
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
    $user_agreement_storage = $this->entityTypeManager->getStorage('user_agreement');
    $this->revision = $user_agreement_storage->loadRevision($user_agreement_revision);
    $form = parent::buildForm($form, $form_state);

    $view_builder = $this->entityTypeManager->getViewBuilder('user_agreement');

    $form['preview_revision'] = [
      '#title' => $this->t("This version"),
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];
    $form['preview_revision']['content'] = $view_builder->view($this->revision, 'full');

    $user_agreement = $user_agreement_storage->load($this->revision->id());
    if ($vid = $user_agreement->getDefaultRevisionId()) {
      $form['preview_current'] = [
        '#title' => $this->t("Current published version"),
        '#type' => 'fieldset',
        '#tree' => TRUE,
      ];
      $form['preview_current']['content'] = $view_builder->view($user_agreement_storage->loadRevision($vid), 'full');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->revision = $this->preparePublishedRevision($this->revision, $form_state);
    $this->revision->revision_log = $this->t('Published revision %revision_id.', [
      '%revision_id' => $this->revision->getRevisionId(),
    ]);
    $this->revision->save();

    $this->logger('content')->notice('User agreement: published %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()->addMessage($this->t('User agreement %title revision %revision has been published.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]));
    $form_state->setRedirect(
      'entity.user_agreement.version_history',
      ['user_agreement' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be published.
   *
   * @param \Drupal\user_agreement\Entity\UserAgreementInterface $revision
   *   The revision to be published.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\user_agreement\Entity\UserAgreementInterface
   *   The prepared revision ready to be stored.
   */
  protected function preparePublishedRevision(UserAgreementInterface $revision, FormStateInterface $form_state) {
    // Publish it.
    $revision->setPublished();

    $revision->setNewRevision(FALSE);
    $revision->isDefaultRevision(TRUE);
    $revision->setRevisionCreationTime($this->time->getRequestTime());

    // Unset revision_default flag from another revisions.
    \Drupal::database()->update('user_agreement_revision')
      ->fields(['revision_default' => 0])
      ->condition('revision_default', 1)
      ->condition('vid', $revision->getRevisionId(), '<>')
      ->condition('id', $revision->id())
      ->execute();

    return $revision;
  }

}
