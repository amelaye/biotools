<?php
/**
 * Shared base class for Form Type behavioral tests: wires a real (non-mocked) Symfony
 * validator into the form extension so that Callback/Length/GreaterThan/... constraints
 * declared by each FormType actually run, instead of the built-in ValidatorExtensionTrait's
 * mock which always reports zero violations.
 * Created 14 september 2026
 * Last modified 14 september 2026
 */
namespace Tests\Form;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

abstract class AbstractFormTypeTestCase extends TypeTestCase
{
    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return array_merge(parent::getExtensions(), [
            new ValidatorExtension($validator),
        ]);
    }
}
