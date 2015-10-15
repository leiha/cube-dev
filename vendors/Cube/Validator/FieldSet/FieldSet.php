<?php

namespace Cube\Validator\FieldSet;

use Cube\Validator\Cleaner\CleanerConstants;
use Cube\Validator\Constraint\ConstraintConstants;
use Cube\Validator\FieldSet\Field\Field;
use Cube\Validator\FieldSet\Field\FieldConstants;

class FieldSet
    implements FieldConstants,
               ConstraintConstants,
               CleanerConstants
{
    const CLASS_fieldSet = 'Cube\Validator\FieldSet\FieldSet';
    const CLASS_field    = 'Cube\Validator\FieldSet\Field\Field';

    /**
     * @var FieldSet[]
     */
    private static $_fieldSets = array();

    /**
     * @param string $fieldSetName
     * @return FieldSet
     */
    public static function get($fieldSetName)
    {
        if(!isset(self::$_fieldSets[$fieldSetName])) {
            if(class_exists($fieldSetName)
                && is_subclass_of($fieldSetName, static::CLASS_fieldSet)
            ) {
                self::$_fieldSets[$fieldSetName] = new $fieldSetName();
            }
            else {
                return null;
            }
        }
        return self::$_fieldSets[$fieldSetName];
    }

    /**
     * @var Field[]
     */
    protected $fields = array();

    /**
     * @var FieldSet[]
     */
    protected $fieldSets = array();

    /**
     * @var array
     */
    protected $valuesValidated = array();

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @param string $fieldSetName
     */
    public function __construct($fieldSetName)
    {
        $this->name = $fieldSetName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $includeGroups
     * @return array
     */
    public function getAllValues($includeGroups = true)
    {
        $values = $this->valuesValidated;

        if($includeGroups) {
            foreach($this->fieldSets as $fieldSet) {
                $values = array_merge($values, $fieldSet->getAllValues());
            }
        }

        return $values;
    }

    /**
     * @param string $groupName
     * @param array  $values
     * @param array  $errors
     * @return boolean
     */
    public function validateGroupValues($groupName, array $values, &$errors = array())
    {
        return isset($this->fieldSets[$groupName])
            ? $this->fieldSets[$groupName]->validateValues($values, $errors)
            : false
            ;
    }

    /**
     * @param array $values
     * @param array $errors
     * @param bool $includeGroups
     * @return bool
     */
    public function validateValues(array $values, &$errors = array(), $includeGroups = true)
    {
        /**
         * Validate Fields of fieldSet
         */
        $cValidateFields = function()
            use ($values, &$errors)
        {
            foreach($this->fields as $fieldId => $field) {
                $fieldName = $field->getName();

                empty($values[$fieldName])
                    ? $this->fixEmptyValue($fieldId, $field, $errors)
                    : $this->validateValue($fieldId, $field, $values[$fieldName], $errors)
                ;
            }
        };

        /**
         * Validate Groups of fieldSet
         */
        $cValidateGroups = function()
            use ($values, &$errors, $includeGroups)
        {
            if ($includeGroups) {
                foreach ($this->fieldSets as $fieldSetName => $fieldSet) {
                    // If array values contains a key of fieldSet name and is an array
                    // Then we take this array for validation
                    $_values = isset($values[$fieldSetName]) && is_array($values[$fieldSetName])
                        ? $values[$fieldSetName]
                        : $values;

                    $fieldSet->validateValues($_values, $errors);
                }
            }
        };

        $this->valuesValidated = array();

        $cValidateFields();
        $cValidateGroups();

        return (count($errors) == 0);
    }

    /**
     * @param string $fieldId
     * @param Field $field
     * @param array $errors
     */
    protected function fixEmptyValue($fieldId, Field $field, &$errors = array())
    {
        if($field->isRequired()) {
            $errors[$field->getName()]['required'] = true;
            return;
        }

        $defaultValue = $field->getDefaultValue();
        if(null !== $defaultValue) {
            $this->valuesValidated[$fieldId] = $defaultValue;
        }
    }

    /**
     * @param string $fieldId
     * @param Field  $field
     * @param mixed  $value
     * @param array  $errors
     * @return bool
     */
    protected function validateValue($fieldId, Field $field, $value, &$errors = array())
    {
        $is = $field->validate($value, $errors);
        if($is) {
            $this->valuesValidated[$fieldId] = $value;
        }

        return isset($errors[$fieldId])
            ? (count($errors[$fieldId]) == 0)
            : true
            ;
    }

    /**
     * @param $groupName
     * @return FieldSet
     */
    public function addGroup($groupName)
    {
        return $this->_add('fieldSet', $groupName);
    }

    /**
     * @param string $fieldName
     * @param mixed $defaultValue
     * @param string $type          Field::TYPE_*
     * @return Field
     */
    public function addField($fieldName, $defaultValue = null, $type = self::TYPE_string)
    {
        return $this->_add('field', $fieldName)
            ->setType        ($type)
            ->addConstraint  ($type)
            ->setDefaultValue($defaultValue)
        ;
    }

    /**
     * @param string $groupName
     * @return FieldSet
     */
    public function getGroup($groupName)
    {
       return $this->_get('fieldSet', $groupName);
    }

    /**
     * @param string $fieldName
     * @return Field
     */
    public function getField($fieldName)
    {
        return $this->_get('field', $fieldName);
    }

    /**
     * @param string $type [field | fieldSet]
     * @param string $name
     * @return FieldSet|Field
     */
    private function _add($type, $name)
    {
        $propName  = $type.'s';
        $className = constant('static::CLASS_'.$type);
        $this->{$propName}[$name] = new $className($name);
        return $this->{$propName}[$name];
    }

    /**
     * @param string $type [field | fieldSet]
     * @param string $name
     * @return Field
     */
    private function _get($type, $name)
    {
        $propName  = $type.'s';
        if(isset($this->{$propName}[$name])) {
            return $this->{$propName}[$name];
        }

        return null;
    }
}