<?php

use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_entity_bundle_field_info_alter().
 */
function portland_legacy_redirects_entity_bundle_field_info_alter(&$fields, \Drupal\Core\Entity\EntityTypeInterface $entity_type, $bundle)
{
  _add_field_legacy_path_validation_constraint_path_exists($fields, $entity_type, $bundle);
}

/**
 * Add relative_path constraint to field_legacy_path if it exists in fields array for the given bundle.
 * This constraint requires the path to be relative, not contain any illegal characters, and not already
 * exist in the form or system. 
 * Called by hook_entity_bundle_field_info_alter.
 */
function _add_field_legacy_path_validation_constraint_path_exists(&$fields, \Drupal\Core\Entity\EntityTypeInterface $entity_type, $bundle) {
  if (array_key_exists('field_legacy_path', $fields)) {
    $fields['field_legacy_path']->addConstraint('relative_path', []);
  }
}

// NOTES ///////////
// * Invoke entity_update hook
// * If node hasn't been saved previously, save all redirects in field_legacy_path
// * If node has been saved previously, sync redirects and field_legacy_path

function portland_legacy_redirects_redirect_delete($redirect) {
  // when redirect is deleted, load target page, check field_legacy_path, and delete
  // value that corresponds to deleted redirect.
}

/**
 * Implements hook_entity_update.
 */
function portland_legacy_redirects_entity_update($entity) {
  // when entity is updated:
  // * compare updated field_legacy_paths with original; paths that have been removed are deleted from redirects.
  // * check redirects table and add entries for any legacy paths that don't already exist.
  if ($entity->hasField('field_legacy_path')) {
    _sync_redirects($entity);
  }
}

function _sync_redirects($entity) {
  $new_paths = $entity->get('field_legacy_path')->getValue();
  $org_paths = $entity->original->get('field_legacy_path')->getValue();

  // compare updated values in field_legacy_path with original values.
  // if a value was removed, add it to $del_paths. if value was added, add it to $add_paths.
  // after the comparisons are complete, spin through those 2 arrays and delete/add as needed.
  foreach ($new_paths as $new_path) {
    foreach ($org_paths as $org_path) {
      if ($new_path['value'] == $org_path['value']) continue; // didn't change; ignore
    }
    // in updated paths but not in orig paths, add it
    $add_paths[] = $new_path['value'];
  }
  foreach ($org_paths as $org_path) {
    foreach ($new_paths as $new_path) {
      if ($org_path['value'] == $new_path['value']) continue;
    }
    // in orig paths but not in updated paths, delete it
    $del_paths[] = $org_path['value'];
  }

  foreach ($add_paths as $add_path) {
    // add redirect
  }

  foreach ($del_paths as $del_path) {
    // delete redirect
  }

  // spin through original values

  // $new_paths[0]['value']
  // foreach ($redirects as $key => $redirect) {

  // spin through field_legacy_path
  //   if a path exists but not the redirect, add to create_redirects array
}


// /**
//  * Implements hook_entity_presave().
//  */
// function portland_legacy_redirects_entity_presave($entity)
// {
//   // if field_legacy_path exists on entity, sync redirects
//   // NOTE: can't create redirects on presave unless entity was previously saved and we can retrieve the id
//   if ($entity->hasField('field_legacy_path')) {
//     _portland_legacy_redirects_create_redirects($entity);
//   }
// }

// /**
//  * Implements hook_form_alter().
//  */
// function portland_legacy_redirects_form_alter(&$form, FormStateInterface $form_state, $form_id)
// {
//   // sync redirects if field_legacy_path exists and there is already a saved node at this route
//   if (array_key_exists('field_legacy_path', $form)) {
//     $form_object = $form_state->getFormObject();
//     $type = $form_object->getEntity()->getEntityTypeId();
//     $this_node = \Drupal::routeMatch()->getParameter($type);
//     if ($this_node) {
//       _sync_node_and_group_redirects($form, $this_node, $type);
//     }
//   }
// }

// /**
//  * Queries db for redirects that point to this node's URI and adds them to the legacy_path field.
//  * Only gets called if field_legacy_path exists in the form.
//  * Called by hook_form_alter.
//  */
// function _sync_node_and_group_redirects(&$form, $this_node, $type) {
//   // only call the sync function if the node has already been saved

//   // $form_object = $form_state->getFormObject();
//   // $type = $form_object->getEntity()->getEntityTypeId();
//   // $entity = $form_object->getEntity();
//   // $type = $entity->getEntityTypeId();


//   if ($this_node) {
//     $nid = $this_node->Id();

//     // if this is a new node form, exit this function
//     $this_node = \Drupal::routeMatch()->getParameter($type);
//     if (!$this_node) return;

//     $nid = $this_node->Id();
//     //$redirect_url = "entity:$type/$nid";
//     // redirects that are added manually through the UI have a different uri pattern, so we need
//     // to do two queries to make sure we're getting them all.
//     $redirects = \Drupal::service('redirect.repository')->findByDestinationUri(["internal:/$type/$nid", "entity:$type/$nid"]);

