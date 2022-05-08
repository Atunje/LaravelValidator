# Nobelatunje/LaravelValidator

A simple validation functionality built on top of Laravel Validator class.

This library can be plugged into any laravel or lumen application and you can easily manage validation.

Core functionalities are:
1. Validates incoming request data and returns an array of validated parameters
2. It can also return a model object if you like, just set the model parameters
3. You can also extract validated fields even if the validation fails
4. It can be injected into your controller class and usage is smooth

### How to install
Install via composer

    $ composer install nobelatunje/laravel-validator

### How to use in your controller
```php

namespace App\Http\Controllers;

use Nobelatunje\LaravelValidator;

class CustomerController extends Controller {

    public function create(LaravelValidator $validator, Request $request) {

        //set validation rules
        $this->setValidationRules($validator);

        if($validator->validate($request)) {

            //save the customer
            $validator->customer->save();
                
            //do other things
            

        } else {
            //return the error message or do something else
            return $validator->error_message;
        }
    
    }


    private function setValidationRules(LaravelValidator $validator) {
        //set the model params
        $validator->setModelParams(Customer::class, 'customer')
        //set the fields and the validation rules
        ->field('firstname', 'string|required', 'FirstName')
        ->field('lastname', 'string|required', 'LastName')
        ->field('email', 'required|email:rfc,dns|unique:customers', 'Email Address')
        ->field('phone_number', 'required|string|unique:customers', 'Phone Number');
    }

}
```
