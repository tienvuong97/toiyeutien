<?php namespace Ncc\User\Components;

use Lang;
use Auth;
use Mail;
use Event;
use Flash;
use Input;
use Request;
use Redirect;
use Validator;
use ValidationException;
use ApplicationException;
use Backend\Models\User;
use October\Rain\Auth\AuthException;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Ncc\User\Models\User as UserModel;
use Ncc\User\Models\Settings as UserSettings;
use Exception;

/**
 * Account component
 *
 * Allows users to register, sign in and update their account. They can also
 * deactivate their account and resend the account verification email.
 */
class Account extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => /*Account*/'ncc.user::lang.account.account',
            'description' => /*User management form.*/'ncc.user::lang.account.account_desc'
        ];
    }

    public function defineProperties()
    {
        return [
            'redirect' => [
                'title'       => /*Redirect to*/'ncc.user::lang.account.redirect_to',
                'description' => /*Page name to redirect to after update, sign in or registration.*/'ncc.user::lang.account.redirect_to_desc',
                'type'        => 'dropdown',
                'default'     => ''
            ],
            'paramCode' => [
                'title'       => /*Activation Code Param*/'ncc.user::lang.account.code_param',
                'description' => /*The page URL parameter used for the registration activation code*/ 'ncc.user::lang.account.code_param_desc',
                'type'        => 'string',
                'default'     => 'code'
            ],
            'forceSecure' => [
                'title'       => /*Force secure protocol*/'ncc.user::lang.account.force_secure',
                'description' => /*Always redirect the URL with the HTTPS schema.*/'ncc.user::lang.account.force_secure_desc',
                'type'        => 'checkbox',
                'default'     => 0
            ],
            'requirePassword' => [
                'title'       => /*Confirm password on update*/'ncc.user::lang.account.update_requires_password',
                'description' => /*Require the current password of the user when changing their profile.*/'ncc.user::lang.account.update_requires_password_comment',
                'type'        => 'checkbox',
                'default'     => 0
            ],
        ];
    }

    public function getRedirectOptions()
    {
        return [''=>'- refresh page -', '0' => '- no redirect -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * Executed when this component is initialized
     */
    public function prepareVars()
    {
        $this->page['user'] = $this->user();
        $this->page['canRegister'] = $this->canRegister();
        $this->page['loginAttribute'] = $this->loginAttribute();
        $this->page['loginAttributeLabel'] = $this->loginAttributeLabel();
        $this->page['updateRequiresPassword'] = $this->updateRequiresPassword();
        $this->page['rememberLoginMode'] = $this->rememberLoginMode();
    }

    /**
     * Executed when this component is bound to a page or layout.
     */
    public function onRun()
    {
        /*
         * Redirect to HTTPS checker
         */
        if ($redirect = $this->redirectForceSecure()) {
            return $redirect;
        }

        /*
         * Activation code supplied
         */
        if ($code = $this->activationCode()) {
            $this->onActivate($code);
        }

        $this->prepareVars();
    }

    //
    // Properties
    //

    /**
     * Returns the logged in user, if available
     */
    public function user()
    {
        if (!Auth::check()) {
            return null;
        }

        return Auth::getUser();
    }

    /**
     * Flag for allowing registration, pulled from UserSettings
     */
    public function canRegister()
    {
        return UserSettings::get('allow_registration', true);
    }

    /**
     * Returns the login model attribute.
     */
    public function loginAttribute()
    {
        return UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
    }

    /**
     * Returns the login label as a word.
     */
    public function loginAttributeLabel()
    {
        return Lang::get($this->loginAttribute() == UserSettings::LOGIN_EMAIL
            ? /*Email*/'ncc.user::lang.login.attribute_email'
            : /*Username*/'ncc.user::lang.login.attribute_username'
        );
    }

    /**
     * Returns the update requires password setting
     */
    public function updateRequiresPassword()
    {
        return $this->property('requirePassword', false);
    }

    /**
     * Returns the login remember mode.
     */
    public function rememberLoginMode()
    {
        return UserSettings::get('remember_login', UserSettings::REMEMBER_ALWAYS);
    }

    /**
     * Looks for the activation code from the URL parameter. If nothing
     * is found, the GET parameter 'activate' is used instead.
     * @return string
     */
    public function activationCode()
    {
        $routeParameter = $this->property('paramCode');

        if ($code = $this->param($routeParameter)) {
            return $code;
        }

        return get('activate');
    }

    //
    // AJAX
    //

    /**
     * Sign in the user
     */
    public function onSignin()
    {
        try {
            /*
             * Validate input
             */
            $data = post();
            $rules = [];

            $rules['login'] = $this->loginAttribute() == UserSettings::LOGIN_USERNAME
                ? 'required|between:2,255'
                : 'required|email|between:6,255';

            $rules['password'] = 'required|between:4,255';

            if (!array_key_exists('login', $data)) {
                $data['login'] = post('username', post('email'));
            }

            $data['login'] = trim($data['login']);

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            /*
             * Authenticate user
             */
            $credentials = [
                'login'    => array_get($data, 'login'),
                'password' => array_get($data, 'password')
            ];

            /*
            * Login remember mode
            */
            switch ($this->rememberLoginMode()) {
                case UserSettings::REMEMBER_ALWAYS:
                    $remember = true;
                    break;
                case UserSettings::REMEMBER_NEVER:
                    $remember = false;
                    break;
                case UserSettings::REMEMBER_ASK:
                    $remember = (bool) array_get($data, 'remember', false);
                    break;
            }

            Event::fire('ncc.user.beforeAuthenticate', [$this, $credentials]);

            $user = Auth::authenticate($credentials, $remember);
            if ($user->isBanned()) {
                Auth::logout();
                throw new AuthException(/*Sorry, this user is currently not activated. Please contact us for further assistance.*/'ncc.user::lang.account.banned');
            }

            /*
             * Record IP address
             */
            if ($ipAddress = Request::ip()) {
                $user->touchIpAddress($ipAddress);
            }

            /*
             * Redirect
             */
            if ($redirect = $this->makeRedirection(true)) {
                return $redirect;
            }
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    /**
     * Register the user
     */
    public function onRegister()
    {
        try {
            if (!$this->canRegister()) {
                throw new ApplicationException(Lang::get(/*Registrations are currently disabled.*/'ncc.user::lang.account.registration_disabled'));
            }

            if ($this->isRegisterThrottled()) {
                throw new ApplicationException(Lang::get(/*Registration is throttled. Please try again later.*/'ncc.user::lang.account.registration_throttled'));
            }

            /*
             * Validate input
             */
            $data = post();

            if (!array_key_exists('password_confirmation', $data)) {
                $data['password_confirmation'] = post('password');
            }

            $user = new UserModel;
            $rules = $user->rules;
            $messages = $user->messages;
            if ($this->loginAttribute() !== UserSettings::LOGIN_USERNAME) {
                unset($rules['username']);
            };
            $validation = Validator::make($data, $rules, $messages);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            /*
             * Record IP address
             */
            if ($ipAddress = Request::ip()) {
                $data['created_ip_address'] = $data['last_ip_address'] = $ipAddress;
            }

            /*
             * Register user
             */
            Event::fire('ncc.user.beforeRegister', [&$data]);

            $requireActivation = UserSettings::get('require_activation', true);
            $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
            $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;
            $user = Auth::register($data, $automaticActivation);

            Event::fire('ncc.user.register', [$user, $data]);

            /*
             * Activation is by the user, send the email
             */
            if ($userActivation) {
                $this->sendActivationEmail($user);

                Flash::success(Lang::get(/*An activation email has been sent to your email address.*/'ncc.user::lang.account.activation_email_sent'));
            }

            /*
             * Automatically activated or not required, log the user in
             */
            if ($automaticActivation || !$requireActivation) {
                Auth::login($user);
            }

            /*
             * Redirect to the intended page after successful sign in
             */
            if ($redirect = $this->makeRedirection(true)) {
                return $redirect;
            }
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    /**
     * Activate the user
     * @param  string $code Activation code
     */
    public function onActivate($code = null)
    {
        try {
            $code = post('code', $code);

            $errorFields = ['code' => Lang::get(/*Invalid activation code supplied.*/'ncc.user::lang.account.invalid_activation_code')];

            /*
             * Break up the code parts
             */
            $parts = explode('!', $code);
            if (count($parts) != 2) {
                throw new ValidationException($errorFields);
            }

            list($userId, $code) = $parts;

            if (!strlen(trim($userId)) || !strlen(trim($code))) {
                throw new ValidationException($errorFields);
            }

            if (!$user = Auth::findUserById($userId)) {
                throw new ValidationException($errorFields);
            }

            if (!$user->attemptActivation($code)) {
                throw new ValidationException($errorFields);
            }

            Flash::success(Lang::get(/*Successfully activated your account.*/'ncc.user::lang.account.success_activation'));

            /*
             * Sign in the user
             */
            Auth::login($user);

        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    /**
     * Update the user
     */
    public function onUpdate()
    {
        if (!$user = $this->user()) {
            return;
        }

        $data = post();

        if ($this->updateRequiresPassword()) {
            if (!$user->checkHashValue('password', $data['password_current'])) {
                throw new ValidationException(['password_current' => Lang::get('ncc.user::lang.account.invalid_current_pass')]);
            }
        }

        if (Input::hasFile('avatar')) {
            $user->avatar = Input::file('avatar');
        }

        $user->fill($data);
        $user->save();

        /*
         * Password has changed, reauthenticate the user
         */
        if (array_key_exists('password', $data) && strlen($data['password'])) {
            Auth::login($user->reload(), true);
        }

        Flash::success(post('flash', Lang::get(/*Settings successfully saved!*/'ncc.user::lang.account.success_saved')));

        /*
         * Redirect
         */
        if ($redirect = $this->makeRedirection()) {
            return $redirect;
        }

        $this->prepareVars();
    }
    /**
     * Update password
     */
    public function onUpdatePassWord()
    {
        if (!$user = $this->user()) {
            return;
        }

        $data = post();
        $rules = $user->rules_pass;
        //So sánh pass nhập vào có đúng với pass hay không
        if (!$user->checkHashValue('password', $data['password_current'])) {
            throw new ValidationException(['password_current' => Lang::get('ncc.user::lang.account.invalid_current_pass')]);
        }       
        // Validate fields
        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }
        // So sánh pass mới vs pass cũ
        if ($user->checkHashValue('password', $data['password'])) {
            throw new ValidationException(['password' => Lang::get('ncc.user::lang.account.invalid_new_pass')]);
        }        

        $user->fill($data);
        $user->save();

        /*
         * Password has changed, reauthenticate the user
         */
        if (array_key_exists('password', $data) && strlen($data['password'])) {
            Auth::login($user->reload(), true);
        }

        Flash::success(post('flash', Lang::get(/*Settings successfully saved!*/'ncc.user::lang.account.success_saved')));

        /*
         * Redirect
         */
        if ($redirect = $this->makeRedirection()) {
            return $redirect;
        }

        $this->prepareVars();
    }
    /**
     * Deactivate user
     */
    public function onDeactivate()
    {
        if (!$user = $this->user()) {
            return;
        }

        if (!$user->checkHashValue('password', post('password'))) {
            throw new ValidationException(['password' => Lang::get('ncc.user::lang.account.invalid_deactivation_pass')]);
        }

        Auth::logout();
        $user->delete();

        Flash::success(post('flash', Lang::get(/*Successfully deactivated your account. Sorry to see you go!*/'ncc.user::lang.account.success_deactivation')));

        /*
         * Redirect
         */
        if ($redirect = $this->makeRedirection()) {
            return $redirect;
        }
    }

    /**
     * Trigger a subsequent activation email
     */
    public function onSendActivationEmail()
    {
        try {
            if (!$user = $this->user()) {
                throw new ApplicationException(Lang::get(/*You must be logged in first!*/'ncc.user::lang.account.login_first'));
            }

            if ($user->is_activated) {
                throw new ApplicationException(Lang::get(/*Your account is already activated!*/'ncc.user::lang.account.already_active'));
            }

            Flash::success(Lang::get(/*An activation email has been sent to your email address.*/'ncc.user::lang.account.activation_email_sent'));

            $this->sendActivationEmail($user);

        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }

        /*
         * Redirect
         */
        if ($redirect = $this->makeRedirection()) {
            return $redirect;
        }
    }

    //
    // Helpers
    //

    /**
     * Returns a link used to activate the user account.
     * @return string
     */
    protected function makeActivationUrl($code)
    {
        $params = [
            $this->property('paramCode') => $code
        ];

        if ($pageName = $this->property('activationPage')) {
            $url = $this->pageUrl($pageName, $params);
        }
        else {
            $url = $this->currentPageUrl($params);
        }

        if (strpos($url, $code) === false) {
            $url .= '?activate=' . $code;
        }

        return $url;
    }

    /**
     * Sends the activation email to a user
     * @param  User $user
     * @return void
     */
    protected function sendActivationEmail($user)
    {
        $code = implode('!', [$user->id, $user->getActivationCode()]);

        $link = $this->makeActivationUrl($code);

        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('ncc.user::mail.activate', $data, function($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }

    /**
     * Redirect to the intended page after successful update, sign in or registration.
     * The URL can come from the "redirect" property or the "redirect" postback value.
     * @return mixed
     */
    protected function makeRedirection($intended = false)
    {
        $method = $intended ? 'intended' : 'to';

        $property = trim((string) $this->property('redirect'));

        // No redirect
        if ($property === '0') {
            return;
        }
        // Refresh page
        if ($property === '') {
            return Redirect::refresh();
        }

        $redirectUrl = $this->pageUrl($property) ?: $property;

        if ($redirectUrl = post('redirect', $redirectUrl)) {
            return Redirect::$method($redirectUrl);
        }
    }

    /**
     * Checks if the force secure property is enabled and if so
     * returns a redirect object.
     * @return mixed
     */
    protected function redirectForceSecure()
    {
        if (
            Request::secure() ||
            Request::ajax() ||
            !$this->property('forceSecure')
        ) {
            return;
        }

        return Redirect::secure(Request::path());
    }

    /**
     * Returns true if user is throttled.
     * @return bool
     */
    protected function isRegisterThrottled()
    {
        if (!UserSettings::get('use_register_throttle', false)) {
            return false;
        }

        return UserModel::isRegisterThrottled(Request::ip());
    }
}
