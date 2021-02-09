<?php

/**
 * Contains \UserAgreement.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalDriverManager;
use Drupal\user_agreement\Entity\UserAgreementSubmission;

/**
 * Example subcontext.
 */
class UserAgreementContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Construct method.
   */
  public function __construct(DrupalDriverManager $drupal) {
  }

  /**
   * Accept the privacy statement with the current logged in user.
   *
   * @Given I accepted the user agreements
   */
  public function iAcceptedTheUserAgreements() {
    $current_user = $this->getUserManager()->getCurrentUser();
    if (!$current_user->uid) {
      $message = sprintf('Only logged in users can accept the privacy statement.');
      throw new \Exception($message);
    }
    else {
      $user_agreements = \Drupal::entityTypeManager()
        ->getStorage('user_agreement')
        ->loadByProperties(['status' => '1']);

      foreach ($user_agreements as $user_agreement) {
        $user_agreement_submission = UserAgreementSubmission::create([
          'name' => "User Agreement",
          'user_agreement' => ['target_id' => $user_agreement->id()],
          'user_agreement_vid' => $user_agreement->getDefaultRevisionId(),
          'user' => $current_user->uid,
          'status' => UserAgreementSubmission::ACCEPTED,
        ]);
        $user_agreement_submission->save();
      }
    }
  }

  /**
   * Reject the privacy statement with the current logged in user.
   *
   * @Given I rejected a user agreement
   */
  public function iRejectedaUserAgreement() {
    $current_user = $this->getUserManager()->getCurrentUser();
    if (!$current_user->uid) {
      $message = sprintf('Only logged in users can accept the privacy statement.');
      throw new \Exception($message);
    }
    else {
      $user_agreements = \Drupal::entityTypeManager()
        ->getStorage('user_agreement')
        ->loadByProperties(['status' => '1']);

      foreach ($user_agreements as $user_agreement) {
        $user_agreement_submission = UserAgreementSubmission::create([
          'name' => "User Agreement",
          'user_agreement' => ['target_id' => $user_agreement->id()],
          'user_agreement_vid' => $user_agreement->getDefaultRevisionId(),
          'user' => $current_user->uid,
          'status' => UserAgreementSubmission::REJECTED,
        ]);
        $user_agreement_submission->save();
      }

    }
  }

}
