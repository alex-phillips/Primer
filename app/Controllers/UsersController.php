<?php

class UsersController extends AppController
{
    public function beforeFilter()
    {
        $this->Auth->allow(array(
            'index',
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

    public function index()
    {

    }

    public function login()
    {
        $this->view->title = 'Login';
        // Redirect if user is already logged in
        if ($this->Session->read('user_logged_in')) {
            Router::redirect('/');
        }

        if ($this->request->is('post')) {
            if (empty($this->request->data['user']['username'])) {
                $this->Session->setFlash('Username cannot be left blank' . $this->request->data['user']['username'], 'failure');
                Router::redirect('/users/login/');
            }
            if (empty($this->request->data['user']['password'])) {
                $this->Session->setFlash('Password cannot be left blank', 'failure');
                Router::redirect('/users/login/');
            }

            $users = $this->User->find(array(
                'conditions' => array(
                    'username' => $this->request->data['user']['username'],
                )
            ));
            if (empty($users)) {
                $this->Session->setFlash('Username or password is incorrect', 'failure');
                Router::redirect('/users/login/');
            }
            $this->User = array_shift($users);

            if ($this->User->id == '') {
                $this->Session->setFlash('Username or password is incorrect', 'failure');
                Router::redirect('/users/login/');
            }

            if (password_verify($this->request->data['user']['password'], $this->User->password) == false) {
                $this->Session->setFlash('Username or password is incorrect', 'failure');
                Router::redirect('/users/login/');
            }

            if ($this->User->active == 1) {
                $this->Session->write($this->User);
                $this->Session->write('user_logged_in', true);

                // Set remember me token and cookie
                if (isset($this->request->data['user']['rememberme'])) {

                    // generate 64 char random string
                    $random_token_string = hash('sha256', mt_rand());

                    $this->request->data['user']['rememberme_token'] = $random_token_string;
                    $this->request->data['user']['id'] = $this->User->id;
                    $this->User->set($this->request->data['user']);
                    $this->User->save();

                    // generate cookie string that consists of userid, randomstring and combined hash of both
                    $cookie_string_first_part = $this->User->id . ':' . $random_token_string;
                    $cookie_string_hash = hash('sha256', $cookie_string_first_part);
                    $cookie_string = base64_encode($cookie_string_first_part . ':' . $cookie_string_hash);

                    // set cookie (2 weeks)
                    setcookie('rememberme', $cookie_string, time() + 1209600, "/", DOMAIN);
                }

                $this->Session->setFlash('Welcome, ' . $this->User->username, 'success');
                Router::redirect('/');
            }
            else {
                $this->Session->setFlash("Your account is not activated yet. Please click on the confirm link in the mail.", 'warning');
                Router::redirect('/users/login/');
            }
        }
    }

    public function logout()
    {
        $this->Session->destroy();
        setcookie('rememberme', false, time() - (3600 * 3650), '/', DOMAIN);
        Router::redirect('/');
    }

    public function view($id = null)
    {
        if ($id == null) {
            Router::redirect('/users/view/' . $this->Session->read('id'));
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
            $this->Session->setFlash("That user does not exist", 'failure');
            Router::redirect('/');
        }

        if ($this->User->id == '') {
            Router::error404();
        }

        Primer::setValue('rendering_object', $this->User);

        $this->view->title = $this->User->username;
        $this->view->set('user', $this->User);
    }

    public function edit($id = null)
    {
        // If no ID is passed, use currently logged in user
        if ($id == null) {
            Router::redirect('/users/edit/' . $this->Session->read('id'));
        }

        if ($id != $this->Session->read('id') && !$this->Session->isAdmin()) {
            $this->Session->setFlash("You are not authorized to edit that user", 'failure');
            Router::redirect('/users/index');
        }

        $this->User = $this->User->findById($id);
        $this->view->title = 'Edit ' . $this->User->username . '\'s Account';

        if ($this->User->id == '') {
            $this->Session->setFlash('User does not exist', 'failure');
            Router::redirect('/');
        }

        $this->view->set('user', $this->User);

        if ($this->request->is('post')) {
            // Some form SPECIFIC validation

            // Require current password to save changes
            if (!isset($this->request->data['user']['password']) || $this->request->data['user']['password'] == '') {
                $this->Session->setFlash("Please enter your current password to save changes", 'failure');
                return;
            }
            // Verify current password is correct
            else if (!password_verify($this->request->data['user']['password'], $this->User->password)) {
                $this->Session->setFlash("Current password is incorrect", 'failure');
                return;
            }

            if ($this->request->data['user']['newpass1'] != $this->request->data['user']['newpass2']) {
                $this->Session->setFlash("The new passwords don't match", 'failure');
                return;
            }

            if ($this->request->data['user']['newpass1'] != '') {
                $this->request->data['user']['password'] = $this->request->data['user']['newpass1'];
            }

            // Escape email address
            $this->request->data['user']['email'] = htmlentities($this->request->data['user']['email'], ENT_QUOTES);

            // TODO: better way to go about doing this, for security reasons. For ALL models...
            // We are already checking ownership on one of the ID's, but which is best, and they
            // either BOTH need to equal, or make the SQL query on the one we check...
            if ($id != $this->request->data['user']['id']) {
                $this->Session->setFlash('User IDs do not match. Please try again.', 'failure');
                Router::redirect('/users/edit/' . $id);
            }

            // Attempt to update the user in the database
            $this->User->set($this->request->data['user']);
            if ($this->User->save()) {
                // Find user again to get updated information into the Session
                $this->User = $this->User->findById($id);
                $this->Session->write($this->User);
                $this->Session->setFlash('Your account has been successfully updated', 'success');
                Router::redirect('/users/view/' . $id);
            }
            else {
                $this->Session->setFlash('There was a problem updating your information. Please try again.', 'failure');
                Router::redirect('/users/edit/' . $id);
            }

        }

        // Set default text in textarea to current bio
        Primer::setJSValue('bio', $this->User->bio, 'user');
    }

    public function delete($id)
    {
        if ($this->request->is('post') && $this->Session->isAdmin()) {
            $this->User->deleteById($this->request->data['user']['id']);
            Router::redirect('/users/');
        }
    }

    // register page
    // TODO: need this function to define captcha. find a way to integrate this into register()
    public function add()
    {
        $this->view->title = 'Register';

        if ($this->request->is('post')) {
            // Check Captcha
            $captcha = new Captcha();

            if (!$captcha->checkCaptcha($this->request->data['user']['captcha'])) {
                $this->Session->setFlash("The entered captcha security characters wrong", 'failure');
                return;
            }

            // Make sure password and repeat are not empty and that they are the same
            if (empty($this->request->data['user']['password1']) || empty($this->request->data['user']['password2'])) {
                $this->Session->setFlash("Password cannot be left empty", 'failure');
                return;
            }
            else if ($this->request->data['user']['password1'] !== $this->request->data['user']['password2']) {
                $this->Session->setFlash("Passwords do not match", 'failure');
                return;
            }

            // Set password field
            $this->request->data['user']['password'] = $this->request->data['user']['password1'];

            // generate random hash for email verification (40 char string)
            $this->request->data['user']['activation_hash'] = sha1(uniqid(mt_rand(), true));

            $this->User = new User($this->request->data['user']);

            if ($this->User->save()) {
                // send a verification email
                if ($this->_sendVerificationEmail()) {
                    $this->Session->setFlash('An activation e-mail has been sent', 'success');
                    Router::redirect('/posts/');
                }
                else {

                    // delete this users account immediately, as we could not send a verification email
                    $this->User->delete();

                    $this->Session->setFlash("Sorry, we could not send you an verification mail. Your account has NOT been created.", 'failure');
                    Router::redirect('/users/add');
                }
            }
            else {
                $this->Session->setFlash('There was a problem creating the user. Please try again.', 'failure');
                Router::redirect('/users/add');
            }
        }
    }

    /**
     * sendVerificationEmail()
     * sends an email to the provided email address
     * @return boolean gives back true if mail has been sent, gives back false if no mail could been sent
     */
    private function _sendVerificationEmail()
    {
        $mail = new PHPMailer();

        // use SMTP or use mail()
        if (EMAIL_USE_SMTP) {
            $mail->IsSMTP();                                      // Set mailer to use SMTP
            $mail->Host = EMAIL_SMTP_HOST;  // Specify main and backup server
            $mail->SMTPAuth = EMAIL_SMTP_AUTH;                               // Enable SMTP authentication
            $mail->Username = EMAIL_SMTP_USERNAME;                            // SMTP username
            $mail->Password = EMAIL_SMTP_PASSWORD;                           // SMTP password

            if (EMAIL_SMTP_ENCRYPTION) {
                $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;                  // Enable encryption, 'ssl' also accepted
            }

        }
        else {
            $mail->IsMail();
        }

        $mail->From = EMAIL_VERIFICATION_FROM_EMAIL;
        $mail->FromName = EMAIL_VERIFICATION_FROM_NAME;
        $mail->AddAddress($this->User->email);
        $mail->Subject = EMAIL_VERIFICATION_SUBJECT;
        $mail->Body = EMAIL_VERIFICATION_CONTENT . EMAIL_VERIFICATION_URL . '/' . urlencode($this->User->email) . '/' . urlencode($this->User->activation_hash);

        if(!$mail->Send()) {
            return false;
        }

        return true;
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
                    $this->Session->setFlash('You may now log in', 'success');
                }
            }
            else {
                $this->Session->setFlash('There was a problem verifying that account. Please contact support.', 'failure');
            }
        }

        Router::redirect('/posts/');
    }

