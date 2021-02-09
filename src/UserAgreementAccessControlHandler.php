<?php

namespace Drupal\user_agreement;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the User agreement entity.
 *
 * @see \Drupal\user_agreement\Entity\UserAgreement.
 */
class UserAgreementAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\user_agreement\Entity\UserAgreementInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished user agreement entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published user agreement entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit user agreement entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete user agreement entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add user agreement entities');
  }

}
