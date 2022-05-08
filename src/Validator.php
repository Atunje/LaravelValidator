<?php

namespace Nobelatunje\LaravelValidator;

use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator as BaseValidator;
use Illuminate\Http\Request;


class Validator {

    /**
     * Entity
     * 
     * object or array that contains validated fields
     */
    public object|array $entity;


    /**
     * Model Class
     * 
     * the class name of the entity to be created after validation
     */
    private string $model_class = '';


    /**
     * Object name
     * 
     * The name of the object to be created after validation instead of the generic name "entity"
     */
    private string $object_name;


    /**
     * Model ID Field Name
     */
    private object $model_instance;


    /**
     * Fields
     * 
     * fields to be validated
     */
    protected array $fields;


    /**
     * Error Message
     * 
     * Error message set when validation returns false
     */
    public string $error_message = "";


    /**
     * Validation rules
     * 
     * an array of rules
     */
    public array $validation_rules = [];


    /**
     * Validation messages
     * 
     * an array of validation messages
     */
    protected array $validation_messages = [];


    /**
     * Attributes
     * 
     * array of the attributes for each field name
     */
    private array $attributes = [];


    /**
     * Set Model ID
     * 
     * Set the id of the model for which data is being validated
     * 
     * @param object $instance
     * 
     * @return Validator
     */
    public function setModelInstance(object $instance): Validator {
        //set 
        $this->model_instance = $instance;

        //allow for method to be chained with other public methods
        return $this;
    }


    /**
     * Field
     * 
     * Set field and it validation rules
     * 
     * @param string $field_name
     * @param string $rules_str
     * 
     * @return Validator
     */
    public function field(string $field_name, string $rules_str, string $attribute_name=''): Validator {

        //initialize
        $this->validation_rules[$field_name] = [];

        $this->fields[] = $field_name;

        //split the rules into component parts
        $rules = explode('|', $rules_str);

        foreach($rules as $rule) {

            //set the validation rule
            $this->setValidationRule($field_name, $rule);

            //set the attribute
            $this->attributes[$field_name] = $attribute_name ?? $field_name;

        }

        //allow for method to be chained with other public methods
        return $this;

    }


    /**
     * Set Validation Messages
     * 
     * Set custom validation messages
     * 
     * @param array $messages
     * 
     * @return Validator
     */
    public function setValidationMessages(array $messages): Validator {

        $this->validation_messages = $messages;

        //allow for method to be chained with other public methods
        return $this;

    }


    /**
     * Set Rule
     * 
     * Add rule to the validation rules array
     */
    protected function setValidationRule($field_name, $rule_str) {

        //separate the rule to get the ones that have association with the db
        $rules = explode(":", $rule_str);

        if(in_array($rules[0], ['exists', 'unique'])) {

            //set the keyword
            $rule_keyword = $rules[0];

            //set the table
            $db_table = $rules[1];

            //set the validation rule
            $rule = Rule::$rule_keyword($db_table);

            if($rule_keyword == 'unique' && !empty($this->model_instance)) 
                $rule = $rule->ignore($this->model_instance);

            $this->validation_rules[$field_name][] = $rule;

        } else {

            $this->validation_rules[$field_name][] = $rule_str;

        }

    }


    /**
     * Validate
     * 
     * validate the supplied fields
     * 
     * @param Request $request
     * @param string $model_class
     * 
     * @throws Exception
     * 
     * @return bool
     */
    public function validate(Request $request): bool {

        if(!empty($this->validation_rules)) {

            $validator = BaseValidator::make($request->all(), $this->validation_rules, $this->validation_messages, $this->attributes);

            if($validator->fails()) {

                foreach($validator->errors()->all() as $msg) {
                    $this->error_message = $msg;
                    break;
                }

                return false;

            } else {
                $this->createEntity($request->all());
            }

            return true;

        }

        throw new Exception("You have not created any field with its validation rules");

    }

    /**
     * Set Model Params
     * 
     * set the class name and the object name of the entity to be created after validation is successful
     * 
     * @param string $model_class
     * @param string $object_name
     * 
     * @return Validator
     */
    public function setModelParams(string $model_class, string $object_name=''): Validator {
        
        $this->model_class = $model_class;
        $this->object_name = $object_name;

        //allow for method to be chained with other public methods
        return $this;
    }


    /**
     * Get Valid Fields
     * 
     * Returns field => value pairs of fields that have valid inputs even if the validation fails
     * 
     * @param Request $request
     * 
     * @return array
     */
    public function getValidFields(Request $request): array {

        if(!empty($this->validation_rules)) {

            $validated = [];

            $validator = BaseValidator::make($request->all(), $this->validation_rules);

            $errors = $validator->errors();

            foreach($this->fields as $field) {

                if(!$errors->has($field)) 
                    $validated[$field] = $request->$field; 

            }

            return $validated;

        }

        throw new Exception("You have not created any field with its validation rules");
    }


    /**
     * Set the entity after validation
     */
    protected function createEntity($data) {

        if(!empty($this->model_class)) {
            
            $this->createModelInstance($data);

        } else {
            
            $this->entity = $data;

        }
       
    }


    /**
     * Create Model Instance
     * 
     * either updates the specified model instance or create a new one and populate it with validated fields
     */
    protected function createModelInstance(array $data) {

        $this->entity = $this->model_instance ?? new $this->model_class();

        foreach($this->fields as $field) {

            $this->entity->$field = $data[$field];

        }

        if(!empty($this->object_name)) {
            $obj_name = $this->object_name;
            $this->$obj_name = $this->entity;
        }
    }

}