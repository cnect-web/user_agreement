<?php

namespace Drupal\user_agreement;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user_agreement\Entity\UserAgreementInterface;

/**
 * Defines the storage handler class for User agreement entities.
 *
 * This extends the base storage class, adding required special handling for
 * User agreement entities.
 *
 * @ingroup user_agreement
 */
class UserAgreementStorage extends SqlContentEntityStorage implements UserAgreementStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(UserAgreementInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {user_agreement_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {user_agreement_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(UserAgreementInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {user_agreement_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('user_agreement_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */

    if ($entity->isNew()) {
      // Ensure the entity is still seen as new after assigning it an id, while
      // storing its data.
      $entity->enforceIsNew();
      if ($this->entityType->isRevisionable()) {
        $entity->setNewRevision();
      }
      $return = SAVED_NEW;
    }
    else {
      // @todo Consider returning a different value when saving a non-default
      //   entity revision. See https://www.drupal.org/node/2509360.
      $return = $entity->isDefaultRevision() ? SAVED_UPDATED : FALSE;
    }

    $this->populateAffectedRevisionTranslations($entity);

    // Populate the "revision_default" flag. We skip this when we are resaving
    // the revision because this is only allowed for default revisions, and
    // these cannot be made non-default.
    $revision_default_key = $this->entityType->getRevisionMetadataKey('revision_default');
    $entity->set($revision_default_key, $entity->isDefaultRevision());

    $this->doSaveFieldItems($entity);

    return $return;
  }

}
