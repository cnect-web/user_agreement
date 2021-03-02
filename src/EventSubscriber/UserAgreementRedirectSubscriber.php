<?php

namespace Drupal\user_agreement\EventSubscriber;

use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasUserManager;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\user_agreement\Event\UserSubmissionEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class UserAgreementRedirectSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
   * The private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $privateTempstore;

  /**
   * The CAS user manager service.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $userAgreementSettings;

  /**
   * The module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $casSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    Messenger $messenger,
    PrivateTempStoreFactory $private_tempstore_factory,
    CasUserManager $cas_user_manager,
    CasHelper $cas_helper,
    EventDispatcherInterface $event_dispatcher,
    ConfigFactory $config_factory) {

    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->privateTempstore = $private_tempstore_factory->get('user_agreement');
    $this->casUserManager = $cas_user_manager;
    $this->casHelper = $cas_helper;
    $this->eventDispatcher = $event_dispatcher;
    $this->userAgreementSettings = $config_factory->get('user_agreement.user_agreement_settings');
    $this->casSettings = $config_factory->get('cas.settings');
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events[CasHelper::EVENT_PRE_USER_LOAD_REDIRECT] = ['preUserLoadRedirect'];
    $events[KernelEvents::REQUEST][] = ['checkUserAgreementsStatus'];
    return $events;
  }

  /**
   * React to a user logging in using CAS.
   *
   * @param \Drupal\cas\Event\CasPreUserLoadEvent $event
   *   CAS pre user load event.
   */
  public function preUserLoadRedirect(CasPreUserLoadRedirectEvent $event) {
    if (($email = $event->getPropertyBag()->getAttribute('email'))) {
      // User can be exempt of agreeing, like administrators.
      $proxy = new \Drupal\Core\Session\AccountProxy;
      if ($account = user_load_by_mail($email)) {
        $proxy->setAccount($account);
      }
      // If the account doesn't exist or the user is exempt.
      if (!$account || !_user_agreement_user_is_exempt($proxy)) {
        $hash = Crypt::hashBase64($email);

        $this->privateTempstore->set('email_hash', $hash);
        $this->privateTempstore->set('ticket', $event->getTicket());
        $this->privateTempstore->set('property_bag', $event->getPropertyBag());
        $this->privateTempstore->set('service_parameters', $event->getServiceParameters());

        // @todo this won't work if user has accepted the first.
        $user_agreements = $this->entityTypeManager
          ->getStorage('user_agreement')
          ->loadByProperties(['status' => '1']);

        $user_agreement = reset($user_agreements);

        if (!_user_agreement_user_has_agreed($user_agreement, $hash)) {
          // Any redirect will do, it'll be picked up by the kernel subscriber.
          $options['query'] = $event->getServiceParameters();

          $url = Url::fromRoute('<front>', [], $options);
          $response = new RedirectResponse($url->toString());
          $event->setRedirectResponse($response);
        }

      }
    }
  }

  function checkUserAgreementsStatus(GetResponseEvent $event) {
    if ($hash = $this->privateTempstore->get('email_hash')) {
      if (!$this->privateTempstore->get('handling_response')) {
        $user_agreements = $this->entityTypeManager
          ->getStorage('user_agreement')
          ->loadByProperties(['status' => '1']);

        foreach ($user_agreements as $user_agreement) {
          if (!_user_agreement_user_has_agreed($user_agreement, $hash)) {
            // Redirect to user agreement page.
            $this->messenger
              ->addWarning($this->t('You must agree with %title to proceed.', [
                '%title' => $user_agreement->label(),
              ]));

            $options['query'] = $this->privateTempstore->get('service_parameters');

            $response = new RedirectResponse($user_agreement->toUrl('canonical', $options)
              ->toString());
            $event->setResponse($response);

            $this->privateTempstore->set('handling_response', TRUE);
            return;
          }
        }

        /** @var string $ticket */
        $ticket = $this->privateTempstore->get('ticket');
        /** @var \Drupal\cas\CasPropertyBag $property_bag */
        $property_bag = $this->privateTempstore->get('property_bag');
        /** @var array $service_parameters */
        $service_parameters = $this->privateTempstore->get('service_parameters');
        /** @var array $accepted_agreements */
        $accepted_agreements = $this->privateTempstore->get('accepted');

        $this->privateTempstore->delete('email_hash');
        $this->privateTempstore->delete('accepted');
        $this->privateTempstore->delete('ticket');
        $this->privateTempstore->delete('property_bag');
        $this->privateTempstore->delete('service_parameters');

        // Finish login.
        $this->casUserManager->login($property_bag, $ticket);

        foreach($accepted_agreements as $agreement_id => $revision_id) {
          $user_agreement = $user_agreements[$agreement_id];
          $newEvent = new UserSubmissionEvent($user_agreement);
          $this->eventDispatcher->dispatch(UserSubmissionEvent::ACCEPTED, $newEvent);
        }

        $message = $this->casSettings->get('login_success_message');
        $this->messenger->addMessage($this->t($message), 'status');

        // Final redirect.
        if(!$configured_url = $this->userAgreementSettings->get('redirect_url')) {
          $event->getRequest()->query->add($service_parameters);
          if (!$redirect_url = $newEvent->getRedirectUrl()) {
            $this->casHelper->handleReturnToParameter($event->getRequest());
            $redirect_url = Url::fromRoute('<front>');
          }
        }
        else {
          $redirect_url = Url::fromUserInput($configured_url, ['absolute' => TRUE]);
        }
        $response = new TrustedRedirectResponse($redirect_url->toString());
        $event->setResponse($response);
      }
    }
  }

}
