<?php

namespace Drupal\user_agreement\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user_agreement\Entity\UserAgreement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;

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
    $this->privateTempStore->delete('handling_response');

    $values = $form_state->getValues();
    $user_agreement = $this
      ->entityTypeManager
      ->getStorage('user_agreement')
      ->load($values['agreement_id']);

    // If accepted, add the agreement info to the private tempstore for
    // processing later.
    if ($values['agree_box'] == 1) {
      $accepted = $this->privateTempStore->get('accepted');
      $accepted[$user_agreement->id()] = $user_agreement->getRevisionId();
      $this->privateTempStore->set('accepted', $accepted);
    }
    // Otherwise, reset state.
    else {
      $this->privateTempStore->delete('email_hash');
      $this->privateTempStore->delete('accepted');
      $this->privateTempStore->delete('ticket');
      $this->privateTempStore->delete('property_bag');
      $this->privateTempStore->delete('service_parameters');

      $this->messenger->addError($this->t("You have rejected the user agreement."));

      $this->casHelper->handleReturnToParameter($this->getRequest());
      $url = Url::fromRoute('<front>');
      $form_state->setRedirectUrl($url);
    }
  }

}
