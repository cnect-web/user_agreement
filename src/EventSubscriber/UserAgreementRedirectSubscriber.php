<?php

namespace Drupal\user_agreement\EventSubscriber;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class UserAgreementRedirectSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    AccountProxy $current_user,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    Messenger $messenger,
    CurrentPathStack $current_path) {
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->currentPath = $current_path;
  }

  /**
   * Check if a user has submited all required agreements.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   */
  public function checkUserAgreementStatus(GetResponseEvent $event) {
    $account = $this
      ->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id());

    $route = $this
      ->routeMatch
      ->getRouteName();

    $allowed_routes = [
      'user.logout',
      'entity.user_agreement.canonical',
    ];

    if (!$account->isAnonymous() && !in_array($route, $allowed_routes)) {
      if (!_user_agreement_user_is_exempt($account)) {
        $user_agreements = $this->entityTypeManager
          ->getStorage('user_agreement')
          ->loadByProperties(['status' => '1']);

        foreach ($user_agreements as $user_agreement) {
          if (!_user_agreement_user_has_agreed($user_agreement, $account)) {
            // Redirect to user agreement page.
            $this->messenger
              ->addWarning($this->t('You must agree with %title to proceed.', [
                '%title' => $user_agreement->label(),
              ]));
            // Add current location as destination.
            $current = $this->currentPath->getPath();
            $options = [
              'query' => [
                'destination' => $current,
              ],
            ];
            $response = new RedirectResponse($user_agreement->toUrl('canonical', $options)->toString());
            $response->sendHeaders();
            exit();
          }
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkUserAgreementStatus'];
    return $events;
  }

}
