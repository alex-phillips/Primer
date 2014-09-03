<?php

class UsersController extends AppController
{
    public function beforeFilter()
    {
        Auth::allow(array(
            'login',
            'logout',
            'view',
            'add',
            'showCaptcha',
            'reset_password',
            'forgot_password',
            'verify',
        ));
    }

    public function login()
    {
        $this->view->title = 'Login';
        // Redirect if user is already logged in
        if (Session::isUserLoggedIn()) {
            Router::redirect('/');
        }

        if (Request::is('post')) {
            if (!Request::post()->get('data.user.username')) {
                Session::setFlash('Username cannot be left blank', 'failure');
                Router::redirect('/users/login/');
            }
            if (!Request::post()->get('data.user.password')) {
                Session::setFlash('Password cannot be left blank', 'failure');
                Router::redirect('/users/login/');
            }

            $users = $this->User->find(array(
                'conditions' => array(
                    'username' => Request::post()->get('data.user.username'),
                )
            ));
            if (empty($users)) {
                Session::setFlash('Username or password is incorrect', 'failure');
                Router::redirect('/users/login/');
            }
            $this->User = array_shift($users);

            if ($this->User->id == '') {
                Session::setFlash('Username or password is incorrect', 'failure');
                Router::redirect('/users/login/');
            }

            if (Security::verifyHash(Request::post()->get('data.user.password'), $this->User->password) === false) {
                Session::setFlash('Username or password is incorrect', 'failure');
                Router::redirect('/users/login/');
            }

            if ($this->User->active == 1) {
                Auth::login($this->User);

                // Set remember me token and cookie
                if (Request::post()->get('data.user.rememberme')) {

                    // generate 64 char random string
                    $random_token_string = hash('sha256', mt_rand());

                    Request::post()->set('data.user.rememberme_token', $random_token_string);
                    Request::post()->Set('data.user.id', $this->User->id);
                    $this->User->set(Request::post()->get('data.user'));
                    $this->User->save();

                    // generate cookie string that consists of userid, randomstring and combined hash of both
                    $cookie_string_first_part = $this->User->id . ':' . $random_token_string;
                    $cookie_string_hash = hash('sha256', $cookie_string_first_part);
                    $cookie_string = base64_encode($cookie_string_first_part . ':' . $cookie_string_hash);

                    // set cookie (2 weeks)
                    setcookie('rememberme', $cookie_string, time() + 1209600, "/", DOMAIN);
                }

                Session::setFlash('Welcome, ' . $this->User->username, 'success');

                if ($referrer = Request::query()->get('forward_to')) {
                    Router::redirect($referrer);
                }
                Router::redirect('/');
            }
            else {
                Session::setFlash("Your account is not activated yet. Please click on the confirm link in the mail.", 'warning');
                Router::redirect('/users/login/');
            }
        }
    }

    public function logout()
    {
        Auth::logout();
        setcookie('rememberme', false, time() - (3600 * 3650), '/', DOMAIN);
        Router::redirect('/');
    }

    public function view($id = null)
    {
        if ($id == null) {
            Router::redirect('/users/view/' . Session::read('Auth.id'));
        }

        if (is_numeric($id)) {
            $this->User = $this->User->findById($id);
        }
        else {
            $this->User = $this->User->findFirst(array(
                'conditions' => array(
                    'username' => $id,
                )
            ));
        }

        if (!$this->User) {
            Session::setFlash("That user does not exist", 'failure');
            Router::redirect('/');
        }

        if ($this->User->id == '') {
            Router::abort();
        }

        Primer::setValue('rendering_object', $this->User);

        $this->view->title = $this->User->username;
        $this->view->set('user', $this->User);
    }

