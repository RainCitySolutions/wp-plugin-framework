<?php
declare(strict_types = 1);
namespace RainCity\WPF\Formidable;

use RainCity\Exception\InvalidStateException;

abstract class FormidableEntry
{
    protected \stdClass $entry;

    /**
     *
     * @param int $entryId
     * @param string $formKey
     * @param bool $includeMetaData
     */
    protected function __construct(int $entryId, string $formKey, bool $includeMetaData = true)
    {
        if (class_exists(\FrmEntry::class)) {
            /** @var \stdClass|null Get the entry with the meta data */
            $entry = \FrmEntry::getOne($entryId, $includeMetaData);

            if (!is_null($entry) && Formidable::getFormId($formKey) == $entry->formId) {
                $this->entry = $entry;
            } else {
                throw new \InvalidArgumentException('Invalid entryId or not for ROI form');
            }
        } else {
            throw new InvalidStateException('Formidable Forms does not appear to be installed and active');
        }
    }

    protected function getFieldValue(string $fieldKey): mixed
    {
        $value = null;

        if (isset($this->entry->metas)) {
            $fieldId = Formidable::getFieldId($fieldKey);

            if (!is_null($fieldId)) {
                $value = $this->entry->metas[$fieldId] ?? null;
            }
        }

        return $value;
    }
}
