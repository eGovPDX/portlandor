<?php

namespace Drupal\simple_oauth;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\user\RoleInterface;

/**
 * Defines a class to build a listing of Access Token entities.
 *
 * @ingroup simple_oauth
 */
class Oauth2TokenListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['type'] = $this->t('Type');
    $header['user'] = $this->t('User');
    $header['name'] = $this->t('Token');
    $header['client'] = $this->t('Client');
    $header['scopes'] = $this->t('Scopes');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\simple_oauth\Entity\Oauth2Token */
    $row['id'] = $entity->id();
    $row['type'] = $entity->bundle();
    $row['user'] = NULL;
    $row['name'] = $entity->toLink(sprintf('%s…', substr($entity->label(), 0, 10)));
    $row['client'] = NULL;
    $row['scopes'] = NULL;
    if (($user = $entity->get('auth_user_id')) && $user->entity) {
      $row['user'] = $user->entity->toLink($user->entity->label());
    }
    if (($client = $entity->get('client')) && $client->entity) {
      $row['client'] = $client->entity->toLink($client->entity->label(), 'edit-form');
    }
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $scopes */
    if ($scopes = $entity->get('scopes')) {
      $row['scopes'] = implode(', ', array_map(function (RoleInterface $role) {
        return $role->label();
      }, $scopes->referencedEntities()));
    }

    return $row + parent::buildRow($entity);
  }

}
