<?php

namespace Drupal\user_agreement\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\PostponeItemException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes User agreement tasks.
 *
 * @QueueWorker(
 *   id = "user_agreement_queue",
 *   title = @Translation("User agreement queue"),
 *   cron = {"time" = 10}
 * )
 */
class UserAgreementQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UserAgreementQueue.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $delay = '+7 days';

    // Only items older than delay.
    if (time() - strtotime($delay, $data->created) >= 0) {

      // Check if everything is still relevant.
      $user_agreement = $this
        ->entityTypeManager
        ->getStorage('user_agreement')
        ->load($data->agreement_id);

      $account = $this
        ->entityTypeManager
        ->getStorage('user')
        ->load($data->agreement_uid);

      if (($user_agreement) && ($account)) {
        if (!_user_agreement_user_has_agreed($user_agreement, $account)) {
          $account->set('status', 0);
          $account->save();
        }
      }
    }
    // Put it back in the queue.
    else {
      throw new PostponeItemException("User {$data->agreement_uid} aggreements verification processed, action postponed.");
    }
  }

}
