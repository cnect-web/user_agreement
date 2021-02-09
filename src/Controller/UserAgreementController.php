<?php

namespace Drupal\user_agreement\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user_agreement\Entity\UserAgreementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserAgreementController.
 *
 *  Returns responses for User agreement routes.
 */
class UserAgreementController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

  /**
   * Displays a User agreement revision.
   *
   * @param int $user_agreement_revision
   *   The User agreement revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($user_agreement_revision) {
    $user_agreement = $this->entityTypeManager()->getStorage('user_agreement')
      ->loadRevision($user_agreement_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('user_agreement');

    return $view_builder->view($user_agreement);
  }

  /**
   * Displays a User agreement revision edit form.
   *
   * @param int $user_agreement_revision
   *   The User agreement revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionEdit($user_agreement_revision) {
    $user_agreement = $this
      ->entityTypeManager()
      ->getStorage('user_agreement')
      ->loadRevision($user_agreement_revision);

    $form = $this
      ->entityTypeManager()
      ->getFormObject('user_agreement', 'edit_revision')
      ->setEntity($user_agreement);

    return $this->formBuilder->getForm($form);
  }

  /**
   * Access callback to the User agreement revision edit form.
   *
   * @param int $user_agreement_revision
   *   The User agreement revision ID.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An AccessResult instance.
   */
  public function revisionEditAccess($user_agreement_revision) {
    if ($user_agreement_revision) {
      $user_agreement = $this
        ->entityTypeManager()
        ->getStorage('user_agreement')
        ->loadRevision($user_agreement_revision);

      return ($user_agreement_revision == $user_agreement->getLastRevisionId())
        ? AccessResult::allowed()
        : AccessResult::forbidden();
    }
    return AccessResult::forbidden();
  }

  /**
   * Access callback to the User agreement revert/publish pages.
   *
   * @param int $user_agreement_revision
   *   The User agreement revision ID.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An AccessResult instance.
   */
  public function revisionPublishRevertAccess($user_agreement_revision) {
    if ($user_agreement_revision) {
      $user_agreement = $this
        ->entityTypeManager()
        ->getStorage('user_agreement')
        ->loadRevision($user_agreement_revision);

      return ($user_agreement_revision == $user_agreement->getDefaultRevisionId())
        ? AccessResult::forbidden()
        : AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Page title callback for a User agreement revision.
   *
   * @param int $user_agreement_revision
   *   The User agreement revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionEditPageTitle($user_agreement_revision) {
    $user_agreement = $this->entityTypeManager()->getStorage('user_agreement')
      ->loadRevision($user_agreement_revision);
    return $this->t('%title (Rev. %revision)', [
      '%title' => $user_agreement->label(),
      '%revision' => $user_agreement_revision,
    ]);
  }

  /**
   * Page title callback for a User agreement revision.
   *
   * @param int $user_agreement_revision
   *   The User agreement revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($user_agreement_revision) {
    $user_agreement = $this->entityTypeManager()->getStorage('user_agreement')
      ->loadRevision($user_agreement_revision);
    return $this->t('%title (Rev. %revision)', [
      '%title' => $user_agreement->label(),
      '%revision' => $user_agreement_revision,
    ]);
  }

  /**
   * Page title callback for user agreement revision submissions page.
   *
   * @param int $user_agreement_revision
   *   The User agreement revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionSubmissionsPageTitle($user_agreement_revision) {
    $user_agreement = $this->entityTypeManager()->getStorage('user_agreement')
      ->loadRevision($user_agreement_revision);
    return $this->t('%title (Rev. %revision) - Submissions', [
      '%title' => $user_agreement->label(),
      '%revision' => $user_agreement_revision,
    ]);
  }

  /**
   * Page title callback for user agreement revision submissions page.
   *
   * @param int $user_agreement
   *   The User agreement ID.
   *
   * @return string
   *   The page title.
   */
  public function currentRevisionSubmissionsPageTitle($user_agreement) {
    $user_agreement = $this->entityTypeManager()->getStorage('user_agreement')
      ->load($user_agreement);

    return $this->t('%title (Rev. %revision) - Submissions', [
      '%title' => $user_agreement->label(),
      '%revision' => $user_agreement->getDefaultRevisionId(),
    ]);
  }

  /**
   * List of user agreement submissions for current published.
   *
   * @param int $user_agreement
   *   A User agreement id.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function currentRevisionSubmissions($user_agreement) {
    $user_agreement = $this
      ->entityTypeManager()
      ->getStorage('user_agreement')
      ->load($user_agreement);

    $build['submissions']['view'] = [
      '#type' => 'view',
      '#name' => 'user_agreement_submissions',
      '#display_id' => 'block_1',
      '#arguments' => [
        $user_agreement->id(),
        $user_agreement->getDefaultRevisionId(),
      ],
    ];
    return $build;
  }

  /**
   * List of user agreement submissions per revision.
   *
   * @param int $user_agreement
   *   A User agreement id.
   * @param int $user_agreement_revision
   *   A User agreement revision id.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionSubmissions($user_agreement, $user_agreement_revision) {
    $build['submissions']['view'] = [
      '#type' => 'view',
      '#name' => 'user_agreement_submissions',
      '#display_id' => 'block_1',
      '#arguments' => [
        $user_agreement,
        $user_agreement_revision,
      ],
    ];
    return $build;
  }

  /**
   * Generates an overview table of older revisions of a User agreement.
   *
   * @param \Drupal\user_agreement\Entity\UserAgreementInterface $user_agreement
   *   A User agreement object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(UserAgreementInterface $user_agreement) {
    $account = $this->currentUser();
    $user_agreement_storage = $this->entityTypeManager()->getStorage('user_agreement');

    $langcode = $user_agreement->language()->getId();
    $langname = $user_agreement->language()->getName();
    $languages = $user_agreement->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $user_agreement->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $user_agreement->label()]);

    $header = [
      $this->t('Revision'),
      '',
      $this->t('Status'),
      $this->t('Operations'),
    ];
    $revert_permission = (
      $account->hasPermission('revert all user agreement revisions')
      ||
      $account->hasPermission('administer user agreement entities')
    );
    $delete_permission = (
      $account->hasPermission('delete all user agreement revisions')
      ||
      $account->hasPermission('administer user agreement entities')
    );

    $rows = [];

    $vids = $user_agreement_storage->revisionIds($user_agreement);
    $default_revision_vid = $user_agreement->getDefaultRevisionId();
    $has_forward_revisions = $user_agreement->hasForwardRevisions();

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\user_agreement\UserAgreementInterface $revision */
      $revision = $user_agreement_storage->loadRevision($vid);

      $links = [];

      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        if ($vid != $user_agreement->getRevisionId()) {
          $link = Link::fromTextAndUrl($revision->label() . ' (Rev. ' . $vid . ')', new Url('entity.user_agreement.revision', [
            'user_agreement' => $user_agreement->id(),
            'user_agreement_revision' => $vid,
          ]));
        }
        else {
          $link = Link::fromTextAndUrl($revision->label() . ' (Rev. ' . $vid . ')', new Url('entity.user_agreement.canonical', [
            'user_agreement' => $user_agreement->id(),
          ]));
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ link }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'link' => $link->toString(),
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($submissions_count = $user_agreement->getSubmissionsCount($vid)) {
          $link = Link::fromTextAndUrl('submissions', $this->getRevisionSubmissionsLink($user_agreement, $vid)['url'])->toString();
          $column = [
            'data' => [
              '#type' => 'inline_template',
              '#template' => '{% trans %} {{ submissions_link }} {% endtrans %} ({{ submissions_count }})',
              '#context' => [
                'submissions_link' => $link,
                'submissions_count' => $submissions_count,
              ],
            ],
          ];
          $row[] = $column;
        }
        else {
          $row[] = [];
        }

        if ($revision->isDefaultRevision()) {
          $properties = [$revision->isPublished() ? $this->t('Published') : $this->t('Unpublished')];
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => implode(', ', $properties),
              '#suffix' => '</em>',
            ],
          ];

          if (!$has_forward_revisions || $user_agreement->getDefaultRevisionId() === FALSE) {
            if ($vid > $user_agreement->getDefaultRevisionId() || !$revision->isPublished()) {
              $links['publish'] = $this->getPublishLink($user_agreement, $vid, $has_translations, $langcode);
            }
            $links['edit_current'] = $this->getEditRevisionLink($user_agreement, $vid, $has_translations, $langcode);
            $links['edit_current']['title'] = $this->t('%status', ['%status' => $revision->isPublished() ? 'New Draft' : 'Edit']);
          }

          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }
        else {

          if ($revert_permission) {
            $properties = [$this->t('Unpublished')];

            $row[] = [
              'data' => [
                '#prefix' => '<em>',
                '#markup' => implode(', ', $properties),
                '#suffix' => '</em>',
              ],
            ];

            if ($vid > $default_revision_vid) {
              if ($vid == $user_agreement->getLastRevisionId()) {
                $links['edit'] = $this->getEditRevisionLink($user_agreement, $vid, $has_translations, $langcode);
              }
              $links['publish'] = $this->getPublishLink($user_agreement, $vid, $has_translations, $langcode);
            }
            else {
              $links['revert'] = $this->getRevertLink($user_agreement, $vid, $has_translations, $langcode);
            }
          }

          else {
            $links = [];
          }

          if ($delete_permission) {
            $links['delete'] = $this->getDeleteLink($user_agreement, $vid);
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = [
          'data' => $row,
          'class' => $this->cssClasses($properties),
        ];
      }
    }

    $build['user_agreement_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Helper.
   *
   * Returns an array with a edit revision link.
   */
  private function getEditRevisionLink(UserAgreementInterface $user_agreement, $vid, $has_translations = FALSE, $langcode = NULL) {
    return $this->getLinkArray($user_agreement, $vid, 'Edit revision', 'revision_edit', $has_translations, $langcode);
  }

  /**
   * Helper.
   *
   * Returns an array with a link to the submissions page.
   */
  private function getRevisionSubmissionsLink(UserAgreementInterface $user_agreement, $vid, $has_translations = FALSE, $langcode = NULL) {
    return $this->getLinkArray($user_agreement, $vid, 'Revision submissions', 'revision_submissions', $has_translations, $langcode);
  }

  /**
   * Helper.
   *
   * Returns an array with a publish revision link.
   */
  private function getPublishLink(UserAgreementInterface $user_agreement, $vid, $has_translations = FALSE, $langcode = NULL) {
    return $this->getLinkArray($user_agreement, $vid, 'Publish', 'revision_publish', $has_translations, $langcode);
  }

  /**
   * Helper.
   *
   * Returns an array with a revert revision link.
   */
  private function getRevertLink(UserAgreementInterface $user_agreement, $vid, $has_translations = FALSE, $langcode = NULL) {
    return $this->getLinkArray($user_agreement, $vid, 'Revert', 'revision_revert', $has_translations, $langcode);
  }

  /**
   * Helper.
   *
   * Returns an array with a delete revision link.
   */
  private function getDeleteLink(UserAgreementInterface $user_agreement, $vid) {
    return $this->getLinkArray($user_agreement, $vid, 'Delete', 'revision_delete', FALSE);
  }

  /**
   * Converts an array of strings to css-ready array of strings.
   *
   * @param array $properties
   *   An array of strings to use as row classes.
   *
   * @return array
   *   An array of strings cleaned and ready to be used as css classes.
   */
  private function cssClasses(array $properties) {
    return array_map('Drupal\Component\Utility\Html::cleanCssIdentifier',
      array_map('strtolower', $properties)
    );
  }

  /**
   * Get link array.
   *
   * @param Drupal\user_agreement\Entity\UserAgreementInterface $user_agreement
   *   User agreement object.
   * @param int $vid
   *   Revision id.
   * @param string $title
   *   Title.
   * @param string $action
   *   Action.
   * @param bool $has_translations
   *   Has translations.
   * @param string $langcode
   *   Language code.
   *
   * @return array
   *   Link array.
   */
  private function getLinkArray(UserAgreementInterface $user_agreement, $vid, $title, $action, bool $has_translations = FALSE, $langcode = NULL): array {
    $options = [
      'user_agreement' => $user_agreement->id(),
      'user_agreement_revision' => $vid,
    ];

    if ($has_translations) {
      if ($action == 'revision_revert') {
        $action = 'revision_revert_translation_confirm';
      }
      $options['langcode'] = $langcode;
    }

    return [
      'title' => $this->t(':title', [':title' => $title])->render(),
      'url' => Url::fromRoute("entity.user_agreement.$action", $options),
    ];
  }

}
