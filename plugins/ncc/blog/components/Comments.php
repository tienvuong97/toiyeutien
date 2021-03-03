<?php namespace Ncc\Blog\Components;

use Cms\Classes\ComponentBase;
use October\Rain\Exception\ValidationException;
use Models, Input, Redirect, Flash, Validator;
use Ncc\Blog\Models\Comment;

class Comments extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'ncc.blog::lang.comment.comment_form',
            'description' => 'ncc.blog::lang.comment.description'
        ];
    }
    public function onRun()
    {
    }


    public function defineProperties()
    {
        return [];
    }
    public function onSave()
    {
        $data = [
            'name'       => Input::get('name'),
            'email'      => Input::get('email'),
            'content'    => Input::get('content'),
        ];

        $rules = [
            'name'       => 'required|max:30',          
            'email'      => 'required|email',
            'content'    => 'required|max:191'
        ];

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {

            throw new ValidationException($validator);

        } else {

            $comment = new Comment();
            // Get request data
            $comment->name       = Input::get('name');
            $comment->email      = Input::get('email');
            $comment->content    = Input::get('content');
            $comment->posts_id   = Input::get('posts_id');
            $comment->save();

            \Flash::success('Your comment has been sent successfully!');

            return Redirect::refresh();   
        }        
    }
    
}
