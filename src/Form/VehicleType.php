<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VehicleType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Vehicle Name',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 120),
                ],
            ])

            ->add('slug', TextType::class, [
                'label' => 'Vehicle Slug',
                'help' => 'Example: toyota-vios',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 150),
                    new Assert\Regex(
                        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                        message: 'Use lowercase letters, numbers, and hyphens only.'
                    ),
                ],
            ])

            ->add('seats', IntegerType::class, [
                'label' => 'Number of Seats',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                    new Assert\LessThanOrEqual(255),
                ],
            ])

            ->add('price', NumberType::class, [
                'label' => 'Price per Day (PHP)',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])

            ->add('vehicleClass', TextType::class, [
                'label' => 'Vehicle Category',
                'help' => 'Enter a category such as Car Sedan, SUV, 4x4 SUV, or Electric Vehicle.',
                'attr' => [
                    'placeholder' => 'Enter vehicle category',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 60),
                ],
            ])

            ->add('vehicleImage', FileType::class, [
                'label' => 'Upload Vehicle Image',
                'mapped' => false,
                'required' => false,
                'help' => 'Accepted formats: JPG, PNG, WEBP. Maximum size: 5 MB.',
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                ],
                'constraints' => array_filter([
                    !$options['is_edit'] ? new Assert\NotBlank(
                        message: 'Please upload a vehicle image.'
                    ) : null,

                    new Assert\File(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Please upload a valid JPG, PNG, or WEBP image.'
                    ),
                ]),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
