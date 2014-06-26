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
        $authComponent = AuthComponent::getInstance();
        $this->password = $authComponent->hash($this->password);

        if (!isset($this->avatar) || !$this->avatar) {
            $this->avatar = $this->_getGravatarImageUrl($this->email);
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