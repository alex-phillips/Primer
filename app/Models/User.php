<?php

/**
 * class Login_Model
 * handles the user's users, logout, username editing, password changing...
 */
class User extends App
{
    private $_roles = array(
        1 => 'User',
        9 => 'Administrator',
    );

    protected static $_validate = array(
        'email' => array(
            'required' => array(
                'message' => 'E-mail cannot be left blank'
            ),
            'email' => array(
                'message' => 'Please enter a valid e-mail address'
            ),
            'max_length' => array(
                'size' => 64,
                'message' => 'E-mail is too long'
            ),
        ),
        'username' => array(
            'required' => array(
                'message' => 'Username cannot be left blank'
            ),
            'unique' => array(
                'message' => 'That username is taken. Please choose another.'
            ),
            'max_length' => array(
                'size' => 64,
                'message' => 'Username is too long'
            ),
            'min_length' => array(
                'size' => 4,
                'message' => 'Username is too short'
            ),
            'regex' => array(
                'rule' => '/^[a-z\d]{2,64}$/i',
                'message' => 'Please enter a valid username'
            )
        ),
    );

    protected function beforeSave()
    {
        /*
         * Make sure that we NEED to save the password and the currently set one
         * isn't already the hash that's stored in the DB.
         */
        $dbUser = User::findById($this->id);
        if ($dbUser) {
            if ($this->password !== $dbUser->password) {
                $this->password = Security::hash($this->password);
            }
        }
        else {
            $this->password = Security::hash($this->password);
        }

        if (!isset($this->avatar) || !$this->avatar) {
            $this->avatar = $this->_getGravatarImageUrl($this->email);
        }

        return true;
    }

    protected function afterSave()
    {
        if (Router::getAction() === 'add') {
            return Mail::send(array(
                    'from' => 'noreply@wootables.com',
                    'fromName' => 'noreply@wootables.com',
                    'recipients' => array(
                        $this->email,
                    ),
                    'subject' => 'Account Activation at Wootables.com',
                    'body' => 'Welcome to wootables.com! Please click on this link to activate your account: http://www.wootables.com/users/verify/' . urlencode($this->email) . '/' . urlencode($this->activation_hash),
                ));
        }

        return true;
    }

    public function getRoleName()
    {
        return $this->_roles[$this->role];
    }

    public function setRole($role)
    {
        if (is_numeric($role)) {
            if (isset($this->_roles[$role])) {
                $this->role = $role;
                return;
            }
        }
        else {
            foreach ($this->_roles as $id => $name) {
                if ($name === $role) {
                    $this->role = $id;
                    return;
                }
            }
        }
    }

    /**
     * @param $email
     * @param int $s size in pixels [1-2048]
     * @param string $d imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r maximum rating (inclusive) [ g | pg | r | x ]
     * @param array $atts optional additional key/value attributes to include in the IMG tag
     *
     * @return string
     */
    private function _getGravatarImageUrl($email, $s = 250, $d = 'mm', $r = 'pg', $atts = array())
    {
        $url = 'http://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . "?s=$s&d=$d&r=$r";

        return $url;
    }
}
