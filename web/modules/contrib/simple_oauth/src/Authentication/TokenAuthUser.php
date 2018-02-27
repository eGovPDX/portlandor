<?php

namespace Drupal\simple_oauth\Authentication;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_oauth\Entity\Oauth2TokenInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * @internal
 */
class TokenAuthUser implements TokenAuthUserInterface {

  /**
   * The decorator subject.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $subject;

  /**
   * The bearer token.
   *
   * @var \Drupal\simple_oauth\Entity\Oauth2TokenInterface
   */
  protected $token;

  /**
   * Constructs a TokenAuthUser object.
   *
   * @param \Drupal\simple_oauth\Entity\Oauth2TokenInterface $token
   *   The underlying token.
   *
   * @throws \Exception
   *   When there is no user.
   */
  public function __construct(Oauth2TokenInterface $token) {
    if (!$this->subject = $token->get('auth_user_id')->entity) {
      /** @var \Drupal\consumers\Entity\Consumer $client */
      if ($client = $token->get('client')->entity) {
        $this->subject = $client->get('user_id')->entity;
      }
    }
    if (!$this->subject) {
      throw OAuthServerException::invalidClient();
    }
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->token;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    return array_map(function ($item) {
      return $item['target_id'];
    }, $this->token->get('scopes')->getValue());
  }

  /* ---------------------------------------------------------------------------
  All the methods below are delegated to the decorated user.
  --------------------------------------------------------------------------- */

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $this->subject->access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    // User #1 has all privileges.
    if ((int) $this->id() === 1) {
      return TRUE;
    }

