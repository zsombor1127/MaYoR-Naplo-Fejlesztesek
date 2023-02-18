<?php
    //Megadja hány darab rekord jelenjen meg egy oldalon
    define('_RECORDPERPAGE', 100);
    
    //Belepakolja egy tömbbe a két megadott dátum közötti napokat
    function createDateRangeArray($tolDt,$igDt) {
        $date_array = [];
    
        $iDateFrom = mktime(1, 0, 0, substr($tolDt, 5, 2), substr($tolDt, 8, 2), substr($tolDt, 0, 4));
        $iDateTo = mktime(1, 0, 0, substr($igDt, 5, 2), substr($igDt, 8, 2), substr($igDt, 0, 4));
    
        if ($iDateTo >= $iDateFrom) {
            array_push($date_array, date('Y-m-d', $iDateFrom));
            while ($iDateFrom<$iDateTo) {
                $iDateFrom += 86400;
                array_push($date_array, date('Y-m-d', $iDateFrom));
            }
        }
        return $date_array;
    }

    //Lekéri az első rekord idejét, majd belepakolja az onnan eltelt napokat egy tömbbe
    function GetLoginLogDate() {
        $q = "SELECT dt FROM loginLog WHERE logId = 1";
        $res = db_query($q, array('fv' => 'GetLoginLogDate', 'modul' => 'login', 'result' => 'record'), $lr);

        $period = createDateRangeArray(date('Y-m-d', strtotime($res['dt'])), date('Y-m-d'));
        return $period;
    }

    //Ellenőrzi a megadott dátum érvényességét
    function DateIsvalid($date, $format = "Y-m-d") {
        $d = DateTime::createFromFormat($format, $date);
        if($d && $d->format($format) == $date) return True;
        else return False;
    }

    /*
        Megnézi hány db rekord van az adott feltételekkel, ezt felosztja n méretű chunkokra
        és attól függően, hogy melyik oldal van kiválasztva visszaadja a lekérdezett adatokat
    */
    function GetLogins($SET = array('toPolicy' => '-', 'tolDt' => null, 'igDt' => null, 'userAccount' => null, 'flag' => null), $current_page) {
        $toPolicy = $SET['toPolicy'];
        $tolDt = $SET['tolDt'];
        $igDt = $SET['igDt'];
        $userAccount = $SET['userAccount'];
        $flag = $SET['flag'];

        //Ha a current_page változó nem egy szám, akkor 1 lesz az értéke
        if(!is_numeric($current_page)) $current_page = 1;


        //Ha nem érvényesek a dátumok az elmúlt 1 napot állítja be
        if(!DateIsvalid($igDt)) $igDt = date('Y-m-d', time());
        if(!DateIsvalid($tolDt)) $tolDt = $igDt;

        //Ha nincs account, policy és flag csak dátum szerint szűrünk
        if(is_null($userAccount) && $toPolicy == '-' && $flag == '-') {
            $q = "SELECT * FROM loginLog WHERE DATE(dt) BETWEEN '%s' AND '%s'";
            $v = array($tolDt, $igDt);
        }
        //Ha csak account van
        elseif(!is_null($userAccount) && $toPolicy == '-' && $flag == '-') {
            $q = "SELECT * FROM loginLog WHERE DATE(dt) BETWEEN '%s' AND '%s' AND userAccount='%s'";
            $v = array($tolDt, $igDt, $userAccount);
        }
        //Ha csak policy van
        elseif(is_null($userAccount) && $toPolicy != '-' && $flag == '-') {
            $q = "SELECT * FROM loginLog WHERE DATE(dt) BETWEEN '%s' AND '%s' AND policy='%s'";
            $v = array($tolDt, $igDt, $toPolicy);
        }
        //Ha csak flag van
        elseif(is_null($userAccount) && $toPolicy == '-' && $flag != '-') {
            $q = "SELECT * FROM loginLog WHERE DATE(dt) BETWEEN '%s' AND '%s' AND flag='%s' ORDER BY userAccount";
            $v = array($tolDt, $igDt, $flag);
        }
        //Ha csak policy és account van
        elseif(!is_null($userAccount) && $toPolicy != '-' && $flag == '-') {
            $q = "SELECT * FROM loginLog WHERE DATE(dt) BETWEEN '%s' AND '%s' AND userAccount='%s' AND policy='%s'";
            $v = array($tolDt, $igDt, $userAccount, $toPolicy);
        }
        //Ha csak account és flag van
        elseif(!is_null($userAccount) && $toPolicy == '-' && $flag != '-') {
            $q = "SELECT * FROM loginLog WHERE DATE(dt) BETWEEN '%s' AND '%s' AND userAccount='%s' AND flag='%s'";
            $v = array($tolDt, $igDt, $userAccount, $flag);
        }
        //Ha csak policy és flag van
        elseif(is_null($userAccount) && $toPolicy != '-' && $flag != '-') {
            $q = "SELECT * FROM loginLog WHERE DATE(dt) BETWEEN '%s' AND '%s' AND policy='%s' AND flag='%s' ORDER BY userAccount";
            $v = array($tolDt, $igDt, $toPolicy, $flag);
        }
        //Ha van minden
        elseif(!is_null($userAccount) && $toPolicy != '-' && $flag != '-') {
            $q = "SELECT * FROM loginLog WHERE DATE(dt) BETWEEN '%s' AND '%s' AND userAccount='%s' AND policy='%s' AND flag='%s'";
            $v = array($tolDt, $igDt, $userAccount, $toPolicy, $flag);
        }

        //Lekérdezi az adatokat
        $res = db_query($q, array('fv' => 'GetLogins', 'modul' => 'login', 'result' => 'indexed', 'values' => $v), $lr);

        //Megszámolja hány db hibás jelszó és felhasználónév van
        $bad_uid = 0;
        $bad_pwd = 0;
        $records = count($res);
        foreach($res as $login) {
            $stat_flag = intval($login['flag']);
            if($stat_flag == 23) $bad_pwd++;
            elseif($stat_flag == 21) $bad_uid++;
        }

        //A kiválasztott oldal függvényében  kiszámolja mettől meddig kellenek az adatok
        $chunks = ceil(count($res) / _RECORDPERPAGE);
        if($current_page > $chunks) $current_page = $chunks;
        if($current_page < 0) $current_page = 1;

        $from = ($current_page * _RECORDPERPAGE) - _RECORDPERPAGE;
        $res = array_slice($res, $from, _RECORDPERPAGE, true);

        //Összeállítja az adatokat
        $dataset = array(
            'stat' => array(
                'logins' => $records,
                'bad_uid' => $bad_uid,
                'bad_pwd' => $bad_pwd,
            ),
            'page' => array(
                'current_page' => $current_page,
                'chunks' => $chunks,
            ),
            'data' => $res,
        );
        return $dataset;
    }
?>