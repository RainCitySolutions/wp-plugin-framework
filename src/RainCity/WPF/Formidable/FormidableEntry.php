<?php
declare(strict_types = 1);
namespace RainCity\WPF\Formidable;

use RainCity\Exception\InvalidStateException;

abstract class FormidableEntry
{
    protected \stdClass $entry;

    /**
     * Initialize the Formidable entry.
     *
     * @param int $entryId An identifier for a Formidable Forms entry.
     * @param int|string $formIdKey The id or key for the form the entry belongs to.
     * @param bool $includeMetaData Whether to include the meta data for the entry.
     */
    protected function __construct(int $entryId, int|string $formIdKey, bool $includeMetaData = true)
    {
        if (class_exists(\FrmEntry::class)) {
            /** @var \stdClass|null Get the entry with the meta data */
            $entry = \FrmEntry::getOne($entryId, $includeMetaData);

            if (!is_null($entry) && $this->isMatchingForm($formIdKey, $entry->formId)) {
                $this->entry = $entry;
            } else {
                throw new \InvalidArgumentException('Invalid entryId or not for ROI form');
            }
        } else {
            throw new InvalidStateException('Formidable Forms does not appear to be installed and active');
        }
    }

    /**
     * Checks if the specifed form matches the form the entry belongs to.
     *
     * @param int|string $formIdKey The id or key for a form
     * @param int $entryFormId The id form the form associated with the entry
     *
     * @return bool True if the formIdKey and the $entryFormId are both the
     *      same form, otherwise false.
     */
    private function isMatchingForm(int|string $formIdKey, int $entryFormId): bool
    {
        if (!is_numeric($formIdKey)) {
            $formIdKey = Formidable::getFormId($formIdKey);
        }

        return $formIdKey == $entryFormId;
    }

    /**
     * Fetch the value for the specified field.
     *
     * @param int|string $fieldIdKey The id or key for a form field.
     *
     * @return mixed The value of the field, or null if the field is not
     *      present or the meta data was not requested during object
     *      construction.
     */
    protected function getFieldValue(int|string $fieldIdKey): mixed
    {
        $value = null;

        if (isset($this->entry->metas)) {
            if (!is_numeric($fieldIdKey)) {
                $fieldIdKey = Formidable::getFieldId($fieldIdKey);
            }

            if (!is_null($fieldIdKey)) {
                $value = $this->entry->metas[$fieldIdKey] ?? null;
            }
        }

        return $value;
    }

    /**
     * Fetch the value for the specified field as an integer.
     *
     * @param int|string $fieldIdKey The id or key for a form field.
     *
     * @return int|NULL The value of the field or null if the field is not
     *      present or the meta data was not requested during object
     *      construction.
     */
    protected function getFieldIntValue(int|string $fieldIdKey): ?int
    {
        $value = $this->getFieldValue($fieldIdKey);

        return !is_null($value) && is_numeric($value) ? intval($value) : null;
    }

    /**
     * Fetch the value for the specified field as a \DateTime instance
     *
     * @param int|string $fieldIdKey The id or key for a form field.
     *
     * @return \DateTime|NULL The value of the field or null if the field is not
     *      present or the meta data was not requested during object
     *      construction.
     */
    protected function getFieldDateValue(int|string $fieldIdKey): ?\DateTime
    {
        $dateStr = $this->getFieldValue($fieldIdKey);

        return !is_null($dateStr) ? new \DateTime($dateStr) : null;
    }
}
