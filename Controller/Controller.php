<?php

namespace Bazinga\ExposeRoutingBundle\Controller;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller class.
 *
 * @package     ExposeRoutingBundle
 * @subpackage  Controller
 * @author William DURAND <william.durand1@gmail.com>
 */
class Controller
{
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;
    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    protected $engine;
    /**
     * @var array
     */
    protected $routesToExpose;

    /**
     * Default constructor.
     * @param \Symfony\Component\Routing\RouterInterface  The router.
     * @param \Symfony\Component\Templating\EngineInterface   The template engine.
     * @param array Some route names to expose.
     */
    public function __construct(RouterInterface $router, EngineInterface $engine, array $routesToExpose)
    {
        $this->router = $router;
        $this->engine = $engine;
        $this->routesToExpose = $routesToExpose;
    }

    /**
     * indexAction action.
     */
    public function indexAction($_format)
    {
        $exposed_routes = array();
        $collection = $this->router->getRouteCollection();
        $pattern    = $this->buildPattern();

        foreach ($collection->all() as $name => $route) {
            if (false === $route->getOption('expose')) {
                continue;
            }

            if (($route->getOption('expose') && true === $route->getOption('expose'))
                || ('' !== $pattern && preg_match('#' . $pattern . '#', $name))) {
                // Maybe there is a better way to do that...
                $compiledRoute = $route->compile();
                $route->setDefaults(array_intersect_key(
                    $route->getDefaults(),
                    array_fill_keys($compiledRoute->getVariables(), null)
                ));

                $exposed_routes[$name] = $route;
            }
        }

        return new Response($this->engine->render('BazingaExposeRoutingBundle::index.' . $_format . '.twig', array(
            'var_prefix'        => '{',
            'var_suffix'        => '}',
            'prefix'            => $this->router->getContext()->getBaseUrl() ?: '',
            'exposed_routes'    => $exposed_routes,
        )));
    }

    /**
     * Convert the routesToExpose array in a regular expression pattern.
     * @return string
     */
    protected function buildPattern()
    {
        $patterns = array();
        foreach ($this->routesToExpose as $toExpose) {
            $patterns[] = '(' . $toExpose . ')';
        }
        return implode($patterns, '|');
    }
}