    public function forgot_password()
    {
        $this->view->title = 'Request Password Reset';
        if ($this->request->is('post')) {
            $username = htmlentities($this->request->data['user']['username'], ENT_QUOTES, 'utf-8');
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
                    $this->Session->setFlash('Your new password has been emailed to you.', 'success');
                    Router::redirect('/');
                }
                else {
                    $this->Session->setFlash('There was a problem sending you your reset password. Please contact webmaster', 'failure');
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

        $mail->From = EMAIL_PASSWORDRESET_FROM_EMAIL;
        $mail->FromName = EMAIL_PASSWORDRESET_FROM_NAME;
        $mail->AddAddress($this->User->email);
        $mail->Subject = EMAIL_PASSWORDRESET_SUBJECT;

        $link = EMAIL_PASSWORDRESET_URL . '/' . urlencode($this->User->username) . '/' . urlencode($this->User->password_reset_hash);
        $mail->Body = EMAIL_PASSWORDRESET_CONTENT . ' <a href="' . $link . '">' . $link . '</a>';

        if (!$mail->Send()) {
            return false;
        }
        else {
            return true;
        }
    }

    public function reset_password($username, $verification_code)
    {
        if ($this->request->is('post')) {
            $users = $this->User->find(array(
                'conditions' => array(
                    'username' => $this->request->data['user']['username']
                )
            ));
            if (!empty($users)) {
                $this->User = $users[0];
                if ($this->User->password_reset_hash == $this->request->data['user']['password_reset_hash']) {
                    if ($this->request->data['user']['newpass1'] == $this->request->data['user']['newpass2']) {
                        $this->User->password = $this->request->data['user']['newpass1'];
                        $this->User->password_reset_hash = null;
                        $this->User->password_reset_timestamp = null;
                        $this->User->save();
                        $this->Session->setFlash('Your password has been successfully updated', 'success');
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
                    $this->Session->setFlash('Your reset link has expired. Please try again.');
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
        $captcha = new Captcha();
        // generate new string with captcha characters and write them into $_SESSION['captcha']
        $captcha->generateCaptcha();
        // render a img showing the characters (=the captcha)
        $captcha->showCaptcha();
    }
}