<?php

class OnlineShop_Framework_AbstractUser /*extends Object_Concrete*/ {

    protected $username;
    protected $password;

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getUsername() {
        return $this->username;
    }
}
