<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 7/8/15
 * Time: 11:39 PM
 */

namespace Ucsf\LdapOrmBundle\Components;


class TwigString implements \Twig_LoaderInterface{
    public function getSource($name)
    {
        return $name;
    }

    public function isFresh($name, $time)
    {
        return true;
    }

    public function exists($name)
    {
        return true;
    }

    public function getCacheKey($name)
    {
        return 'twigStringService:' . $name;
    }

    public function render($string, $variables) {
        $twig = new \Twig_Environment($this);
        $rendered = $twig->render($string, $variables);
        return $rendered;
    }
}