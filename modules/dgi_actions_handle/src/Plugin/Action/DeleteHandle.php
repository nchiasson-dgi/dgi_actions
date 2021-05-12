<?php

namespace Drupal\dgi_actions_handle\Plugin\Action;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\dgi_actions\Plugin\Action\DeleteIdentifier;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\dgi_actions_handle\Utility\HandleTrait;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes a Handle.
 *
 * @Action(
 *   id = "dgi_actions_delete_handle",
 *   label = @Translation("Delete a Handle"),
 *   type = "entity"
 * )
 */
class DeleteHandle extends DeleteIdentifier {

  use HandleTrait;

  /**
   * {@inheritdoc}
   */
  protected function handleResponse(ResponseInterface $response) {
    $this->logger->info('Handle %prefix/%suffix was deleted.', [
      '%prefix' => $this->getPrefix(),
      '%suffix' => $this->getSuffix(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function delete() {
    $this->handleRequest();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestType(): string {
    return 'DELETE';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    return [
      'auth' => $this->getAuthorizationParams(),
    ];
  }

}
