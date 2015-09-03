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
        // get from database
        return $name;
    }

    public function isFresh($name, $time)
    {
        // determine from database
        return true;
    }

    public function getCacheKey($name)
    {
        // check if exists
        return 'twigStringService:' . $name;
    }

    public function render($string, $variables) {
//        error_log('STRING: '.$string);
//        error_log('VARIABLES: '.print_r($variables, TRUE));
        $twig = new \Twig_Environment($this);
        $rendered = $twig->render($string, $variables);
//        error_log('RENDERED: '.$rendered);
        return $rendered;
    }
}