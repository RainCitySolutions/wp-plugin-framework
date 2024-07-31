<?php
namespace RainCity\WPF;

abstract class FormHelper implements ActionHandlerInf
{
    protected string $formKey;

    public function __construct(string $formKey)
    {
        $this->formKey = $formKey;
    }

    public function getFormId(): int
    {
        return Formidable::getFormId($this->formKey);
    }

    /**
     * Method to give the helper an opportunity to add any actions or filters.
     *
     * @param ActionFilterLoader $loader
     */
    public function loadActions(ActionFilterLoader $loader): void
    {
    }
}
