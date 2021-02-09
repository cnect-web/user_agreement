<?php

namespace Drupal\user_agreement;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\user_agreement\Entity\UserAgreementInterface;

/**
 * Defines the storage handler class for User agreement entities.
 *
 * This extends the base storage class, adding required special handling for
 * User agreement entities.
 *
 * @ingroup user_agreement
 */
interface UserAgreementStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of User agreement revision IDs for a specific User agreement.
   *
   * @param \Drupal\user_agreement\Entity\UserAgreementInterface $entity
   *   The User agreement entity.
   *
   * @return int[]
   *   User agreement revision IDs (in ascending order).
   */
  public function revisionIds(UserAgreementInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as User agreement author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   User agreement revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\user_agreement\Entity\UserAgreementInterface $entity
   *   The User agreement entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(UserAgreementInterface $entity);

  /**
   * Unsets the language for all User agreement with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