    public function edit($id = null)
    {
        // If no ID is passed, use currently logged in user
        if ($id == null) {
            Router::redirect('/users/edit/' . Session::read('Auth.id'));
        }

        if ($id != Session::read('Auth.id') && !Session::isAdmin()) {
            Session::setFlash("You are not authorized to edit that user", 'failure');
            Router::redirect('/users/index');
        }

        $this->User = $this->User->findById($id);
        $this->view->title = 'Edit ' . $this->User->username . '\'s Account';

        if ($this->User->id == '') {
            Session::setFlash('User does not exist', 'failure');
            Router::redirect('/');
        }

        $this->view->set('user', $this->User);

        if (Request::is('post')) {
            // Some form SPECIFIC validation

            // Require current password to save changes
            if (!Request::post()->get('data.user.password')) {
                Session::setFlash("Please enter your current password to save changes", 'failure');
                return;
            }
            // Verify current password is correct
            else if (!password_verify(Request::post()->get('data.user.password'), $this->User->password)) {
                Session::setFlash("Current password is incorrect", 'failure');
                return;
            }

            if (Request::post()->get('data.user.newpass1') != Request::post()->get('data.user.newpass2')) {
                Session::setFlash("The new passwords don't match", 'failure');
                return;
            }

            if ($newPassword = Request::post()->get('data.user.newpass1')) {
                Request::post()->set('data.user.password', $newPassword);
            }

            // Escape email address
            Request::post()->set('data.user.email', htmlentities(Request::post()->get('data.user.email'), ENT_QUOTES));

            // TODO: better way to go about doing this, for security reasons. For ALL models...
            // We are already checking ownership on one of the ID's, but which is best, and they
            // either BOTH need to equal, or make the SQL query on the one we check...
            if ($id != Request::post()->get('data.user.id')) {
                Session::setFlash('User IDs do not match. Please try again.', 'failure');
                Router::redirect('/users/edit/' . $id);
            }

            // Attempt to update the user in the database
            $this->User->set(Request::post()->get('data.user'));
            if ($this->User->save()) {
                // Find user again to get updated information into the Session
                $this->User = $this->User->findById($id);
                Auth::login($this->User);
                Session::setFlash('Your account has been successfully updated', 'success');
                Router::redirect('/users/view/' . $id);
            }
            else {
                Session::setFlash($this->User->errors, 'failure');
                Router::redirect('/users/edit/' . $id);
            }

        }

        // Set default text in textarea to current bio
        Primer::setJSValue('bio', $this->User->bio, 'user');
    }

    public function delete($id = null)
    {
        if (Request::is('post') && Session::isAdmin()) {
            $this->User->deleteById(Request::post()->get('data.user.id'));
            Router::redirect('/users/');
        }
    }

    // register page
    // TODO: need this function to define captcha. find a way to integrate this into register()
    public function add()
    {
        $this->view->title = 'Register';

        if (Request::is('post')) {
            // Check Captcha
            if (!Security::checkCaptcha(Request::post()->get('data.user.captcha'))) {
                Session::setFlash("The entered captcha security characters wrong", 'failure');
                return;
            }

            // Make sure password and repeat are not empty and that they are the same
            if (!Request::post()->get('data.user.password1') || !Request::post()->get('data.user.password2')) {
                Session::setFlash("Password cannot be left empty", 'failure');
                return;
            }
            else if (Request::post()->get('data.user.password1') !== Request::post()->get('data.user.password2')) {
                Session::setFlash("Passwords do not match", 'failure');
                return;
            }

            // Set password field
            Request::post()->set('data.user.password', Request::post()->get('data.user.password1'));

            // generate random hash for email verification (40 char string)
            Request::post()->set('data.user.activation_hash', sha1(uniqid(mt_rand(), true)));

            $this->User = new User(Request::post()->get('data.user'));

            if ($this->User->save()) {
                Session::setFlash('An activation e-mail has been sent', 'success');
                Router::redirect('/posts/');
            }
            else {
                Session::setFlash('There was a problem creating the user. Please try again.', 'failure');
                Router::redirect('/users/add');
            }
        }
    }

    /**
     * Verify new user creation with e-mailed link and make account active
     *
     * @param $email
     * @param $user_verification_code
     */
    public function verify($email, $user_verification_code)
    {
        if ($email && $user_verification_code) {
            $users = $this->User->find(array(
                'conditions' => array(
                    'AND' => array(
                        'email' => urldecode($email),
                        'activation_hash' => urldecode($user_verification_code),
                    )
                )
            ));
            if (!empty($users) && sizeof($users) === 1) {
                $this->User = $users[0];
                $this->User->active = 1;
                $this->User->activation_hash = null;
                if ($this->User->save()) {
                    Session::setFlash('You may now log in', 'success');
                }
                else {
                    Session::setFlash($this->User->errors, 'failure');
                }
            }
            else {
                Session::setFlash('There was a problem verifying that account. Please contact support.', 'failure');
            }
        }

        Router::redirect('/posts/');
    }

