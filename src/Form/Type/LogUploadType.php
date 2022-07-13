<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Vich\UploaderBundle\Form\Type\VichFileType;
use App\Entity\LogFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form used to upload log files
 */
class LogUploadType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('logFile', VichFileType::class, [
            'required' => true,
            'allow_delete' => true,
            'delete_label' => 'Remove file',
            'asset_helper' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogFile::class,
        ]);
    }


}
