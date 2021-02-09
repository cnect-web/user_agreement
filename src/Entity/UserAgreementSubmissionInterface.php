<?php

namespace Drupal\user_agreement\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining User agreement submission entities.
 *
 * @ingroup user_agreement
 */
interface UserAgreementSubmissionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the User agreement submission name.
   *
   * @return string
   *   Name of the User agreement submission.
   */
  public function getName();

  /**
   * Sets the User agreement submission name.
   *
   * @param string $name
   *   The User agreement submission name.
   *
   * @return \Drupal\user_agreement\Entity\UserAgreementSubmissionInterface
   *   The called User agreement submission entity.
   */
  public function setName($name);

  /**
   * Gets the User agreement submission creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User agreement submission.
   */
  public function getCreatedTime();

  /**
   * Sets the User agreement submission creation timestamp.
   *
   * @param int $timestamp
   *   The User agreement submission creation timestamp.
   *
   * @return \Drupal\user_agreement\Entity\UserAgreementSubmissionInterface
   *   The called User agreement submission entity.
   */
  public function setCreatedTime($timestamp);

}