    return $this->getRoleStorage()->isPermissionInRoles($permission, $this->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return $this->subject->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->subject->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredLangcode($fallback_to_default = TRUE) {
    return $this->subject->getPreferredLangcode($fallback_to_default);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredAdminLangcode($fallback_to_default = TRUE) {
    return $this->subject->getPreferredAdminLangcode($fallback_to_default);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return $this->subject->getUsername();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountName() {
    return $this->subject->getAccountName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayName() {
    return $this->subject->getDisplayName();
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->subject->getEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeZone() {
    return $this->subject->getTimeZone();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastAccessedTime() {
    return $this->subject->getLastAccessedTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->subject->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->subject->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->subject->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslationChanges() {
    return $this->subject->hasTranslationChanges();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionTranslationAffected($affected) {
    return $this->subject->setRevisionTranslationAffected($affected);
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionTranslationAffected() {
    return $this->subject->isRevisionTranslationAffected();
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->subject->getChangedTime();
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    return $this->subject->setChangedTime($timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations() {
    return $this->subject->getChangedTimeAcrossTranslations();
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return $this->subject->uuid();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->subject->id();
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    return $this->subject->language();
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return $this->subject->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function enforceIsNew($value = TRUE) {
    return $this->subject->enforceIsNew($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->subject->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return $this->subject->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->subject->label();
  }

  /**
   * {@inheritdoc}
   */
  public function urlInfo($rel = 'canonical', array $options = []) {
    return $this->subject->urlInfo($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', $options = []) {
    return $this->subject->url($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function link($text = NULL, $rel = 'canonical', array $options = []) {
    return $this->subject->link($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkTemplate($key) {
    return $this->subject->hasLinkTemplate($key);
  }

  /**
   * {@inheritdoc}
   */
  public function uriRelationships() {
    return $this->subject->uriRelationships();
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id) {
    return User::load($id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(array $ids = NULL) {
    return User::loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    return User::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    return $this->subject->save();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->subject->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $this->subject->preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->subject->postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    User::preCreate($storage, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    return $this->subject->postCreate($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    User::preDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    User::postDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    User::postLoad($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    return $this->subject->createDuplicate();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->subject->getEntityType();
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    return $this->subject->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalId() {
    return $this->subject->getOriginalId();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return $this->subject->getCacheTagsToInvalidate();
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalId($id) {
    return $this->subject->setOriginalId($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTypedData() {
    return $this->subject->getTypedData();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyKey() {
    return $this->subject->getConfigDependencyKey();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyName() {
    return $this->subject->getConfigDependencyName();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigTarget() {
    return $this->subject->getConfigTarget();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    return User::baseFieldDefinitions($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    return User::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function hasField($field_name) {
    return $this->subject->hasField($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition($name) {
    return $this->subject->getFieldDefinition($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    return $this->subject->getFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->subject->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function get($field_name) {
    return $this->subject->get($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function set($field_name, $value, $notify = TRUE) {
    return $this->subject->set($field_name, $value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($include_computed = TRUE) {
    return $this->subject->getFields($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableFields($include_computed = TRUE) {
    return $this->subject->getTranslatableFields($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($field_name) {
    $this->subject->onChange($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return $this->subject->validate();
  }

  /**
   * {@inheritdoc}
   */
  public function isValidationRequired() {
    return $this->subject->isValidationRequired();
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationRequired($required) {
    return $this->subject->setValidationRequired($required);
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheContexts(array $cache_contexts) {
    return $this->subject->addCacheContexts($cache_contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheTags(array $cache_tags) {
    return $this->subject->addCacheTags($cache_tags);
  }

  /**
   * {@inheritdoc}
   */
  public function mergeCacheMaxAge($max_age) {
    return $this->subject->mergeCacheMaxAge($max_age);
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheableDependency($other_object) {
    return $this->subject->addCacheableDependency($other_object);
  }

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->subject->isNewRevision();
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($value = TRUE) {
    $this->subject->setNewRevision($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionId() {
    return $this->subject->getRevisionId();
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevision($new_value = NULL) {
    return $this->subject->isDefaultRevision($new_value);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    $this->subject->preSaveRevision($storage, $record);
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultTranslation() {
    return $this->subject->isDefaultTranslation();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationLanguages($include_default = TRUE) {
    return $this->subject->getTranslationLanguages($include_default);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslation($langcode) {
    return $this->subject->getTranslation($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getUntranslated() {
    return $this->subject->getUntranslated();
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslation($langcode) {
    return $this->subject->hasTranslation($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function addTranslation($langcode, array $values = []) {
    return $this->subject->addTranslation($langcode, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function removeTranslation($langcode) {
    $this->subject->removeTranslation($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return $this->subject->isTranslatable();
  }

  /**
   * {@inheritdoc}
   */
  public function hasRole($rid) {
    return in_array($rid, $this->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function addRole($rid) {
    $this->subject->addRole($rid);
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole($rid) {
    $this->subject->removeRole($rid);
  }

  /**
   * {@inheritdoc}
   */
  public function setUsername($username) {
    return $this->subject->setUsername($username);
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return $this->subject->getPassword();
  }

  /**
   * {@inheritdoc}
   */
  public function setPassword($password) {
    return $this->subject->setPassword($password);
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    return $this->subject->setEmail($mail);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->subject->getCreatedTime();
  }

  /**
   * {@inheritdoc}
   */
  public function setLastAccessTime($timestamp) {
    return $this->subject->setLastAccessTime($timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastLoginTime() {
    return $this->subject->getLastLoginTime();
  }

  /**
   * {@inheritdoc}
   */
  public function setLastLoginTime($timestamp) {
    return $this->subject->setLastLoginTime($timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->subject->isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function isBlocked() {
    return $this->subject->isBlocked();
  }

  /**
   * {@inheritdoc}
   */
  public function activate() {
    return $this->subject->activate();
  }

  /**
   * {@inheritdoc}
   */
  public function block() {
    return $this->subject->block();
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialEmail() {
    return $this->subject->getInitialEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function setExistingPassword($password) {
    return $this->subject->setExistingPassword($password);
  }

  /**
   * {@inheritdoc}
   */
  public function checkExistingPassword(UserInterface $account_unchanged) {
    return $this->subject->checkExistingPassword($account_unchanged);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    throw new \Exception('Invalid use of getIterator in token authentication.');
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    $this->subject->toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function toLink($text = NULL, $rel = 'canonical', array $options = []) {
    return $this->subject->toLink($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function isNewTranslation() {
    return $this->subject->isNewTranslation();
  }

  /**
   * {@inheritdoc}
   */
  public function getLoadedRevisionId() {
    return $this->subject->getLoadedRevisionId();
  }

  /**
   * {@inheritdoc}
   */
  public function updateLoadedRevisionId() {
    return $this->subject->updateLoadedRevisionId();
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestRevision() {
    return $this->subject->isLatestRevision();
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevisionId() {
    return $this->subject->getLatestRevisionId();
  }

  /**
   * Returns the role storage object.
   *
   * @return \Drupal\user\RoleStorageInterface
   *   The role storage object.
   */
  protected function getRoleStorage() {
    return \Drupal::entityTypeManager()->getStorage('user_role');
  }

}
