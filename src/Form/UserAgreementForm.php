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
class UserAgreementForm extends ContentEntityForm {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    $instance->routeMatch = $container->get('current_route_match');
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

    if (!$this->entity->isNew()) {
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

      $form['user_id']['#group'] = 'author_information';

      $route = $this->routeMatch->getRouteName();
      $langcode = $form_state->getFormObject()->getEntity()->get('langcode')->value;

      // These fields are shared between translations,
      // so just check if we're translating stuff.
      // @todo maybe they shouldn't be shared?
      $translation_routes = [
        'entity.user_agreement.content_translation_add',
        'entity.user_agreement.content_translation_edit',
        'entity.user_agreement.content_translation_delete',
      ];

      $languages = [
        'en',
        'und',
      ];

      if (in_array($langcode, $languages) && !in_array($route, $translation_routes)) {
        $form['status']['widget']['value']['#type'] = 'hidden';
        $form['status']['widget']['value']['#default_value'] = FALSE;
        $form['revision']['#default_value'] = TRUE;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision') && $form_state->getValue('revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

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
    $form_state->setRedirect('entity.user_agreement.canonical', ['user_agreement' => $entity->id()]);
  }

}
