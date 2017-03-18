<?php

namespace PQ\Controllers;

use PQ\Core\Config;
use PQ\Core\Controller;

use PQ\Core\Csrf;
use PQ\Core\Mail;
use PQ\Core\Random;
use PQ\Core\Redirect;
use PQ\Core\Request;
use PQ\Core\Session;
use PQ\Models\Token as TokenModel;
use PQ\Models\User as UserModel;
use PQ\Models\Login as LoginModel;

class Login extends Controller
{
    protected $user;
    protected $login;
    protected $token;

    private $Request;
    private $Redirect;
    private $Csrf;
    private $Random;
    private $Mail;
    private $Config;

    public function __construct(Config $Config, Csrf $Csrf, Random $Random, Redirect $Redirect, Request $Request, Session $Session, Mail $Mail)
    {
        $this->user = new UserModel();
        $this->login = new LoginModel();
        $this->token = new TokenModel();

        $this->Request = $Request;
        $this->Redirect = $Redirect;
        $this->Csrf = $Csrf;
        $this->Random = $Random;
        $this->Mail = $Mail;
        $this->Config = $Config;

        parent::__construct();
    }

    public function index()
    {
        if (!$this->Request->isGet()) {
            $this->Redirect->to('index');
            return;
        }

        if ($this->login->isUserLoggedIn()) {
            $this->Redirect->to('index');
        }

        $this->View->render('login/index');
    }

    public function action()
    {
        if (!$this->Request->isPost()) {
            $this->Redirect->to('login');
            return;
        }

        $username = $this->Request->post('username', true);
        $password = $this->Request->post('password', true);
        $token = $this->Request->post('token', true);


        if (!$this->Csrf->isTokenValid($token)) {
            $this->Session->add("flash_error", "Failed to login user.");
            $this->Redirect->to('login/index');
            return;
        }

        $login = $this->login->login($username, $password);
        if (!$login) {
            $this->Redirect->to('login/index');
            return;
        }


        if ($this->user->isAdmin()) {
            $this->Redirect->to('admin/dashboard');
            return;
        }

        $result = $this->user->getUserByUsername($username);
        $this->Redirect->to('level/index/' . $result->level);
        return;
    }

    public function logout()
    {
        $this->login->logout();
        $this->Redirect->to('index');
        return;
    }

    public function sendmail()
    {
        if (!$this->Request->isPost()) {
            $this->Redirect->to('login');
            return;
        }

        $username = $this->Request->post('username', true);


        if ($this->user->doesUserExist('username', $username) === false) {
            $this->View->renderJSON(
                [
                    'status' => 'error',
                    'message' => 'Failed to send reset email'
                ],
                200
            );
            return;
        }

        $user = $this->user->getUserByUsername($username);

        $token = $this->Random->generate(21);
        $this->token->set($user->id, 'lostpassword', $token);

        $this->Mail->sendMail(
            $user->email,
            $this->Config->get('DEFAULT_EMAIL'),
            $this->Config->get('DEFAULT_NAME'),
            $this->Config->get('DEFAULT_NAME') .  'Password Reset',
            'Use the following link to reset Email: ' . $this->Config->get('URL') . 'login/resetpassword/' . $user->username . '/' . $this->token->get($user->id, 'lostpassword')
        );

        $this->View->renderJSON(
            [
                'status' => 'success',
                'message' => 'Password reset mail sent'
            ],
            200
        );
        return;
    }

    public function resetpassword($username, $token)
    {
        if (!$this->user->doesUserExist('username', $username)) {
            $this->Redirect->to('login');
            return;
        }

        if ($this->token->isExpired($this->user->getUserByUsername($username)->id, 'lostpassword')) {
            $this->Redirect->to('login');
            return;
        }

        $this->View->render('login/resetpassword', [
            'username' => $username
        ]);
    }
}