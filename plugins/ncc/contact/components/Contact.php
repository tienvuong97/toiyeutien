<?php namespace Ncc\Contact\Components;

use Cms\Classes\ComponentBase;
use October\Rain\Exception\ValidationException;
use Models, Input, Redirect, Flash, Validator, Mail;
use Ncc\Contact\Models\Contacts;
use Ncc\Contact\Models\Settings as ContactSettings;

class Contact extends ComponentBase
{
   
    public $email;
    public $allowReceive;

    public function componentDetails()
    {
        return [
            'name'        => 'ncc.contact::lang.contact.component_name',
            'description' => 'ncc.contact::lang.contact.component_description'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onSave()

    {
        // Get values form contact config
        $allowReceive = ContactSettings::instance()->allow_receive_mail;
        $email        = ContactSettings::instance()->email_receive;
        
        $data = [
            'name'       => Input::get('name'),
            'email'      => Input::get('email'),
            'subject'    => Input::get('subject'),
            'content'    => Input::get('content'),
        ];
    
        $rules = [
            'name'       => 'required|max:30',          
            'email'      => 'required|email',
            'subject'    => 'required|max:191',
            'content'    => 'required|max:191'
        ];

        $subject = $data['subject'];

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {

            throw new ValidationException($validator);

        } else {

            $contact = new Contacts();
            // Get request data
            $contact->name       = Input::get('name');
            $contact->email      = Input::get('email');
            $contact->subject    = Input::get('subject');
            $contact->content    = Input::get('content');

            $contact->save();

            Flash::success('Your contact has been sent successfully!');

            if ($allowReceive == '1') {

                Mail::send('ncc.contact::mail.msg', $data, function($message) use ($email, $subject) {

                    $message->to($email);
                    $message->subject($subject);

                });
            }
            return Redirect::refresh();   

        }
    }
}
