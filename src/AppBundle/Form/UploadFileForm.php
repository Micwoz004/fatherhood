<?php
/**
 * Created by PhpStorm.
 * User: Michał
 * Date: 02.12.2017
 * Time: 12:52
 */

namespace AppBundle\Form;

use AppBundle\Entity\UploadFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class UploadFileForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('csv', FileType::class, array(
                'label' => 'Plik zawierający wyniki (w formacie CSV)',
                'attr' => array(
                    'class' => ''
                )
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Prześlij',
                'attr' => array(
                    'class' => 'btn btn-primary send-file',
                    'style' => 'margin-top: 30px;'
                )
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => UploadFile::class
        ));
    }

}