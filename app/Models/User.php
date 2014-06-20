<?php

/**
 * class Login_Model
 * handles the user's users, logout, username editing, password changing...
 */
class User extends Model
{
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
        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
        // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
        // compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
        // want the parameter: as an array with, currently only used with 'cost' => XX.
        // @TODO: need to transfer password hashing and retrieval to Auth component
        if (isset($this->password)) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT, array('cost' => HASH_COST_FACTOR));
        }

        // escapin' this, additionally removing everything that could be (html/javascript-) code
        if (isset($this->username)) {
            $this->username = htmlentities($this->username, ENT_QUOTES);
        }
        if (isset($this->email)) {
            $this->email = htmlentities($this->email, ENT_QUOTES);

            if (!$this->avatar) {
                $this->avatar = $this->_getGravatarImageUrl($this->email);
            }
        }

        return true;
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
        $url = 'http://www.gravatar.com/avatar/';
        $url .= md5( strtolower( trim( $email ) ) );
        $url .= "?s=$s&d=$d&r=$r";
        return $url;
    }
}