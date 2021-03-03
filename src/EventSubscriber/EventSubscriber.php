<?php

namespace Drupal\user_agreement\EventSubscriber;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user_agreement\Entity\UserAgreementSubmission;
use Drupal\user_agreement\Event\UserSubmissionEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventSubscriber.
 *
 * Provides event subscriber for agree, decline events.
 */
class EventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new EventSubscriber object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, Messenger $messenger, AccountProxy $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[UserSubmissionEvent::ACCEPTED] = ['userAccepted'];
    $events[UserSubmissionEvent::REJECTED] = ['userRejected'];

    return $events;
  }

  /**
   * This method is called when the UserAccepted is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function userAccepted(Event $event) {
    $user_agreement = $event->user_agreement;
    $account = $this->currentUser->getAccount();

    $name = $this->t('Agreement | User :uid - :agreement (R. :agreement_vid)', [
      ':uid' => $account->id(),
      ':agreement' => $user_agreement->id(),
      ':agreement_vid' => $user_agreement->getRevisionId(),
    ])->render();

    $user_agreement_submission_data = [
      'name' => $name,
      'user_agreement' => ['target_id' => $user_agreement->id()],
      'user_agreement_vid' => $user_agreement->getRevisionId(),
      'user' => $account->id(),
      'status' => UserAgreementSubmission::ACCEPTED,
      'email_hash' => Crypt::hashBase64($account->getEmail()),
    ];

    $user_agreement_submission = UserAgreementSubmission::create($user_agreement_submission_data);
    $user_agreement_submission->save();

    $this->messenger->addMessage($this->t('You have agreed with %label.', ['%label' => $user_agreement->label()]), 'status');
  }

  /**
   * This method is called when the UserRejected is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function userRejected(Event $event) {
    $user_agreement = $event->user_agreement;

    $this->messenger->addMessage($this->t('You have not agreed with %label.', [
      '%label' => $user_agreement->label()
    ]), 'error');
  }

}
