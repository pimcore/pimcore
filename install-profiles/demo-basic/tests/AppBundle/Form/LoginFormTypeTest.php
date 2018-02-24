<?php

declare(strict_types=1);

namespace Tests\AppBundle\Form;

use AppBundle\Form\LoginFormType;
use Symfony\Component\Form\Test\TypeTestCase;

class LoginFormTypeTest extends TypeTestCase
{
    public function testFormContainsLoginFields()
    {
        $form = $this->factory->create(LoginFormType::class);

        $children = [
            '_username',
            '_password',
        ];

        foreach ($children as $child) {
            $this->assertTrue($form->has($child), sprintf('Form has a field named "%s"', $child));
        }
    }
}