//     $new_deltas = array();
//     $field = $form['field_legacy_path']['widget'];
//     $max_delta = $field['#max_delta'];

//     // if max_delta is zero, that means no paths have been saved, and a blank field has
//     // been added to the form. we want that blank field to be at the end.
//     $delta_counter = $max_delta == 0 ? $max_delta : $max_delta + 1;
//     $save_empty_delta;
//     if ($max_delta == 0) {
//       $delta_counter = 0;
//       $save_empty_delta = $form['field_legacy_path']['widget'][0];
//       unset($form['field_legacy_path']['widget'][0]);
//     } else {
//       $delta_counter = $max_delta + 1;
//     }

//     // need to make sure there's a field delta for each of the redirects in $redirects,
//     foreach ($redirects as $key => $redirect) {
//       $source_path = $redirect->getSource()['path'];
//       // spin through field_legacy_path values to see if $source_path is in there. if not, add.
//       for ($i = 0; $i < $max_delta; $i++) {
//         $source_value = _portland_legacy_redirects_utility_strip_leading_slash($field[$i]['value']['#default_value']);
//         if ($source_path == $source_value) {
//           continue 2;
//         }
//       }

//       // if there wasn't a match and break by this point, add $source_path to a temporary array,
//       // which will be appended to field_legacy_path widget after the loops.
//       $new_deltas[$delta_counter] = [
//         '#delta' => $delta_counter,
//         '#weight' => $delta_counter,
//         'value' => [
//           '#type' => 'textfield',
//           '#size' => 60,
//           '#maxlength' => 255,
//           '#default_value' => '/' . $source_path
//         ],
//       ];

//       $delta_counter++;
//     }

//     // now add new deltas to field
//     foreach ($new_deltas as $idx => $delta) {
//       $form['field_legacy_path']['widget'][] = $delta;
//     }
//     if ($max_delta == 0) {
//       $form['field_legacy_path']['widget'][] = $save_empty_delta;
//     }
//   }

// }

// /**
//  * Implements hook_entity_update.
//  */
// function portland_legacy_redirects_entity_update($entity) {
//   _portland_legacy_redirects_create_redirects($entity);
// }

/**
 * Creates redirects for entity in the redirects table.
 * Called by hook_entity_update.
 */
function _portland_legacy_redirects_create_redirects(&$entity) {
  // if this is a node that has the legacy path field, create redirects
  $type = $entity->getEntityTypeId();
  if (($type == 'node' || $type == 'group') && $entity->hasField('field_legacy_path')){ // hasField call is unnecessary here

    // $entity->toUrl()->toUriString() generates a URI string that looks like this: route:entity.node.canonical;node=332.
    // The Redirect module stores a string that looks like this: entity:node/332.
    // Both work for redirection, but only ones in the latter format appear in the node's URL Redirects panel.
    $nid = $entity->id();
    $this_redirect_url = "entity:$type/" . $nid;

    // NOTE: on form_alter as the form is loading, paths that are in the redirects table
    // but not yet stored in the field are added in. this means the field and field_orig
    // values will always appear to be the same when this function is called.

    // an array of paths from the entity edit form
    $field_legacy_path = $entity->get('field_legacy_path')->getValue();

    // existing redirects that match this uri
    $redirects = \Drupal::service('redirect.repository')->findByDestinationUri(["internal:/$type/$nid", "entity:$type/$nid"]);

    // 1. spin through field values and add any to redirects table that aren't already there.
    foreach ($field_legacy_path as $delta => $value) {
      $str_value = $value['value'];
      // remove leading slash from value
      if (substr($str_value, 0, 1) == "/") {
        $str_value = substr($str_value, 1, strlen($str_value));
      }
      $found = _portland_legacy_redirects_utility_find_redirect_from_table($redirects, $str_value);
      if (!$found) {
        // create redirect
        Redirect::create([
          'redirect_source' => $str_value,
          'redirect_redirect' => $this_redirect_url,
          'language' => 'en',
          'status_code' => 301,
        ])->save();
      }
    }

    // 2. spin through redirects and remove any field values that arent there
    foreach ($redirects as $delta => $redirect) {
      $redirect_path = $redirect->get('redirect_source')[0]->getValue()['path'];
      $found = _portland_legacy_redirects_utility_find_redirect_from_field($field_legacy_path, $redirect_path);
      if (!$found) {
        // remove from table
        $redirect->delete();
      }
    }
  }
}

function _portland_legacy_redirects_utility_find_redirect_from_table($redirects, $find) {
  foreach ($redirects as $delta => $redirect) {
    $redirect_path = $redirect->get('redirect_source')[0]->getValue()['path'];
    if ($redirect_path == $find) {
      return $redirect;
    }
  }
}

function _portland_legacy_redirects_utility_find_redirect_from_field($field, $find) {
  foreach ($field as $delta => $path) {
    $redirect_path = $path['value'];
    if ($redirect_path == '/' . $find) {
      return $redirect_path;
    }
  }
}

/**
 * Strips leading slash from a path.
 */
function _portland_legacy_redirects_utility_strip_leading_slash($path) {
  return substr($path, 0, 1) == "/" ? substr($path, 1) : $path;
}
