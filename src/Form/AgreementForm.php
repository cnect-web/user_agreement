<?php

namespace Drupal\user_agreement\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user_agreement\Event\UserSubmissionEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user_agreement\Entity\UserAgreement;
use Drupal\user_agreement\Entity\UserAgreementSubmission;

/**
 * Class AgreementForm.
 *
 * Provides agreement form.
 */
class AgreementForm extends FormBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'agreement_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $agreement_entity = $this->routeMatch->getParameter('user_agreement');

    // On revision pages $agreement entity is an int.
    if (!$agreement_entity instanceof UserAgreement) {
      $agreement_entity = $this->entityTypeManager
        ->getStorage('user_agreement')
        ->load($agreement_entity);
    }

    $form['agreement_id'] = [
      '#type' => 'hidden',
      '#value' => $agreement_entity->id(),
    ];

    $form['agreement_vid'] = [
      '#type' => 'hidden',
      '#value' => $agreement_entity->getRevisionId(),
    ];

    $form['user_id'] = [
      '#type' => 'hidden',
      '#value' => $this->currentUser->id(),
    ];

    if ($more_info = $agreement_entity->getMoreInfo()) {
      $form['more_info'] = [
        '#title' => $this->t('More information'),
        '#type' => 'fieldset',
        '#states' => [
          'visible' => [
            ':input[name="agree_box"]' => [
              'checked' => FALSE,
            ],
          ],
        ],
      ];

      $form['more_info']['text'] = [
        '#type' => '#markup',
        '#markup' => $more_info,
      ];
    }

    $form['agree_box_title'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('By pressing "I accept", you give your consent regarding the :title.', [
        ':title' => $agreement_entity->label(),
      ]),
    ];

    $form['agree_box'] = [
      '#title' => $this->t('I accept'),
      '#type' => 'checkbox',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'agree',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $values = $form_state->getValues();

    $user_agreement = $this->entityTypeManager->getStorage('user_agreement')
      ->load($values['agreement_id']);

    $user = $this->entityTypeManager->getStorage('user')
      ->load($values['user_id']);

    if (!_user_agreement_user_has_agreed($user_agreement, $user)) {

      $user_agreement_submission_data = [
        'name' => $this->t('Agreement | User :uid - :agreement (R. :agreement_vid)', [
          ':uid' => $user->id(),
          ':agreement' => $user_agreement->id(),
          ':agreement_vid' => $values['agreement_vid'],
        ])->render(),
        'user_agreement' => ['target_id' => $values['agreement_id']],
        'user_agreement_vid' => $values['agreement_vid'],
        'user' => $values['user_id'],
      ];

      switch ($values['agree_box']) {
        case 0:
          $user_agreement_submission_data['status'] = UserAgreementSubmission::REJECTED;
          $event_name = UserSubmissionEvent::REJECTED;
          break;

        case 1:
          $user_agreement_submission_data['status'] = UserAgreementSubmission::ACCEPTED;
          $event_name = UserSubmissionEvent::ACCEPTED;
          break;
      }

      if ($existing_user_agreement_submission = $this->entityTypeManager->getStorage('user_agreement_submission')
        ->loadByProperties([
          'user' => $values['user_id'],
          'user_agreement' => $values['agreement_id'],
          'user_agreement_vid' => $values['agreement_vid'],
        ])) {
        $user_agreement_submission = array_shift($existing_user_agreement_submission);
        $user_agreement_submission->delete();
      }

      $user_agreement_submission = UserAgreementSubmission::create($user_agreement_submission_data);
      $user_agreement_submission->save();

      $event = new UserSubmissionEvent($user_agreement_submission, $user);
      $this->eventDispatcher->dispatch($event_name, $event);

    }

  }

}
