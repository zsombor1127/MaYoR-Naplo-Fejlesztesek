<?php
    if (_RIGHTS_OK !== true) die();
    global $date, $results, $all_user, $login_stat, $students, $teachers, $parents, $page_data, $search_data, $flags;

    //Kirendereli a keresőmezőt
    putLogHeader($date, $flags, array('students' => $students, 'teachers' => $teachers, 'parents' => $parents));

    //Ha létezik a login_stat tömb és vannak rekordok akkor kirendereli a statisztikákat
    if(isset($login_stat) && !empty(count($results))) {
        putLoginStat($login_stat);
    }

    //Ha létezik a page_data tömb és vannak rekordok akkor kirendereli az oldalváltó gombot
    if(isset($page_data) && !empty(count($results))) {
        putSwitchPageForm($search_data, $page_data);
    }

    //Ha létezik a results tömb akkor kirendereli a lekérdezett bejelentkezéseket
    if(isset($results)) {
        putSearchResults($results);
    }
?>