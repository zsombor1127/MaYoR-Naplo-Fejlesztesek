<?php
    if (_RIGHTS_OK !== true) die();

    //Jogosultság ellenőrzése
    if (__NAPLOADMIN !== true) $_SESSION['alert'][] = 'page:insufficient_access';

    require_once('include/modules/naplo/share/diak.php');
    require_once('include/modules/naplo/share/tanar.php');
    require_once('include/modules/naplo/share/szulo.php');

    //Lekéri a felhasználókat, hogy ki tudja őket listázni
    $students = GetDiakok();
    $teachers = GetTanarok();
    $parents = GetSzulok();
    
    $date = GetLoginLogDate(); //Lekéri a keresésnél választható dátumokat
    $action = readVariable($_POST['action'], 'strictstring');

    //Innen listázza ki a front-end a hibakódokat
    $flags = array(
        '0' => 'Sikeres bejelentkezés',
        '1' => 'Lejárt jelszó',
        '2' => 'Ismeretlen hiba',
        '3' => '2fa szükséges',
        '21' => 'Hibás felhasználónév',
        '22' => 'Multi uid',
        '23' => 'Hibás jelszó',
        '24' => 'Felhasználó letiltva',
        '25' => 'Bejelentkezés korlátozott',
    );


    //A két action mehet egybe mert lényegében mind a kettő ugyan az
    if($action == 'search' || $action == 'switchpage') {

        //Beolvassa a szükséges értékeket
        $tolDt = readVariable($_POST['tolDt'], 'date');
        $igDt = readVariable($_POST['igDt'], 'date');
        $flag = readVariable($_POST['flag'], 'enum');
        $userAccount = readVariable($_POST['account'], 'userAccount'); // status:id formában érkezik meg a status lehet (tanar, diak, szulo)
        $raw_useraccount = $userAccount; //A nyers adat kerül bele a search_data tömbbe

        //A kötőjel itt az összes policy-t jelenti
        $toPolicy = readVariable($_POST['toPolicy'], 'enum', _POLICY, array('private', 'parent', 'public', '-'));

        if(isset($_POST['current'])) $currentPage = readVariable($_POST['current'], 'id');
        else $currentPage = 1;

        //Megnézi, hogy merre lapoztunk, ennek függvényében változtatja a currentPage változót
        if(isset($_POST['switch'])) {
            $switch_mode = readVariable($_POST['switch'], 'strictstring', 'back', array('back', 'forward'));
            if($switch_mode == 'forward') $currentPage = $currentPage + 1;
            elseif($switch_mode == 'back' && $currentPage > 1) $currentPage = $currentPage - 1;
        }
        
        //Feldolgozza a bejővő felhasználónevet (itt még id)
        if($userAccount != 'mayoradmin' && $userAccount != '-') {
            $userAccount = explode(':', $userAccount);
            if($userAccount[0] == 'diak') {
                $userAccount = getDiakFelhasznaloById($userAccount[1]);
            } elseif($userAccount[0] == 'tanar') {
                $userAccount = getTanarFelhasznaloById($userAccount[1]);
            } elseif($userAccount[0] == 'szulo') {
                $userAccount = getSzuloAdat($userAccount[1])['userAccount'];
                $toPolicy = 'parent'; //Ha szülőként próbál belépni akkor dob egy 21-es hibát private policyre, ezt szűri ki
            }
        } elseif($userAccount == '-') {
            $userAccount = null;
        }

        //Lekéri a bejelentkezéseket
        $results = GetLogins(array('toPolicy' => $toPolicy, 'tolDt' => $tolDt, 'igDt' => $igDt, 'userAccount' => $userAccount, 'flag' => $flag), $currentPage);
        $login_stat = $results['stat']; //Ez a tömb a statisztikát tartalmazza
        $page_data = $results['page']; //Az oldalváltáshoz szükséges változókat tárolja
        $results = $results['data']; //A lekérdezett bejelentkezések adatait tárolja

        //Visszaküldi a keresési feltételeket, az oldalváltásnál van szerepe
        $search_data = array(
            'toPolicy' => $toPolicy,
            'account' => $raw_useraccount,
            'tolDt' => $tolDt,
            'igDt' => $igDt,
            'flag' => $flag
        );
    }
?>