<?php

namespace App\Form;

use App\Entity\Car;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand', TextType::class, [
                'attr' => ['class' => 'form-input', 'placeholder' => 'e.g., BMW, Audi, Porsche'],
                'label' => 'Brand'
            ])
            ->add('model', TextType::class, [
                'attr' => ['class' => 'form-input', 'placeholder' => 'e.g., 3 Series, A3, 911'],
                'label' => 'Model'
            ])
            ->add('basePrice', IntegerType::class, [
                'attr' => ['class' => 'form-input', 'placeholder' => 'e.g., 42000'],
                'label' => 'Base Price (USD)'
            ])
            ->add('image', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'e.g., bmw-3series.jpg'],
                'label' => 'Image Filename',
                'help' => 'Place image in public/uploads/cars/ folder'
            ]);
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}