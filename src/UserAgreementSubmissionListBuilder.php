<?php

namespace Drupal\user_agreement;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of User agreement submission entities.
 *
 * @ingroup user_agreement
 */
class UserAgreementSubmissionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('User agreement submission ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\user_agreement\Entity\UserAgreementSubmission $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.user_agreement_submission.edit_form',
      ['user_agreement_submission' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