    public function forgot_password()
    {
        $this->view->title = 'Request Password Reset';
        if (Request::is('post')) {
            $username = htmlentities(Request::post()->get('data.user.username'), ENT_QUOTES, 'utf-8');
            $users = $this->User->find(array(
                'conditions' => array(
                    'username' => $username
                )
            ));
            $this->User = $users[0];
            if ($this->User) {
                $timestamp = time();
                $this->User->password_reset_hash = sha1(uniqid(mt_rand(), true));
                $this->User->password_reset_timestamp = $timestamp;

                if ($this->_sendPasswordResetMail() == true) {
                    $this->User->save();
                    Session::setFlash('Your new password has been emailed to you.', 'success');
                    Router::redirect('/');
                }
                else {
                    Session::setFlash('There was a problem sending you your reset password. Please contact webmaster', 'failure');
                    Router::redirect('/');
                }
            }
        }
    }

    private function _sendPasswordResetMail()
    {
        $mail = new PHPMailer;
        // use SMTP or use mail()
        if (EMAIL_USE_SMTP) {
            $mail->IsSMTP(); // Set mailer to use SMTP
            $mail->Host = EMAIL_SMTP_HOST; // Specify main and backup server
            $mail->SMTPAuth = EMAIL_SMTP_AUTH; // Enable SMTP authentication
            $mail->Username = EMAIL_SMTP_USERNAME; // SMTP username
            $mail->Password = EMAIL_SMTP_PASSWORD; // SMTP password

            if (EMAIL_SMTP_ENCRYPTION) {
                $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION; // Enable encryption, 'ssl' also accepted
            }
        }
        else {
            $mail->IsMail();
        }

        $mail->From = 'noreply@wootables.com';
        $mail->FromName = 'noreply@wootables.com';
        $mail->AddAddress($this->User->email);
        $mail->Subject = 'Password Reset for wootables.com';

        $link = 'http://www.wootables.com/users/verifypasswordrequest/' . urlencode($this->User->username) . '/' . urlencode($this->User->password_reset_hash);
        $mail->Body = 'Please click on this link to reset your password: <a href="' . $link . '">' . $link . '</a>';

        if (!$mail->Send()) {
            return false;
        }
        else {
            return true;
        }
    }

    public function reset_password($username, $verification_code)
    {
        if (Request::is('post')) {
            $users = $this->User->find(array(
                'conditions' => array(
                    'username' => Request::post()->get('data.user.username')
                )
            ));
            if (!empty($users)) {
                $this->User = $users[0];
                if ($this->User->password_reset_hash == Request::post()->get('data.user.password_reset_hash')) {
                    if (Request::post()->get('data.user.newpass1') === Request::post()->get('data.user.newpass2')) {
                        $this->User->password = Request::post()->get('data.user.newpass1');
                        $this->User->password_reset_hash = null;
                        $this->User->password_reset_timestamp = null;
                        $this->User->save();
                        Session::setFlash('Your password has been successfully updated', 'success');
                        Router::redirect('/');
                    }
                }
            }
        }
        else {
            $username = htmlspecialchars($username, ENT_QUOTES, 'utf-8');
            $verification_code = htmlentities($verification_code, ENT_QUOTES);

            $users = $this->User->find(array(
                'conditions' => array(
                    'AND' => array(
                        'username' => $username,
                        'password_reset_hash' => $verification_code
                    )
                )
            ));
            if (!empty($users)) {
                $this->User = $users[0];
                // 3600 seconds are 1 hour
                $timestamp_one_hour_ago = time() - 3600;

                if ($this->User->password_reset_timestamp > $timestamp_one_hour_ago) {
                    $this->view->set('username', $this->User->username);
                    $this->view->set('password_reset_hash', $this->User->password_reset_hash);
                    return;
                } else {
                    Session::setFlash('Your reset link has expired. Please try again.');
                    Router::redirect('/login/');
                }
            }
            else {
                Router::redirect('/');
            }
        }
    }

    /**
     * special helper method:
     * showCaptcha() returns an image, so we can use it in img tags in the views, like
     * <img src="/users/showCaptcha" />
     */
    public function showCaptcha()
    {
        Security::generateCaptcha();
        Security::showCaptcha();
    }
}