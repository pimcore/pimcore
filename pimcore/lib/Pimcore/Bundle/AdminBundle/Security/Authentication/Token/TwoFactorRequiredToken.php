<?php
/**
 * Created by PhpStorm.
 * User: tmittendorfer
 * Date: 06.06.2018
 * Time: 15:42
 */

namespace Pimcore\Bundle\AdminBundle\Security\Authentication\Token;


use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class TwoFactorRequiredToken extends PostAuthenticationGuardToken
{

}