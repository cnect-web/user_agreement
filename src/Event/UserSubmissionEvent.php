<?php

namespace Drupal\user_agreement\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\user_agreement\Entity\UserAgreementSubmission;

/**
 * Event that is fired when a user submits agrees/reject an user agreement.
 */
class UserSubmissionEvent extends Event {

  const ACCEPTED = 'user_agreement_user_submission_accepted_event';
  const REJECTED = 'user_agreement_user_submission_rejected_event';

  /**
   * The user agreement submission.
   *
   * @var \Drupal\user_agreement\Entity\UserAgreementSubmission
   */
  public $submission;

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user_agreement\Entity\UserAgreementSubmission $submission
   *   The user agreement submission entity.
   * @param \Drupal\user\UserInterface $account
   *   The account of the user logged in.
   */
  public function __construct(UserAgreementSubmission $submission, UserInterface $account) {
    $this->account = $account;
    $this->submission = $submission;
  }

}
