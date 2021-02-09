<?php

/**
 * @file
 * Contains user_agreement.page.inc.
 *
 * Page callback for User agreement entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for User agreement templates.
 *
 * Default template: user_agreement.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_user_agreement(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
