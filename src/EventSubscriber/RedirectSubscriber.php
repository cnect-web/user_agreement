<?php

namespace Drupal\user_agreement\EventSubscriber;

use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class RedirectSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $privateTempstore;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $private_tempstore_factory, Messenger $messenger) {
    $this->privateTempstore = $private_tempstore_factory->get('user_agreement');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events[CasHelper::EVENT_PRE_USER_LOAD_REDIRECT] = ['preUserLoadRedirect'];
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
      $proxy = new AccountProxy;
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

        $user_agreements = _user_agreement_check_user_agreements($hash);

        // If there's items here, we know the user has not accepted it yet.
        if (!empty($user_agreements)) {
          $user_agreement = reset($user_agreements);
          // Redirect to user agreement page.
          $options['query'] = $event->getServiceParameters();

          $this->messenger
            ->addWarning($this->t('You must agree with %title to proceed.', [
              '%title' => $user_agreement->label(),
            ]));

          $url = Url::fromRoute('entity.user_agreement.canonical', ['user_agreement' => $user_agreement->id()], $options)
            ->toString(TRUE);
          $response = new TrustedRedirectResponse($url->getGeneratedUrl());
          $response->getCacheableMetadata()->addCacheContexts(['session']);
          $event->setRedirectResponse($response);
        }
      }

    }
  }

}
