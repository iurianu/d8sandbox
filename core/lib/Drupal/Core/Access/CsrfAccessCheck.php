<?php

/**
 * @file
 * Contains \Drupal\Core\Access\CsrfAccessCheck.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Allows access to routes to be controlled by a '_csrf_token' parameter.
 *
 * To use this check, add a "token" GET parameter to URLs of which the value is
 * a token generated by \Drupal::csrfToken()->get() using the same value as the
 * "_csrf_token" parameter in the route.
 */
class CsrfAccessCheck implements RoutingAccessInterface {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * Constructs a CsrfAccessCheck object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(CsrfTokenGenerator $csrf_token) {
    $this->csrfToken = $csrf_token;
  }

  /**
   * Checks access based on a CSRF token for the request.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, Request $request, RouteMatchInterface $route_match) {
    $parameters = $route_match->getRawParameters();
    $path = ltrim($route->getPath(), '/');
    // Replace the path parameters with values from the parameters array.
    foreach ($parameters as $param => $value) {
      $path = str_replace("{{$param}}", $value, $path);
    }

    if ($this->csrfToken->validate($request->query->get('token'), $path)) {
      $result = AccessResult::allowed();
    }
    else {
      $result = AccessResult::forbidden();
    }
    // Not cacheable because the CSRF token is highly dynamic.
    return $result->setCacheMaxAge(0);
  }

}