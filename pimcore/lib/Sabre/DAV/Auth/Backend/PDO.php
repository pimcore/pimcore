<?php

/**
 * This is an authentication backend that uses a file to manage passwords.
 *
 * The backend file must conform to Apache's htdigest format
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_Backend_PDO extends Sabre_DAV_Auth_Backend_AbstractDigest {

    private $pdo;

    /**
     * Creates the backend object. 
     *
     * If the filename argument is passed in, it will parse out the specified file fist.
     * 
     * @param string $filename 
     * @return void
     */
    public function __construct(PDO $pdo) {

        $this->pdo = $pdo;

    }

    /**
     * Returns a users' information 
     * 
     * @param string $realm 
     * @param string $username 
     * @return string 
     */
    public function getUserInfo($realm,$username) {

        $stmt = $this->pdo->prepare('SELECT username, digesta1, email FROM users WHERE username = ?');
        $stmt->execute(array($username));
        $result = $stmt->fetchAll();

        if (!count($result)) return false;
        $user = array(
            'uri' => 'principals/' . $result[0]['username'],
            'digestHash' => $result[0]['digesta1'],
        );
        if ($result[0]['email']) $user['{http://sabredav.org/ns}email-address'] = $result[0]['email'];
        return $user;

    }

    /**
     * Returns a list of all users
     *
     * @return array
     */
    public function getUsers() {

        $result = $this->pdo->query('SELECT username, email FROM users')->fetchAll();
        
        $rv = array();
        foreach($result as $user) {

            $r = array(
                'uri' => 'principals/' . $user['username'],
            );
            if ($user['email']) $r['{http://sabredav.org/ns}email-address'] = $user['email'];
            $rv[] = $r;

        }

        return $rv;

    }

}
