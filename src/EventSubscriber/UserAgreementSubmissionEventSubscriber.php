<?php

namespace Drupal\user_agreement\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserDataInterface;
use Drupal\user_agreement\Event\UserSubmissionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class UserAgreementSubmissionEventSubscriber.
 *
 * Provides event subscriber for agree, decline events.
 */
class UserAgreementSubmissionEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * User data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a new UserAgreementSubmissionEventSubscriber object.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManager $entity_type_manager, Messenger $messenger, UserDataInterface $user_data, QueueFactory $queue_factory) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->userData = $user_data;
    $this->queueFactory = $queue_factory;
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
    $submission = $event->submission;
    $user_agreement = $submission->get('user_agreement')->entity;

    $this->messenger->addMessage($this->t('You have agreed with %label.', ['%label' => $user_agreement->label()]), 'status');
  }

  /**
   * This method is called when the UserRejected is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function userRejected(Event $event) {
    $submission = $event->submission;
    $user_agreement = $submission->get('user_agreement')->entity;

    $rejected_user_agreements = (array) $this->userData->get('user_agreement', $this->currentUser->id(), 'rejected_user_agreements');
    $rejected_user_agreements[$user_agreement->id()] = $user_agreement->getDefaultRevisionId();
    $this->userData->set('user_agreement', $this->currentUser->id(), 'rejected_user_agreements', $rejected_user_agreements);

    $this->messenger->addMessage($this->t('You have not agreed with %label.', ['%label' => $user_agreement->label()]), 'error');

    /** @var QueueInterface $queue */
    $queue = $this->queueFactory->get('user_agreement_queue');

    $item = new \stdClass();
    $item->agreement_id = $user_agreement->id();
    $item->agreement_vid = $user_agreement->getDefaultRevisionId();
    $item->agreement_uid = $this->currentUser->id();
    $item->created = time();

    $queue->createItem($item);
  }

}
