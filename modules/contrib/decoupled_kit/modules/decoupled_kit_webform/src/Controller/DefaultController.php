<?php

namespace Drupal\decoupled_kit_webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Drupal\Core\Path\AliasManagerInterface definition.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pathAliasManager = $container->get('path.alias_manager');
    $instance->pathValidator = $container->get('path.validator');
    return $instance;
  }

  /**
   * Get webform confirmation data.
   *
   * @param int $id
   *   Webform submission id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return webform submission settings.
   */
  public function index($id) {
    $data = [];

    $webform_submission = $this->entityTypeManager()->getStorage('webform_submission')->load($id);
    if ($webform_submission) {
      $webform = $webform_submission->getWebform();
      if ($webform) {
        $data = $this->getWebformConfirmation($webform);
      }
    }

    return new JsonResponse([
      'id' => $id,
      'data' => $data,
    ]);
  }

  /**
   * Get Webform confirmation data.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform object.
   *
   * @return array
   *   Return webform confirmation data depends of confirmation type.
   */
  protected function getWebformConfirmation(Webform $webform) {
    $confirmation_type = $webform->getSetting('confirmation_type');
    $data['confirmation_type'] = $confirmation_type;

    $confirmation_message = trim($webform->getSetting('confirmation_message', ''));
    $confirmation_title = trim($webform->getSetting('confirmation_title', ''));

    switch ($confirmation_type) {
      case WebformInterface::CONFIRMATION_URL:
      case WebformInterface::CONFIRMATION_URL_MESSAGE:
        $confirmation_url = trim($webform->getSetting('confirmation_url'));
        // Remove base path from root-relative URL.
        // Only applies for Drupal sites within a sub directory.
        $confirmation_url = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $confirmation_url);
        // Get system path.
        $confirmation_url = $this->pathAliasManager->getPathByAlias($confirmation_url);
        // Get redirect URL if internal or valid.
        if (strpos($confirmation_url, 'internal:') === 0) {
          $redirect_url = Url::fromUri($confirmation_url);
        }
        else {
          $redirect_url = $this->pathValidator->getUrlIfValid($confirmation_url);
        }
        if ($redirect_url) {
          if ($confirmation_type == WebformInterface::CONFIRMATION_URL_MESSAGE) {
            $data['confirmation_message'] = $confirmation_message;
          }
          $data['redirect_url'] = $redirect_url->toString();
        }
        break;

      case WebformInterface::CONFIRMATION_INLINE:
      case WebformInterface::CONFIRMATION_MESSAGE:
        $data['confirmation_message'] = $confirmation_message;
        break;

      case WebformInterface::CONFIRMATION_PAGE:
      case WebformInterface::CONFIRMATION_MODAL:
        $data['confirmation_title'] = $confirmation_title;
        $data['confirmation_message'] = $confirmation_message;
        break;

      case WebformInterface::CONFIRMATION_NONE:
        break;

      case WebformInterface::CONFIRMATION_DEFAULT:
      default:
        $data['confirmation_message'] = $confirmation_message;
    }

    return $data;
  }

}
