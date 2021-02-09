<?php

namespace Drupal\user_agreement\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the User agreement entity.
 *
 * @ingroup user_agreement
 *
 * @ContentEntityType(
 *   id = "user_agreement",
 *   label = @Translation("User agreement"),
 *   handlers = {
 *     "storage" = "Drupal\user_agreement\UserAgreementStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\user_agreement\UserAgreementListBuilder",
 *     "views_data" = "Drupal\user_agreement\Entity\UserAgreementViewsData",
 *     "translation" = "Drupal\user_agreement\UserAgreementTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\user_agreement\Form\UserAgreementForm",
 *       "add" = "Drupal\user_agreement\Form\UserAgreementForm",
 *       "edit" = "Drupal\user_agreement\Form\UserAgreementForm",
 *       "edit_revision" = "Drupal\user_agreement\Form\UserAgreementRevisionEditForm",
 *       "delete" = "Drupal\user_agreement\Form\UserAgreementDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\user_agreement\UserAgreementHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\user_agreement\UserAgreementAccessControlHandler",
 *   },
 *   base_table = "user_agreement",
 *   data_table = "user_agreement_field_data",
 *   revision_table = "user_agreement_revision",
 *   revision_data_table = "user_agreement_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer user agreement entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/user-agreements/{user_agreement}",
 *     "add-form" = "/admin/people/user-agreements/add",
 *     "edit-form" = "/admin/structure/user_agreement/{user_agreement}/edit",
 *     "delete-form" = "/admin/structure/user_agreement/{user_agreement}/delete",
 *     "version-history" = "/admin/structure/user_agreement/{user_agreement}/revisions",
 *     "revision" = "/admin/structure/user_agreement/{user_agreement}/revisions/{user_agreement_revision}",
 *     "current_revision_submissions" = "/user-agreements/{user_agreement}/submissions",
 *     "revision_submissions" = "/admin/structure/user_agreement/{user_agreement}/revisions/{user_agreement_revision}/submissions",
 *     "revision_edit" = "/admin/structure/user_agreement/{user_agreement}/revisions/{user_agreement_revision}/edit",
 *     "revision_publish" = "/admin/structure/user_agreement/{user_agreement}/revisions/{user_agreement_revision}/publish",
 *     "revision_revert" = "/admin/structure/user_agreement/{user_agreement}/revisions/{user_agreement_revision}/revert",
 *     "revision_set_active" = "/admin/structure/user_agreement/{user_agreement}/revisions/{user_agreement_revision}/set_active",*
 *     "revision_delete" = "/admin/structure/user_agreement/{user_agreement}/revisions/{user_agreement_revision}/delete",
 *     "translation_revert" = "/admin/structure/user_agreement/{user_agreement}/revisions/{user_agreement_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/user_agreement",
 *   },
 *   field_ui_base_route = "user_agreement.settings"
 * )
 */
class UserAgreement extends EditorialContentEntityBase implements UserAgreementInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    $routes_to_add_revision_param = [
      "revision",
      "revision_edit",
      "revision_submissions",
      "revision_publish",
      "revision_revert",
      "revision_delete",
    ];

    if (in_array($rel, $routes_to_add_revision_param) && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the user_agreement owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('title', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the User agreement entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the User agreement entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content'))
      ->setDescription(t('Terms and conditions text.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -3,
        'settings' => [
          'rows' => 10,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['more_info'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('More info'))
      ->setDescription(t('Relevant information.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -2,
        'settings' => [
          'rows' => 8,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(TRUE);

    $fields['status']->setDescription(t('Check this box to publish this user agreement.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -1,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    if (\Drupal::moduleHandler()->moduleExists('path')) {
      $fields['path'] = BaseFieldDefinition::create('path')
        ->setLabel(t('URL alias'))
        ->setTranslatable(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'path',
          'weight' => 30,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setComputed(TRUE);
    }

    return $fields;
  }

  /**
   * Returns true if an entity has newer revisions than the current active one.
   *
   * @return bool
   *   True if an entity has newer revisions than the current active one.
   */
  public function hasForwardRevisions() {
    $user_agreement_storage = $this->entityTypeManager()->getStorage('user_agreement');

    $vid = $this->getDefaultRevisionId();
    $vids = $user_agreement_storage->revisionIds($this);

    $forward_revision_vids = array_filter($vids, function ($x) use ($vid) {
      return $x > $vid;
    });
    $forward_revision_count = count($forward_revision_vids);
    return $forward_revision_count > 0;
  }

  /**
   * Returns current default revision id.
   *
   * @return int
   *   The current default revision id.
   */
  public function getDefaultRevisionId() {
    return \Drupal::database()->select('user_agreement_revision', 'uar')
      ->fields('uar', ['vid'])
      ->condition('id', $this->id())
      ->condition('revision_default', 1)
      ->range(0, 1)
      ->orderBy('vid', 'DESC')
      ->execute()
      ->fetchField(0);
  }

  /**
   * Returns current default revision id.
   *
   * @return int
   *   Get latest default revision id.
   */
  public function getLastRevisionId() {
    return \Drupal::database()->select('user_agreement_revision', 'uar')
      ->fields('uar', ['vid'])
      ->condition('id', $this->id())
      ->range(0, 1)
      ->orderBy('vid', 'DESC')
      ->execute()
      ->fetchField(0);
  }

  /**
   * Counts user_agreement_submissions for a revision id.
   *
   * @return int
   *   Count of user submissions.
   */
  public function getSubmissionsCount($vid) {
    $query = \Drupal::entityQuery('user_agreement_submission')
      ->condition('user_agreement_vid', $vid)
      ->condition('user_agreement.entity.id', $this->id());

    $user_agreement_submission = $query->execute();
    return count($user_agreement_submission);
  }

  /**
   * Returns additional information string.
   *
   * @return string
   *   Additional information string.
   */
  public function getMoreInfo() {
    $value = $this->get('more_info')->value;
    $format = $this->get('more_info')->format;
    if ($value && $format) {
      $value = check_markup($value, $format);
    }

    return $value;
  }

}
