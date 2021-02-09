<?php

namespace Drupal\user_agreement\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a form for deleting a User agreement revision.
 *
 * @ingroup user_agreement
 */
class UserAgreementRevisionDeleteForm extends ConfirmFormBase {

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
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->userAgreementStorage = $container->get('entity_type.manager')->getStorage('user_agreement');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_agreement_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
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
    return $this->t('Delete');
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
    $this->userAgreementStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('User agreement: deleted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()->addMessage($this->t('Revision from %revision-date of User agreement %title has been deleted.', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label(),
    ]));
    $form_state->setRedirect(
      'entity.user_agreement.canonical',
       ['user_agreement' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {user_agreement_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.user_agreement.version_history',
         ['user_agreement' => $this->revision->id()]
      );
    }
  }

}
