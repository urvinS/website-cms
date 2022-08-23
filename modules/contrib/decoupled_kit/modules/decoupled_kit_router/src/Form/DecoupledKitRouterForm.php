<?php

namespace Drupal\decoupled_kit_router\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Decoupled_kit_router form.
 *
 * @property \Drupal\decoupled_kit_router\DecoupledKitRouterInterface $entity
 */
class DecoupledKitRouterForm extends EntityForm {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the decoupled_kit_router.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\decoupled_kit_router\Entity\DecoupledKitRouter::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    // Get content type entities types.
    $allEntities = $this->entityTypeManager->getDefinitions();
    $options = [];
    foreach ($allEntities as $entityName => $entityDefinition) {
      if ($entityDefinition instanceof ContentEntityType) {
        $options[$entityName] = (string) $entityDefinition->getLabel();
      }
    }
    $form['entity_type'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Entity type'),
      '#default_value' => $this->entity->get('entity_type'),
      '#description' => $this->t('Entity type of the Decoupled Kit Router.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateBundles',
        'method' => 'html',
        'wrapper' => 'bundle-to-update',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Get bundles...'),
        ],
      ],
    ];

    $options = [];
    if (!empty($this->entity->get('entity_type'))) {
      $options = $this->getBundles($this->entity->get('entity_type'));
    }
    $form['entity_bundle'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Entity bundle'),
      '#default_value' => $this->entity->get('entity_bundle'),
      '#description' => $this->t('Entity bundle of the Decoupled Kit Router.'),
      '#required' => TRUE,
      '#attributes' => ["id" => 'bundle-to-update'],
    ];

    $form['router_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#default_value' => $this->entity->get('router_path'),
      '#description' => $this->t('Path of the Decoupled Kit Router. Route must have UUID parameter.'),
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => !$this->entity->isNew() ? $this->entity->status() : TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $this->getValues($form_state);
    $storage = $this->entityTypeManager->getStorage('decoupled_kit_router');
    $limitResult = $this->entity->isNew() ? 0 : 1;

    // Check for router path exists.
    $result = $storage->loadByProperties(['router_path' => $values['router_path']]);
    if (count($result) > $limitResult) {
      $form_state->setErrorByName('router_path', $this->t('Router path must be unique.'));
    }

    // Check for entity type and entity bundle.
    $result = $storage->loadByProperties([
      'entity_type' => $values['entity_type'],
      'entity_bundle' => $values['entity_bundle'],
    ]);
    if (count($result) > $limitResult) {
      $form_state->setErrorByName('entity_type', $this->t('Entity type with entity bundle must be unique.'));
      $form_state->setErrorByName('entity_bundle');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    foreach ($this->getValues($form_state) as $key => $value) {
      $this->entity->set($key, $value);
    }

    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new decoupled_kit_router %label.', $message_args)
      : $this->t('Updated decoupled_kit_router %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * Get and convert form values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state values.
   *
   * @return array
   *   Array with converted values.
   */
  private function getValues(FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type');
    $entity_bundle = trim($form_state->getValue('entity_bundle'));

    $path = trim($form_state->getValue('router_path'));
    $path = sprintf('/%s', ltrim($path, '/'));

    return [
      'entity_type' => $entity_type,
      'entity_bundle' => $entity_bundle,
      'router_path' => $path,
    ];
  }

  /**
   * Update bundles.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state values.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Update bundles list.
   */
  public function updateBundles(array $form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    $value = $triggeringElement['#value'];
    $options = $this->getBundles($value);
    $renderedField = '';
    foreach ($options as $key => $value) {
      $renderedField .= sprintf('<option value="%s">%s</option>', $key, $value);
    }
    $wrapper_id = $triggeringElement["#ajax"]["wrapper"];
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#" . $wrapper_id, $renderedField));
    return $response;
  }

  /**
   * Get bundles.
   *
   * @param string $entity_type
   *   Entity type key.
   *
   * @return array
   *   Key-value bundles list.
   */
  private function getBundles($entity_type) {
    $res = [];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    foreach ($bundles as $key => $value) {
      $res[$key] = $value['label'];
    }
    return $res;
  }

}
