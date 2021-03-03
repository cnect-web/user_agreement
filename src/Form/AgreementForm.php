<?php

namespace Drupal\user_agreement\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\user_agreement\Entity\UserAgreement;
use Drupal\user_agreement\Event\UserSubmissionEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AgreementForm.
 *
 * Provides agreement form.
 */
class AgreementForm extends FormBase {

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
   * The private temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The CAS helper service.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

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
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $tempstore = $container->get('tempstore.private');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->privateTempStore = $tempstore->get('user_agreement');
    $instance->messenger = $container->get('messenger');
    $instance->casHelper = $container->get('cas.helper');
    $instance->casUserManager = $container->get('cas.user_manager');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    $config_factory = $container->get('config.factory');
    $instance->userAgreementSettings = $config_factory->get('user_agreement.user_agreement_settings');
    $instance->casSettings = $config_factory->get('cas.settings');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'agreement_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $agreement_entity = $this->routeMatch->getParameter('user_agreement');

    // On revision pages $agreement entity is an int.
    if (!$agreement_entity instanceof UserAgreement) {
      $agreement_entity = $this->entityTypeManager
        ->getStorage('user_agreement')
        ->load($agreement_entity);
    }

    $form['agreement_id'] = [
      '#type' => 'hidden',
      '#value' => $agreement_entity->id(),
    ];

    $form['agreement_vid'] = [
      '#type' => 'hidden',
      '#value' => $agreement_entity->getRevisionId(),
    ];

    if ($more_info = $agreement_entity->getMoreInfo()) {
      $form['more_info'] = [
        '#title' => $this->t('More information'),
        '#type' => 'fieldset',
        '#states' => [
          'visible' => [
            ':input[name="agree_box"]' => [
              'checked' => FALSE,
            ],
          ],
        ],
      ];

      $form['more_info']['text'] = [
        '#type' => '#markup',
        '#markup' => $more_info,
      ];
    }

    $form['agree_box_title'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('By pressing "I accept", you give your consent regarding the :title.', [
        ':title' => $agreement_entity->label(),
      ]),
    ];

    $form['agree_box'] = [
      '#title' => $this->t('I accept'),
      '#type' => 'checkbox',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'agree',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['agree_box'] == 0) {
      $this->messenger->addError($this->t("Login cancelled: you have rejected the user agreement."));

      $this->privateTempStore->delete('email_hash');
      $this->privateTempStore->delete('accepted');
      $this->privateTempStore->delete('ticket');
      $this->privateTempStore->delete('property_bag');
      $this->privateTempStore->delete('service_parameters');

      // Redirect to the page where the login was invoked.
      $this->casHelper->handleReturnToParameter($this->getRequest());
      $url = Url::fromRoute('<front>');
      $form_state->setRedirectUrl($url);
      return;
    }
    else {
      $accepted = $this->privateTempStore->get('accepted');
      $accepted[$values['agreement_id']] = $values['agreement_vid'];
      $this->privateTempStore->set('accepted', $accepted);
    }

    $user_agreements = _user_agreement_check_user_agreements($this->privateTempStore->get('email_hash'));

    if (!empty($user_agreements)) {
      $user_agreement = reset($user_agreements);

      $this->messenger
        ->addWarning($this->t('You must agree with %title to proceed.', [
          '%title' => $user_agreement->label(),
        ]));
      $options['query'] = $this->privateTempStore->get('service_parameters');
      $url = Url::fromRoute('entity.user_agreement.canonical', ['user_agreement' => $user_agreement->id()], $options)->toString(TRUE);
    }
    else {
      $url = $this->finishLogin();
    }

    $response = new TrustedRedirectResponse($url->getGeneratedUrl());
    $response->getCacheableMetadata()->addCacheContexts(['session']);
    $form_state->setResponse($response);
  }

  public function finishLogin() {

    /** @var string $ticket */
    $ticket = $this->privateTempStore->get('ticket');
    /** @var \Drupal\cas\CasPropertyBag $property_bag */
    $property_bag = $this->privateTempStore->get('property_bag');
    /** @var array $service_parameters */
    $service_parameters = $this->privateTempStore->get('service_parameters');
    /** @var array $accepted_agreements */
    $accepted_agreements = $this->privateTempStore->get('accepted');

    $this->privateTempStore->delete('email_hash');
    $this->privateTempStore->delete('accepted');
    $this->privateTempStore->delete('ticket');
    $this->privateTempStore->delete('property_bag');
    $this->privateTempStore->delete('service_parameters');

    // Finish login.
    $this->casUserManager->login($property_bag, $ticket);

    foreach($accepted_agreements as $agreement_id => $revision_id) {
      $user_agreement = UserAgreement::load($agreement_id);
      $newEvent = new UserSubmissionEvent($user_agreement);
      $this->eventDispatcher->dispatch(UserSubmissionEvent::ACCEPTED, $newEvent);
    }

    $message = $this->casSettings->get('login_success_message');
    $this->messenger->addMessage($this->t($message), 'status');

    // Final redirect.
    if($configured_url = $this->userAgreementSettings->get('redirect_url')) {
      $redirect_url = Url::fromUserInput($configured_url, ['absolute' => TRUE]);
    }
    else {
      $this->getRequest()->query->add($service_parameters);
      $this->casHelper->handleReturnToParameter($this->getRequest());
      $redirect_url = Url::fromRoute('<front>');
    }

    $url = $redirect_url->toString(TRUE);
    return $url;
  }

}
