<?php

namespace Drupal\user_agreement\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for User agreement edit forms.
 *
 * @ingroup user_agreement
 */
class UserAgreementRevisionEditForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function showRevisionUi() {
    $this->show_revision_ui = TRUE;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\user_agreement\Entity\UserAgreement $entity */
    $form = parent::buildForm($form, $form_state);

    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];

    $form['author_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Author information'),
      '#open' => FALSE,
      '#group' => 'advanced',
      '#weight' => 20,
      '#attributes' => [
        'class' => ['entity-content-form-author-information'],
      ],
      '#attached' => [
        'library' => ['core/drupal.entity-form'],
      ],
    ];

    $user_agreement_storage = $this->entityTypeManager->getStorage('user_agreement');
    if ($vid = $this->entity->getLoadedRevisionId()) {
      $revision = $user_agreement_storage->loadRevision($vid);
      $latest_revision = $revision->isDefaultRevision();
      // If this is the current published revision, we want to ensure an edit
      // will create a new revision.
      // Set the "Create new revision" to true and hide it.
      if ($latest_revision && $revision->isPublished()) {
        $form['revision']['#default_value'] = TRUE;
        $form['revision']['#prefix'] = '<div class="hidden">';
        $form['revision']['#suffix'] = '</div>';
        $form['revision_log_message']['#states'] = [];
      }
    }

    $form['user_id']['#group'] = 'author_information';

    // Hide the publish checkbox.
    // New edits should be unpublish by default.
    $form['status']['widget']['value']['#description'] = $this->t("<em>Caution:</em> Checking this box will mark this as the published version and viewable to the site's users.");
    $form['status']['widget']['value']['#default_value'] = FALSE;
    $form['status']['#prefix'] = '<div class="hidden">';
    $form['status']['#suffix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $last_published_vid = $entity->getDefaultRevisionId();

    $entity->setNewRevision(FALSE);
    $entity->isDefaultRevision(FALSE);

    if (!$form_state->isValueEmpty('revision') && $form_state->getValue('revision') != FALSE) {
      $entity->setNewRevision(TRUE);
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }

    $status = parent::save($form, $form_state);
    $published = $form_state->getValue('status')['value'] == 1;

    // If revision isn't published, set the default to the previous one.
    if (!$published) {
      $storage = $this->entityManager->getStorage('user_agreement');
      $previous_revision = $storage->loadRevision($last_published_vid);
      $previous_revision->setNewRevision(FALSE);
      $previous_revision->isDefaultRevision(TRUE);

      // Also, unset the current revision as default.
      $entity->isDefaultRevision(FALSE);
    }
    else {
      $entity->isDefaultRevision(TRUE);
    }
    $entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label User agreement.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label User agreement.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.user_agreement.version_history', ['user_agreement' => $entity->id()]);
  }

}
