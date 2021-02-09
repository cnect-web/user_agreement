<?php

namespace Drupal\user_agreement;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for User agreement entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class UserAgreementHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($history_route = $this->getHistoryRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.version_history", $history_route);
    }

    if ($revision_route = $this->getRevisionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision", $revision_route);
    }

    if ($revision_edit_route = $this->getRevisionEditRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_edit", $revision_edit_route);
    }

    if ($curremt_revision_submissions_route = $this->getCurrentRevisionSubmissionsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.current_revision_submissions", $curremt_revision_submissions_route);
    }

    if ($submissions_route = $this->getRevisionSubmissionsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_submissions", $submissions_route);
    }

    if ($publish_route = $this->getRevisionPublishRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_publish", $publish_route);
    }

    if ($revert_route = $this->getRevisionRevertRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_revert", $revert_route);
    }

    if ($set_active_route = $this->getRevisionSetActiveRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_set_active", $set_active_route);
    }

    if ($delete_route = $this->getRevisionDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_delete", $delete_route);
    }

    if ($translation_route = $this->getRevisionTranslationRevertRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_revert_translation_confirm", $translation_route);
    }

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }

    return $collection;
  }

  /**
   * Gets the version history route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getHistoryRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('version-history')) {
      $route = new Route($entity_type->getLinkTemplate('version-history'));
      $route
        ->setDefaults([
          '_title' => "{$entity_type->getLabel()} revisions",
          '_controller' => '\Drupal\user_agreement\Controller\UserAgreementController::revisionOverview',
        ])
        ->setRequirement('_permission', 'view all user agreement revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision')) {
      $route = new Route($entity_type->getLinkTemplate('revision'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\user_agreement\Controller\UserAgreementController::revisionShow',
          '_title_callback' => '\Drupal\user_agreement\Controller\UserAgreementController::revisionPageTitle',
        ])
        ->setRequirement('_permission', 'view all user agreement revisions')
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

  /**
   * Gets the revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCurrentRevisionSubmissionsRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('current_revision_submissions')) {
      $route = new Route($entity_type->getLinkTemplate('current_revision_submissions'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\user_agreement\Controller\UserAgreementController::currentRevisionSubmissions',
          '_title_callback' => '\Drupal\user_agreement\Controller\UserAgreementController::currentRevisionSubmissionsPageTitle',
        ])
        ->setRequirement('_permission', 'view all user agreement revisions')
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

  /**
   * Gets the revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionSubmissionsRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision_submissions')) {
      $route = new Route($entity_type->getLinkTemplate('revision_submissions'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\user_agreement\Controller\UserAgreementController::revisionSubmissions',
          '_title_callback' => '\Drupal\user_agreement\Controller\UserAgreementController::revisionSubmissionsPageTitle',
        ])
        ->setRequirement('_permission', 'view all user agreement revisions')
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

  /**
   * Gets the revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionEditRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision_edit')) {
      $route = new Route($entity_type->getLinkTemplate('revision_edit'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\user_agreement\Controller\UserAgreementController::revisionEdit',
          '_title_callback' => '\Drupal\user_agreement\Controller\UserAgreementController::revisionEditPageTitle',
        ])
        ->setRequirement('_permission', 'view all user agreement revisions')
        ->setRequirement('_custom_access', '\Drupal\user_agreement\Controller\UserAgreementController::revisionEditAccess')
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

  /**
   * Gets the revision revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionPublishRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision_publish')) {
      $route = new Route($entity_type->getLinkTemplate('revision_publish'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\user_agreement\Form\UserAgreementRevisionPublishForm',
          '_title' => 'Publish revision',
        ])
        ->setRequirement('_permission', 'revert all user agreement revisions')
        ->setRequirement('_custom_access', '\Drupal\user_agreement\Controller\UserAgreementController::revisionPublishRevertAccess')
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

  /**
   * Gets the revision revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRevertRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision_revert')) {
      $route = new Route($entity_type->getLinkTemplate('revision_revert'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\user_agreement\Form\UserAgreementRevisionRevertForm',
          '_title' => 'Revert to earlier revision',
        ])
        ->setRequirement('_permission', 'revert all user agreement revisions')
        ->setRequirement('_custom_access', '\Drupal\user_agreement\Controller\UserAgreementController::revisionPublishRevertAccess')
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

  /**
   * Gets the revision set active route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionSetActiveRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision_set_active')) {
      $route = new Route($entity_type->getLinkTemplate('revision_set_active'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\user_agreement\Form\UserAgreementRevisionSetActiveForm',
          '_title' => 'Set as active revision',
        ])
        ->setRequirement('_permission', 'revert all user agreement revisions')
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

  /**
   * Gets the revision delete route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionDeleteRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision_delete')) {
      $route = new Route($entity_type->getLinkTemplate('revision_delete'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\user_agreement\Form\UserAgreementRevisionDeleteForm',
          '_title' => 'Delete revision',
        ])
        ->setRequirement('_permission', 'delete all user agreement revisions')
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

  /**
   * Gets the revision translation revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionTranslationRevertRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('translation_revert')) {
      $route = new Route($entity_type->getLinkTemplate('translation_revert'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\user_agreement\Form\UserAgreementRevisionRevertTranslationForm',
          '_title' => 'Revert to earlier revision of a translation',
        ])
        ->setRequirement('_permission', 'revert all user agreement revisions')
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->getBundleEntityType()) {
      $route = new Route("/admin/structure/{$entity_type->id()}/settings");
      $route
        ->setDefaults([
          '_form' => 'Drupal\user_agreement\Form\UserAgreementSettingsForm',
          '_title' => "{$entity_type->getLabel()} settings",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', FALSE);

      return $route;
    }
  }

}
