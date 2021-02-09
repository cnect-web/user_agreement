<?php

namespace Drupal\user_agreement\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining User agreement entities.
 *
 * @ingroup user_agreement
 */
interface UserAgreementInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the User agreement name.
   *
   * @return string
   *   Name of the User agreement.
   */
  public function getName();

  /**
   * Sets the User agreement name.
   *
   * @param string $name
   *   The User agreement name.
   *
   * @return \Drupal\user_agreement\Entity\UserAgreementInterface
   *   The called User agreement entity.
   */
  public function setName($name);

  /**
   * Gets the User agreement creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User agreement.
   */
  public function getCreatedTime();

  /**
   * Sets the User agreement creation timestamp.
   *
   * @param int $timestamp
   *   The User agreement creation timestamp.
   *
   * @return \Drupal\user_agreement\Entity\UserAgreementInterface
   *   The called User agreement entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the User agreement revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the User agreement revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\user_agreement\Entity\UserAgreementInterface
   *   The called User agreement entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the User agreement revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the User agreement revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\user_agreement\Entity\UserAgreementInterface
   *   The called User agreement entity.
   */
  public function setRevisionUserId($uid);

}
