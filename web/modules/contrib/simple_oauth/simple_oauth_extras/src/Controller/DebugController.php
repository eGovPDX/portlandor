<?php

namespace Drupal\simple_oauth_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\PermissionHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DebugController extends ControllerBase {

  /**
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $userPermissions;

  /**
   * Oauth2Token constructor.
   *
   * @param \Drupal\user\PermissionHandlerInterface $user_permissions
   */
  public function __construct(PermissionHandlerInterface $user_permissions) {
    $this->userPermissions = $user_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions')
    );
  }

  /**
   * Processes a GET request.
   */
  public function debug(ServerRequestInterface $request) {
    $user = $this->currentUser();
    $permissions_list = $this->userPermissions->getPermissions();
    $permission_info = [];
    // Loop over all the permissions and check if the user has access or not.
    foreach ($permissions_list as $permission_id => $permission) {
      $permission_info[$permission_id] = [
        'title' => $permission['title'],
        'access' => $user->hasPermission($permission_id),
      ];
      if (!empty($permission['description'])) {
        $permission_info['description'] = $permission['description'];
      }
    }
    return new JsonResponse([
      'token' => str_replace('Bearer ', '', $request->getHeader('Authorization')),
      'id' => $user->id(),
      'roles' => $user->getRoles(),
      'permissions' => $permission_info,
    ]);
  }

}
