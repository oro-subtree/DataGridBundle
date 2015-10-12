<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\Handler\Extra;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor\EntityApiHandlerInterface;

class ContactApiHandler implements EntityApiHandlerInterface
{
    const HANDLER_KEY = 'oro.entity.api.extra.contact';
    const ENTITY_CLASS = 'OroCRM\Bundle\ContactBundle\Entity\Contact';

    /**
     * {@inheritdoc}
     */
    public function preProcess($entity)
    {
        // TODO: Implement preProcess() method.
    }

    /**
     * {@inheritdoc}
     */
    public function onProcess($entity)
    {
        // TODO: Implement process() method.
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcess($entity)
    {
        // TODO: Implement afterProcess() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return self::ENTITY_CLASS;
    }
}
