<?php

namespace Drupal\user_agreement\Event;

use Drupal\Core\Url;
use Drupal\user_agreement\Entity\UserAgreement;
use Symfony\Component\EventDispatcher\Event;

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
   * The form redirect URL.
   *
   * @var \Drupal\Core\Url
   */
  protected $redirectUrl;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user_agreement\Entity\UserAgreementSubmission $submission
   *   The user agreement submission entity.
   * @param \Drupal\user\UserInterface $account
   *   The account of the logged in user.
   */
  public function __construct(UserAgreement $user_agreement) {
    $this->user_agreement = $user_agreement;
  }

  /**
   * Sets the form redirect URL.
   *
   * @param \Drupal\Core\Url $redirect_url
   *   The form redirect URL.
   *
   * @return $this
   */
  public function setRedirectUrl(Url $redirect_url): self {
    $this->redirectUrl = $redirect_url;
    return $this;
  }

  /**
   * Returns the form redirect URL.
   *
   * @return \Drupal\Core\Url|null
   *   The form redirect URL.
   */
  public function getRedirectUrl(): ?Url {
    return $this->redirectUrl;
  }


}
