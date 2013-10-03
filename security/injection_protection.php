<?php

# jak sie zabezpieczyc przed Stefanem?

$login = $request->getPost('login');
$password = $request->getPost('password');

$query = Doctrine_Query::create()
    ->from('TableWithUsers')
    ->where('login = ?', $login)
    ->andWhere('password = md5(?)', $password);

$result = $query->fetchOne(array(), Doctrine::HYDRATE_ARRAY);
if($row == false) {
    throw new Exception('User with this credentials does not exist in database');
}

echo 'Hello, ' . $login;